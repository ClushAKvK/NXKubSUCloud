<?php
declare(strict_types=1);

namespace OCA\EduAdaptiveAccess\Service;

use OCA\EduAdaptiveAccess\AppInfo\Application;
use OCP\IConfig;
use OCP\ISession;
use OCP\IUser;

class StepUpService {
    private const CHALLENGES_KEY = 'stepup_challenges';
    private const SESSION_APPROVALS_KEY = 'stepup_approvals';
    private const OTP_TTL_SECONDS = 300;
    private const APPROVAL_TTL_SECONDS = 300;
    private const MAX_ATTEMPTS = 3;

    public function __construct(
        private IConfig $config,
        private ISession $session,
    ) {
    }

    public function hasValidApproval(IUser $user, string $resourceId, string $action): bool {
        $approvals = $this->getApprovals();
        $key = $this->approvalKey($user->getUID(), $resourceId, $action);
        $now = time();

        foreach ($approvals as $approvalKey => $expiresAt) {
            if (!is_int($expiresAt) || $expiresAt < $now) {
                unset($approvals[$approvalKey]);
            }
        }

        $this->saveApprovals($approvals);

        return isset($approvals[$key]) && $approvals[$key] >= $now;
    }

    public function createChallengeAndSend(IUser $user, array $resource, string $action, string $targetUrl): array {
        $challenges = $this->getChallenges();
        $this->cleanupExpiredChallenges($challenges);

        $challengeId = bin2hex(random_bytes(16));
        $code = (string)random_int(100000, 999999);
        $resourceId = (string)($resource['id'] ?? '');
        $resourceTitle = (string)($resource['title'] ?? 'Ресурс');

        $challenges[$challengeId] = [
            'id' => $challengeId,
            'uid' => $user->getUID(),
            'resource_id' => $resourceId,
            'resource_title' => $resourceTitle,
            'action' => $action,
            'debug_code' => $code,
            'code_hash' => password_hash($code, PASSWORD_DEFAULT),
            'target_url' => $targetUrl,
            'attempts' => 0,
            'max_attempts' => self::MAX_ATTEMPTS,
            'expires_at' => time() + self::OTP_TTL_SECONDS,
            'created_at' => time(),
        ];

        $this->persistChallenges($challenges);

        return [
            'ok' => true,
            'challenge_id' => $challengeId,
        ];
    }

    public function getChallengeViewData(IUser $user, string $challengeId): ?array {
        $challenges = $this->getChallenges();
        $this->cleanupExpiredChallenges($challenges);

        $challenge = $challenges[$challengeId] ?? null;
        if (!is_array($challenge)) {
            return null;
        }

        if (($challenge['uid'] ?? '') !== $user->getUID()) {
            return null;
        }

        if (($challenge['expires_at'] ?? 0) < time()) {
            unset($challenges[$challengeId]);
            $this->persistChallenges($challenges);
            return null;
        }

        $remainingSeconds = max(0, (int)$challenge['expires_at'] - time());

        return [
            'id' => $challenge['id'],
            'resource_title' => (string)$challenge['resource_title'],
            'action' => (string)$challenge['action'],
            'action_label' => $this->getActionLabel((string)$challenge['action']),
            'attempts_left' => max(0, (int)$challenge['max_attempts'] - (int)$challenge['attempts']),
            'remaining_seconds' => $remainingSeconds,
            'target_url' => (string)$challenge['target_url'],
            'debug_code' => (string)$challenge['debug_code'],
        ];
    }

    public function verifyChallenge(IUser $user, string $challengeId, string $code): array {
        $code = trim($code);
        $challenges = $this->getChallenges();
        $this->cleanupExpiredChallenges($challenges);

        $challenge = $challenges[$challengeId] ?? null;
        if (!is_array($challenge)) {
            return [
                'ok' => false,
                'error' => 'Код подтверждения не найден или уже истёк.',
            ];
        }

        if (($challenge['uid'] ?? '') !== $user->getUID()) {
            return [
                'ok' => false,
                'error' => 'Код подтверждения принадлежит другому пользователю.',
            ];
        }

        if (($challenge['expires_at'] ?? 0) < time()) {
            unset($challenges[$challengeId]);
            $this->persistChallenges($challenges);

            return [
                'ok' => false,
                'error' => 'Срок действия кода истёк. Попробуйте запросить действие заново.',
            ];
        }

        if ($code === '') {
            return [
                'ok' => false,
                'error' => 'Введите код подтверждения.',
            ];
        }

        if ((int)$challenge['attempts'] >= (int)$challenge['max_attempts']) {
            unset($challenges[$challengeId]);
            $this->persistChallenges($challenges);

            return [
                'ok' => false,
                'error' => 'Количество попыток исчерпано. Попробуйте запросить действие заново.',
            ];
        }

        $isValid = password_verify($code, (string)$challenge['code_hash']);

        if (!$isValid) {
            $challenge['attempts'] = (int)$challenge['attempts'] + 1;

            if ((int)$challenge['attempts'] >= (int)$challenge['max_attempts']) {
                unset($challenges[$challengeId]);
            } else {
                $challenges[$challengeId] = $challenge;
            }

            $this->persistChallenges($challenges);

            return [
                'ok' => false,
                'error' => 'Неверный код подтверждения.',
            ];
        }

        $this->grantApproval(
            $user->getUID(),
            (string)$challenge['resource_id'],
            (string)$challenge['action']
        );

        $targetUrl = (string)$challenge['target_url'];

        unset($challenges[$challengeId]);
        $this->persistChallenges($challenges);

        return [
            'ok' => true,
            'redirect_url' => $targetUrl,
            'action' => (string)$challenge['action'],
        ];
    }

    private function grantApproval(string $uid, string $resourceId, string $action): void {
        $approvals = $this->getApprovals();
        $approvals[$this->approvalKey($uid, $resourceId, $action)] = time() + self::APPROVAL_TTL_SECONDS;
        $this->saveApprovals($approvals);
    }

    private function approvalKey(string $uid, string $resourceId, string $action): string {
        return $uid . ':' . $resourceId . ':' . $action;
    }

    private function getApprovals(): array {
        $approvals = $this->session->get(self::SESSION_APPROVALS_KEY);
        return is_array($approvals) ? $approvals : [];
    }

    private function saveApprovals(array $approvals): void {
        $this->session->set(self::SESSION_APPROVALS_KEY, $approvals);
    }

    private function getChallenges(): array {
        $raw = $this->config->getAppValue(Application::APP_ID, self::CHALLENGES_KEY, '');
        if ($raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function persistChallenges(array $challenges): void {
        $this->config->setAppValue(
            Application::APP_ID,
            self::CHALLENGES_KEY,
            json_encode($challenges, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        );
    }

    private function cleanupExpiredChallenges(array &$challenges): void {
        $now = time();

        foreach ($challenges as $id => $challenge) {
            if (!is_array($challenge)) {
                unset($challenges[$id]);
                continue;
            }

            if ((int)($challenge['expires_at'] ?? 0) < $now) {
                unset($challenges[$id]);
            }
        }

        $this->persistChallenges($challenges);
    }

    private function getActionLabel(string $action): string {
        return match ($action) {
            'read' => 'чтение в браузере',
            'download' => 'скачивание',
            default => 'доступ',
        };
    }
}