<?php
declare(strict_types=1);

return [
    'routes' => [
        ['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
        ['name' => 'page#saveProfile', 'url' => '/profile/save', 'verb' => 'POST'],
        ['name' => 'page#saveGlobal', 'url' => '/global/save', 'verb' => 'POST'],
        ['name' => 'page#saveResource', 'url' => '/resources/save', 'verb' => 'POST'],
        ['name' => 'page#deleteResource', 'url' => '/resources/delete', 'verb' => 'POST'],
        ['name' => 'page#viewResource', 'url' => '/resources/{id}', 'verb' => 'GET'],
        ['name' => 'page#downloadResource', 'url' => '/resources/{id}/download', 'verb' => 'GET'],
    ],
];