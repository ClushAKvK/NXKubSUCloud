<?php
declare(strict_types=1);

namespace OCA\EduAdaptiveAccess\Service;

use OCA\EduAdaptiveAccess\AppInfo\Application;
use OCP\IConfig;

class ResourceRegistryService {
    private const KEY_RESOURCES = 'resources';

    public function __construct(
        private IConfig $config,
    ) {
    }

    public function getAll(): array {
        $raw = $this->config->getAppValue(Application::APP_ID, self::KEY_RESOURCES, '');
        if ($raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [];
        }

        usort($decoded, static function (array $a, array $b): int {
            return strcmp(($a['title'] ?? ''), ($b['title'] ?? ''));
        });

        return $decoded;
    }

    public function getById(string $id): ?array {
        foreach ($this->getAll() as $resource) {
            if (($resource['id'] ?? '') === $id) {
                return $resource;
            }
        }

        return null;
    }

    public function save(array $input, string $ownerUid): array {
        $resources = $this->getAll();
        $id = trim((string)($input['id'] ?? ''));

        $directionCode = trim((string)($input['direction_code'] ?? ''));
        $directionTitle = trim((string)($input['direction_title'] ?? ''));
        $disciplineName = trim((string)($input['discipline_name'] ?? ''));

        $normalized = [
            'id' => $id !== '' ? $id : bin2hex(random_bytes(8)),
            'owner_uid' => $ownerUid,
            'owner_path' => $this->normalizePath((string)($input['owner_path'] ?? '')),
            'title' => trim((string)($input['title'] ?? '')),
            'description' => trim((string)($input['description'] ?? '')),

            'direction_code' => $directionCode,
            'direction_title' => $directionTitle,
            'discipline_name' => $disciplineName,

            // legacy compatibility
            'course_code' => $directionCode,

            'publication_status' => $this->normalizePublicationStatus((string)($input['publication_status'] ?? 'published')),
            'target_scope' => $this->normalizeTargetScope((string)($input['target_scope'] ?? 'direction')),
            'target_group' => trim((string)($input['target_group'] ?? '')),

            'sensitivity' => $this->normalizeSensitivity((string)($input['sensitivity'] ?? 'learning')),
            'open_from' => trim((string)($input['open_from'] ?? '')),
            'due_until' => trim((string)($input['due_until'] ?? '')),
            'allow_student_download' => isset($input['allow_student_download']),
            'updated_at' => date('c'),
            'created_at' => date('c'),
        ];

        if ($normalized['target_scope'] !== 'group') {
            $normalized['target_group'] = '';
        }

        $updated = false;
        foreach ($resources as $index => $resource) {
            if (($resource['id'] ?? '') === $normalized['id']) {
                $normalized['created_at'] = $resource['created_at'] ?? date('c');
                $resources[$index] = $normalized;
                $updated = true;
                break;
            }
        }

        if (!$updated) {
            $resources[] = $normalized;
        }

        $this->persist($resources);

        return $normalized;
    }

    public function delete(string $id): void {
        $resources = array_values(array_filter(
            $this->getAll(),
            static fn(array $resource): bool => ($resource['id'] ?? '') !== $id
        ));

        $this->persist($resources);
    }

    private function persist(array $resources): void {
        $this->config->setAppValue(
            Application::APP_ID,
            self::KEY_RESOURCES,
            json_encode($resources, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        );
    }

    private function normalizeSensitivity(string $value): string {
        $value = trim($value);
        if (!in_array($value, ['public', 'learning', 'personal', 'exam'], true)) {
            return 'learning';
        }

        return $value;
    }

    private function normalizePublicationStatus(string $value): string {
        $value = trim($value);
        if (!in_array($value, ['draft', 'published'], true)) {
            return 'published';
        }

        return $value;
    }

    private function normalizeTargetScope(string $value): string {
        $value = trim($value);
        if (!in_array($value, ['direction', 'group', 'teachers_only'], true)) {
            return 'direction';
        }

        return $value;
    }

    private function normalizePath(string $path): string {
        $path = trim($path);
        if ($path === '') {
            return '/';
        }

        if ($path[0] !== '/') {
            $path = '/' . $path;
        }

        return $path;
    }
}