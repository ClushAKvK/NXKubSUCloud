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

        return [
            'uid' => $uid,
            'display_name' => $user->getDisplayName(),
            'role' => $role,
            'course_code' => $this->config->getUserValue($uid, Application::APP_ID, 'course_code', ''),
            'department' => $this->config->getUserValue($uid, Application::APP_ID, 'department', ''),
            'managed_device' => $this->config->getUserValue($uid, Application::APP_ID, 'managed_device', '0') === '1',
            'group_ids' => $this->groupManager->getUserGroupIds($user),
        ];
    }

    public function saveUserProfile(IUser $user, array $input): void {
        $uid = $user->getUID();

        $courseCode = trim((string)($input['course_code'] ?? ''));
        $department = trim((string)($input['department'] ?? ''));
        $managedDevice = isset($input['managed_device']) ? '1' : '0';

        $this->config->setUserValue($uid, Application::APP_ID, 'course_code', $courseCode);
        $this->config->setUserValue($uid, Application::APP_ID, 'department', $department);
        $this->config->setUserValue($uid, Application::APP_ID, 'managed_device', $managedDevice);
    }
}