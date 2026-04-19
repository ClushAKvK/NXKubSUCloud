<?php
declare(strict_types=1);

namespace OCA\EduAdaptiveAccess\Service;

use OCA\EduAdaptiveAccess\AppInfo\Application;
use OCP\IConfig;

class PolicyConfigService {
    private const KEY_GLOBAL = 'global_policy';

    public function __construct(
        private IConfig $config,
    ) {
    }

    public function getGlobalPolicy(): array {
        $raw = $this->config->getAppValue(Application::APP_ID, self::KEY_GLOBAL, '');
        if ($raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return $this->normalizePolicy($decoded);
            }
        }

        return $this->normalizePolicy([]);
    }

    public function saveGlobalPolicy(array $input): void {
        $policy = $this->normalizePolicy($input);
        $this->config->setAppValue(
            Application::APP_ID,
            self::KEY_GLOBAL,
            json_encode($policy, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        );
    }

    private function normalizePolicy(array $input): array {
        $mode = (string)($input['mode'] ?? 'normal');
        if (!in_array($mode, ['normal', 'deadline', 'exam'], true)) {
            $mode = 'normal';
        }

        $tau1 = (float)($input['tau1'] ?? 0.45);
        $tau2 = (float)($input['tau2'] ?? 0.75);

        if ($tau1 < 0) {
            $tau1 = 0;
        }
        if ($tau2 > 1) {
            $tau2 = 1;
        }
        if ($tau1 > $tau2) {
            $tau1 = 0.45;
            $tau2 = 0.75;
        }

        $trustedCidrs = trim((string)($input['trusted_cidrs'] ?? "127.0.0.1/32\n10.0.0.0/8\n192.168.0.0/16\n62.60.245.229/32"));
        $teachersGroup = trim((string)($input['teachers_group'] ?? 'admin'));
        $studentsGroup = trim((string)($input['students_group'] ?? 'students'));

        return [
            'mode' => $mode,
            'tau1' => round($tau1, 2),
            'tau2' => round($tau2, 2),
            'trusted_cidrs' => $trustedCidrs,
            'teachers_group' => $teachersGroup !== '' ? $teachersGroup : 'admin',
            'students_group' => $studentsGroup !== '' ? $studentsGroup : 'students',
        ];
    }

    public function getTrustedCidrsAsArray(): array {
        $policy = $this->getGlobalPolicy();
        $lines = preg_split('/\r\n|\r|\n/', $policy['trusted_cidrs'] ?? '') ?: [];

        return array_values(array_filter(array_map('trim', $lines), static fn(string $line) => $line !== ''));
    }
}