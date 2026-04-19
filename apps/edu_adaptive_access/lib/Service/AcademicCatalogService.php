<?php
declare(strict_types=1);

namespace OCA\EduAdaptiveAccess\Service;

use OCA\EduAdaptiveAccess\AppInfo\Application;
use OCP\IConfig;

class AcademicCatalogService {
    private const KEY_CATALOG = 'academic_catalog';

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
        $this->persist($catalog);

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

    public function addDiscipline(string $directionCode, string $disciplineName): string {
        $disciplineName = trim($disciplineName);
        if ($disciplineName === '') {
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
        sort($existing, SORT_NATURAL | SORT_FLAG_CASE);

        $catalog[$directionCode]['disciplines'] = array_values($existing);
        $this->persist($catalog);

        return $disciplineName;
    }

    private function persist(array $catalog): void {
        $this->config->setAppValue(
            Application::APP_ID,
            self::KEY_CATALOG,
            json_encode($catalog, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
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

    private function getDefaultCatalog(): array {
        return [
            '01.04.02' => [
                'title' => 'Прикладная математика и информатика',
                'disciplines' => [
                    'Методика преподавания ИКТ',
                    'Базы данных',
                    'Машинное обучение',
                ],
            ],
            '02.04.02' => [
                'title' => 'Фундаментальная информатика и информационные технологии',
                'disciplines' => [
                    'Архитектура информационных систем',
                    'Интеллектуальный анализ данных',
                ],
            ],
            '09.04.04' => [
                'title' => 'Программная инженерия',
                'disciplines' => [
                    'Проектирование программных систем',
                    'Тестирование и качество ПО',
                ],
            ],
        ];
    }
}