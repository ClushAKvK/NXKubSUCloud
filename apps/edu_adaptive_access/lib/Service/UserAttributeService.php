<?php
declare(strict_types=1);

namespace OCA\EduAdaptiveAccess\Service;

use OCA\EduAdaptiveAccess\AppInfo\Application;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IUser;

class UserAttributeService {
    public function __construct(
        private IConfig $config,
        private IGroupManager $groupManager,
        private PolicyConfigService $policyConfigService,
        private AcademicCatalogService $academicCatalogService,
    ) {
    }

    public function getUserProfile(IUser $user): array {
        $uid = $user->getUID();
        $policy = $this->policyConfigService->getGlobalPolicy();

        $role = 'student';
        if ($this->groupManager->isAdmin($uid)) {
            $role = 'admin';
        } elseif ($this->groupManager->isInGroup($uid, $policy['teachers_group'])) {
            $role = 'teacher';
        } elseif ($this->groupManager->isInGroup($uid, $policy['students_group'])) {
            $role = 'student';
        }

        $groupIds = $this->groupManager->getUserGroupIds($user);

        $directionCode = '';
        $directionTitle = '';
        $disciplineName = $this->config->getUserValue($uid, Application::APP_ID, 'discipline_name', '');
        $academicGroupName = '';

        if ($role === 'student') {
            $resolved = $this->academicCatalogService->resolveStudentDirectionFromGroups(
                $groupIds,
                [
                    $policy['students_group'],
                    $policy['teachers_group'],
                    'admin',
                ]
            );

            $academicGroupName = $resolved['group_name'];
            $directionCode = $resolved['direction_code'];
            $directionTitle = $resolved['direction_title'];
        } else {
            $directionCode = $this->config->getUserValue($uid, Application::APP_ID, 'direction_code', '');
            $directionTitle = $this->academicCatalogService->getDirectionTitle($directionCode);
        }

        $allowedDisciplines = $directionCode !== ''
            ? $this->academicCatalogService->getDisciplinesForDirection($directionCode)
            : [];

        if ($disciplineName !== '' && !in_array($disciplineName, $allowedDisciplines, true)) {
            $disciplineName = '';
        }

        return [
            'uid' => $uid,
            'display_name' => $user->getDisplayName(),
            'role' => $role,
            'direction_code' => $directionCode,
            'direction_title' => $directionTitle,
            'discipline_name' => $disciplineName,
            'academic_group_name' => $academicGroupName,
            'group_ids' => $groupIds,

            // legacy compatibility
            'course_code' => $directionCode,
            'department' => '',
            'managed_device' => false,
        ];
    }

    public function saveUserProfile(IUser $user, array $input): void {
        $uid = $user->getUID();
        $profile = $this->getUserProfile($user);

        $disciplineName = trim((string)($input['discipline_name'] ?? ''));
        $newDiscipline = trim((string)($input['new_discipline'] ?? ''));

        if ($profile['role'] === 'student') {
            $directionCode = $profile['direction_code'];
            $allowedDisciplines = $directionCode !== ''
                ? $this->academicCatalogService->getDisciplinesForDirection($directionCode)
                : [];

            if ($disciplineName !== '' && !in_array($disciplineName, $allowedDisciplines, true)) {
                $disciplineName = '';
            }

            $this->config->setUserValue($uid, Application::APP_ID, 'discipline_name', $disciplineName);
            return;
        }

        $directionCode = trim((string)($input['direction_code'] ?? ''));

        if ($directionCode !== '') {
            $directionTitle = $this->academicCatalogService->getDirectionTitle($directionCode);
            if ($directionTitle === '') {
                $directionCode = '';
                $disciplineName = '';
            }
        } else {
            $disciplineName = '';
        }

        if ($directionCode !== '' && $newDiscipline !== '') {
            $disciplineName = $this->academicCatalogService->addDiscipline($directionCode, $newDiscipline);
        }

        $allowedDisciplines = $directionCode !== ''
            ? $this->academicCatalogService->getDisciplinesForDirection($directionCode)
            : [];

        if ($disciplineName !== '' && !in_array($disciplineName, $allowedDisciplines, true)) {
            $disciplineName = '';
        }

        $this->config->setUserValue($uid, Application::APP_ID, 'direction_code', $directionCode);
        $this->config->setUserValue($uid, Application::APP_ID, 'discipline_name', $disciplineName);
    }
}