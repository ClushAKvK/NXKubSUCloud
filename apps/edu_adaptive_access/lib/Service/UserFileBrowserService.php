<?php
declare(strict_types=1);

namespace OCA\EduAdaptiveAccess\Service;

use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IUser;

class UserFileBrowserService {
    private const MAX_FILES = 500;

    public function __construct(
        private IRootFolder $rootFolder,
    ) {
    }

    public function listSelectableFiles(IUser $user): array {
        $userFolder = $this->rootFolder->getUserFolder($user->getUID());
        $result = [];

        $this->collectFiles($userFolder, '', $result);

        usort($result, static function (array $a, array $b): int {
            return strcmp($a['path'], $b['path']);
        });

        return $result;
    }

    private function collectFiles(Folder $folder, string $relativePrefix, array &$result): void {
        if (count($result) >= self::MAX_FILES) {
            return;
        }

        foreach ($folder->getDirectoryListing() as $node) {
            if (count($result) >= self::MAX_FILES) {
                return;
            }

            $name = $node->getName();
            $relativePath = $relativePrefix . '/' . $name;

            if ($node instanceof Folder) {
                if ($this->shouldSkipFolder($name)) {
                    continue;
                }

                $this->collectFiles($node, $relativePath, $result);
                continue;
            }

            if ($node instanceof File) {
                $extension = strtolower((string)pathinfo($name, PATHINFO_EXTENSION));

                $result[] = [
                    'path' => $relativePath,
                    'name' => $name,
                    'extension' => $extension,
                    'mime' => $node->getMimeType(),
                    'size' => $node->getSize(),
                    'mtime' => $node->getMTime(),
                    'browser_readable' => $extension === 'pdf',
                ];
            }
        }
    }

    private function shouldSkipFolder(string $name): bool {
        $name = strtolower($name);

        return in_array($name, [
            'files_trashbin',
            'files_versions',
            'appdata_',
            '.trash',
            '.versions',
        ], true);
    }
}