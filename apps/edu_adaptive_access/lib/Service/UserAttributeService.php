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

        $directionCode = $this->config->getUserValue($uid, Application::APP_ID, 'direction_code', '');
        $directionTitle = $this->academicCatalogService->getDirectionTitle($directionCode);
        $disciplineName = $this->config->getUserValue($uid, Application::APP_ID, 'discipline_name', '');

        return [
            'uid' => $uid,
            'display_name' => $user->getDisplayName(),
            'role' => $role,
            'direction_code' => $directionCode,
            'direction_title' => $directionTitle,
            'discipline_name' => $disciplineName,
            'managed_device' => $this->config->getUserValue($uid, Application::APP_ID, 'managed_device', '0') === '1',
            'group_ids' => $this->groupManager->getUserGroupIds($user),

            // legacy compatibility
            'course_code' => $directionCode,
            'department' => '',
        ];
    }

    public function saveUserProfile(IUser $user, array $input): void {
        $uid = $user->getUID();

        $directionCode = trim((string)($input['direction_code'] ?? ''));
        $disciplineName = trim((string)($input['discipline_name'] ?? ''));
        $newDiscipline = trim((string)($input['new_discipline'] ?? ''));
        $managedDevice = isset($input['managed_device']) ? '1' : '0';

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
        $this->config->setUserValue($uid, Application::APP_ID, 'managed_device', $managedDevice);
    }
}