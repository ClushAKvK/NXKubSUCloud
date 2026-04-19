<?php
declare(strict_types=1);

namespace OCA\EduAdaptiveAccess\Service;

use DateTimeImmutable;
use OCP\IRequest;
use OCP\IUser;

class AdaptiveAccessService {
    public function __construct(
        private IRequest $request,
        private PolicyConfigService $policyConfigService,
        private UserAttributeService $userAttributeService,
        private CidrMatcher $cidrMatcher,
    ) {
    }

    public function evaluate(array $resource, IUser $user, string $action): array {
        $profile = $this->userAttributeService->getUserProfile($user);
        $policy = $this->policyConfigService->getGlobalPolicy();

        $reasons = [];
        $baseAllowed = $this->isBaseAllowed($resource, $profile, $action, $reasons);

        if (!$baseAllowed) {
            return [
                'decision' => 'DENY',
                'risk' => 1.0,
                'base_allowed' => false,
                'subject' => $profile,
                'context' => $this->buildContext($profile, $policy),
                'reasons' => $reasons,
            ];
        }

        $context = $this->buildContext($profile, $policy);
        $risk = $this->calculateRisk($resource, $profile, $action, $context, $reasons);

        $decision = 'ALLOW';
        if ($risk >= (float)$policy['tau2']) {
            $decision = 'DENY';
        } elseif ($risk >= (float)$policy['tau1']) {
            $decision = 'STEP_UP';
        }

        if ($decision === 'STEP_UP' && $action === 'download') {
            $reasons[] = 'Для MVP действие понижено: скачивание запрещено, чтение карточки ресурса остаётся доступным';
        }

        return [
            'decision' => $decision,
            'risk' => round(min(1.0, $risk), 2),
            'base_allowed' => true,
            'subject' => $profile,
            'context' => $context,
            'reasons' => array_values(array_unique($reasons)),
        ];
    }

    private function buildContext(array $profile, array $policy): array {
        $ip = $this->request->getRemoteAddress();
        $trustedNetwork = $this->cidrMatcher->matchesAny($ip, $this->policyConfigService->getTrustedCidrsAsArray());

        return [
            'ip' => $ip,
            'trusted_network' => $trustedNetwork,
            'managed_device' => (bool)$profile['managed_device'],
            'mode' => $policy['mode'],
        ];
    }

    private function isBaseAllowed(array $resource, array $profile, string $action, array &$reasons): bool {
        $role = $profile['role'];
        $sameCourse = ($profile['course_code'] ?? '') !== '' &&
            ($profile['course_code'] ?? '') === ($resource['course_code'] ?? '');
        $isOwner = ($profile['uid'] ?? '') === ($resource['owner_uid'] ?? '');

        if ($role === 'admin') {
            $reasons[] = 'Администратор: базовый доступ разрешён';
            return true;
        }

        if ($isOwner) {
            $reasons[] = 'Владелец ресурса';
            return true;
        }

        $sensitivity = $resource['sensitivity'] ?? 'learning';

        if ($sensitivity === 'public') {
            $reasons[] = 'Публичный ресурс';
            return true;
        }

        if ($sensitivity === 'learning') {
            if ($role === 'teacher') {
                $reasons[] = 'Преподаватель';
                return true;
            }

            if ($role === 'student' && $sameCourse) {
                $reasons[] = 'Студент своего курса';
                return true;
            }

            $reasons[] = 'Learning-ресурс доступен только преподавателю или студенту своего курса';
            return false;
        }

        if ($sensitivity === 'personal') {
            if ($role === 'teacher' && $sameCourse) {
                $reasons[] = 'Преподаватель своего курса';
                return true;
            }

            $reasons[] = 'Personal-ресурс доступен владельцу, администратору или преподавателю своего курса';
            return false;
        }

        if ($sensitivity === 'exam') {
            if ($role === 'teacher') {
                $reasons[] = 'Преподаватель';
                return true;
            }

            if ($role === 'student' && $sameCourse) {
                $openFrom = trim((string)($resource['open_from'] ?? ''));
                if ($openFrom !== '') {
                    $now = new DateTimeImmutable('now');
                    $openDate = new DateTimeImmutable($openFrom);
                    if ($now < $openDate) {
                        $reasons[] = 'Экзаменационный ресурс ещё не открыт по времени';
                        return false;
                    }
                }

                if ($action === 'download' && !($resource['allow_student_download'] ?? false)) {
                    $reasons[] = 'Для студентов скачивание exam-ресурса базово запрещено';
                    return false;
                }

                $reasons[] = 'Студент своего курса и ресурс уже открыт';
                return true;
            }

            $reasons[] = 'Exam-ресурс доступен преподавателю, администратору или студенту своего курса после открытия';
            return false;
        }

        $reasons[] = 'Неизвестный класс чувствительности';
        return false;
    }

    private function calculateRisk(
        array $resource,
        array $profile,
        string $action,
        array $context,
        array &$reasons
    ): float {
        $sensitivityWeight = match ($resource['sensitivity'] ?? 'learning') {
            'public' => 0.20,
            'learning' => 0.40,
            'personal' => 0.80,
            'exam' => 1.00,
            default => 0.40,
        };

        $actionWeight = match ($action) {
            'read' => 0.20,
            'download' => 0.50,
            default => 0.30,
        };

        $risk = (0.35 * $sensitivityWeight) + (0.25 * $actionWeight);

        if (!$context['trusted_network']) {
            $risk += 0.25;
            $reasons[] = 'Недоверенная сеть';
        } else {
            $reasons[] = 'Доверенная сеть';
        }

        if (!$context['managed_device']) {
            $risk += 0.20;
            $reasons[] = 'Неуправляемое устройство';
        } else {
            $reasons[] = 'Управляемое устройство';
        }

        if ($context['mode'] === 'deadline') {
            $risk += 0.15;
            $reasons[] = 'Режим дедлайна';
        } elseif ($context['mode'] === 'exam') {
            $risk += 0.30;
            $reasons[] = 'Экзаменационный режим';
        } else {
            $reasons[] = 'Обычный учебный режим';
        }

        if (($resource['sensitivity'] ?? '') === 'exam' && $action === 'download') {
            $risk += 0.10;
            $reasons[] = 'Скачивание exam-ресурса повышает риск';
        }

        if (($profile['role'] ?? '') === 'teacher') {
            $risk -= 0.05;
            $reasons[] = 'Роль преподавателя немного снижает риск';
        }

        return max(0.0, min(1.0, $risk));
    }
}