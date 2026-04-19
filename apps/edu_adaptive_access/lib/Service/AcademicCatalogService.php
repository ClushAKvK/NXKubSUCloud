<?php
declare(strict_types=1);

namespace OCA\EduAdaptiveAccess\Service;

use OCA\EduAdaptiveAccess\AppInfo\Application;
use OCP\IConfig;

class AcademicCatalogService {
    private const KEY_CATALOG = 'academic_catalog';
    private const KEY_GROUP_DIRECTION_MAP = 'group_direction_map';

    public function __construct(
        private IConfig $config,
    ) {
    }

    public function getCatalog(): array {
        $raw = $this->config->getAppValue(Application::APP_ID, self::KEY_CATALOG, '');
        if ($raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return $this->normalizeCatalog($decoded);
            }
        }

        $catalog = $this->getDefaultCatalog();
        $this->persistCatalog($catalog);

        return $catalog;
    }

    public function getDirectionOptions(): array {
        $catalog = $this->getCatalog();
        $result = [];

        foreach ($catalog as $code => $item) {
            $result[] = [
                'code' => $code,
                'title' => (string)($item['title'] ?? $code),
                'disciplines' => array_values($item['disciplines'] ?? []),
            ];
        }

        usort($result, static fn(array $a, array $b): int => strcmp($a['code'], $b['code']));

        return $result;
    }

    public function getDisciplinesForDirection(string $directionCode): array {
        $catalog = $this->getCatalog();

        if (!isset($catalog[$directionCode])) {
            return [];
        }

        return array_values($catalog[$directionCode]['disciplines'] ?? []);
    }

    public function getDirectionTitle(string $directionCode): string {
        $catalog = $this->getCatalog();

        return (string)($catalog[$directionCode]['title'] ?? '');
    }

    public function addDirection(string $directionCode, string $directionTitle): string {
        $directionCode = trim($directionCode);
        $directionTitle = trim($directionTitle);

        if ($directionCode === '' || $directionTitle === '') {
            return '';
        }

        $catalog = $this->getCatalog();
        if (!isset($catalog[$directionCode])) {
            $catalog[$directionCode] = [
                'title' => $directionTitle,
                'disciplines' => [],
            ];
        } else {
            $catalog[$directionCode]['title'] = $directionTitle;
        }

        ksort($catalog, SORT_NATURAL | SORT_FLAG_CASE);
        $this->persistCatalog($catalog);

        return $directionCode;
    }

    public function addDiscipline(string $directionCode, string $disciplineName): string {
        $directionCode = trim($directionCode);
        $disciplineName = trim($disciplineName);

        if ($directionCode === '' || $disciplineName === '') {
            return '';
        }

        $catalog = $this->getCatalog();
        if (!isset($catalog[$directionCode])) {
            return '';
        }

        $existing = $catalog[$directionCode]['disciplines'] ?? [];
        foreach ($existing as $discipline) {
            if (mb_strtolower($discipline) === mb_strtolower($disciplineName)) {
                return $discipline;
            }
        }

        $existing[] = $disciplineName;
        $existing = array_values(array_unique($existing));
        sort($existing, SORT_NATURAL | SORT_FLAG_CASE);

        $catalog[$directionCode]['disciplines'] = $existing;
        $this->persistCatalog($catalog);

        return $disciplineName;
    }

    public function getGroupDirectionMap(): array {
        $raw = $this->config->getAppValue(Application::APP_ID, self::KEY_GROUP_DIRECTION_MAP, '');
        if ($raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return $this->normalizeGroupDirectionMap($decoded);
            }
        }

        $map = $this->getDefaultGroupDirectionMap();
        $this->persistGroupDirectionMap($map);

        return $map;
    }

    public function mapGroupToDirection(string $groupName, string $directionCode): void {
        $groupName = trim($groupName);
        $directionCode = trim($directionCode);

        if ($groupName === '' || $directionCode === '') {
            return;
        }

        $catalog = $this->getCatalog();
        if (!isset($catalog[$directionCode])) {
            return;
        }

        $map = $this->getGroupDirectionMap();
        $map[$groupName] = $directionCode;

        ksort($map, SORT_NATURAL | SORT_FLAG_CASE);
        $this->persistGroupDirectionMap($map);
    }

    public function getMappedDirectionForGroup(string $groupName): string {
        $map = $this->getGroupDirectionMap();

        return (string)($map[$groupName] ?? '');
    }

    public function resolveStudentDirectionFromGroups(array $groupIds, array $excludedGroups = []): array {
        $map = $this->getGroupDirectionMap();

        foreach ($groupIds as $groupId) {
            if (in_array($groupId, $excludedGroups, true)) {
                continue;
            }

            if (isset($map[$groupId])) {
                $directionCode = $map[$groupId];

                return [
                    'group_name' => $groupId,
                    'direction_code' => $directionCode,
                    'direction_title' => $this->getDirectionTitle($directionCode),
                ];
            }
        }

        return [
            'group_name' => '',
            'direction_code' => '',
            'direction_title' => '',
        ];
    }

    private function persistCatalog(array $catalog): void {
        $this->config->setAppValue(
            Application::APP_ID,
            self::KEY_CATALOG,
            json_encode($catalog, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        );
    }

    private function persistGroupDirectionMap(array $map): void {
        $this->config->setAppValue(
            Application::APP_ID,
            self::KEY_GROUP_DIRECTION_MAP,
            json_encode($map, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        );
    }

    private function normalizeCatalog(array $catalog): array {
        $normalized = [];

        foreach ($catalog as $code => $item) {
            if (!is_array($item)) {
                continue;
            }

            $title = trim((string)($item['title'] ?? ''));
            if ($title === '') {
                $title = (string)$code;
            }

            $disciplines = [];
            foreach (($item['disciplines'] ?? []) as $discipline) {
                $discipline = trim((string)$discipline);
                if ($discipline !== '') {
                    $disciplines[] = $discipline;
                }
            }

            $disciplines = array_values(array_unique($disciplines));
            sort($disciplines, SORT_NATURAL | SORT_FLAG_CASE);

            $normalized[(string)$code] = [
                'title' => $title,
                'disciplines' => $disciplines,
            ];
        }

        if ($normalized === []) {
            return $this->getDefaultCatalog();
        }

        ksort($normalized, SORT_NATURAL | SORT_FLAG_CASE);

        return $normalized;
    }

    private function normalizeGroupDirectionMap(array $map): array {
        $normalized = [];

        foreach ($map as $groupName => $directionCode) {
            $groupName = trim((string)$groupName);
            $directionCode = trim((string)$directionCode);

            if ($groupName !== '' && $directionCode !== '') {
                $normalized[$groupName] = $directionCode;
            }
        }

        if ($normalized === []) {
            return $this->getDefaultGroupDirectionMap();
        }

        ksort($normalized, SORT_NATURAL | SORT_FLAG_CASE);

        return $normalized;
    }

    private function getDefaultCatalog(): array {
        return [
            '01.03.02' => [
                'title' => 'Прикладная математика и информатика',
                'disciplines' => [
                    'Алгоритмы и структуры данных',
                    'Дискретная математика',
                    'Программирование на Python',
                ],
            ],
            '01.04.02' => [
                'title' => 'Прикладная математика и информатика',
                'disciplines' => [
                    'Методика преподавания ИКТ',
                    'Базы данных',
                    'Машинное обучение',
                    'Интеллектуальный анализ данных',
                    'Облачные технологии в образовании',
                ],
            ],
            '02.03.02' => [
                'title' => 'Фундаментальная информатика и информационные технологии',
                'disciplines' => [
                    'Архитектура вычислительных систем',
                    'Операционные системы',
                    'Сетевые технологии',
                ],
            ],
            '02.04.02' => [
                'title' => 'Фундаментальная информатика и информационные технологии',
                'disciplines' => [
                    'Архитектура информационных систем',
                    'Интеллектуальный анализ данных',
                    'Безопасность распределённых систем',
                ],
            ],
            '09.03.04' => [
                'title' => 'Программная инженерия',
                'disciplines' => [
                    'Объектно-ориентированное программирование',
                    'Тестирование программного обеспечения',
                    'Управление требованиями',
                ],
            ],
            '09.04.04' => [
                'title' => 'Программная инженерия',
                'disciplines' => [
                    'Проектирование программных систем',
                    'Тестирование и качество ПО',
                    'DevOps и CI/CD',
                    'Облачная архитектура приложений',
                ],
            ],
            '10.03.01' => [
                'title' => 'Информационная безопасность',
                'disciplines' => [
                    'Криптографические методы защиты',
                    'Защита веб-приложений',
                    'Сетевой мониторинг и аудит',
                ],
            ],
            '44.03.01' => [
                'title' => 'Педагогическое образование',
                'disciplines' => [
                    'Педагогический дизайн цифровых курсов',
                    'Методика преподавания информатики',
                    'Цифровые образовательные среды',
                ],
            ],
        ];
    }

    private function getDefaultGroupDirectionMap(): array {
        return [
            'ПМ63' => '01.04.02',
            'ПМ64' => '01.04.02',
            'ПИ31' => '01.03.02',
            'ПИ32' => '01.03.02',
            'ИВТ41' => '02.03.02',
            'ИВТ42' => '02.03.02',
            'ПИМ11' => '09.04.04',
            'ИБ21' => '10.03.01',
        ];
    }
}