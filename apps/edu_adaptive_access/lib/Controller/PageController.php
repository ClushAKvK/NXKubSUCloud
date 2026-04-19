<?php
declare(strict_types=1);

namespace OCA\EduAdaptiveAccess\Controller;

use OCA\EduAdaptiveAccess\AppInfo\Application;
use OCA\EduAdaptiveAccess\Service\AcademicCatalogService;
use OCA\EduAdaptiveAccess\Service\AdaptiveAccessService;
use OCA\EduAdaptiveAccess\Service\PolicyConfigService;
use OCA\EduAdaptiveAccess\Service\ResourceRegistryService;
use OCA\EduAdaptiveAccess\Service\UserAttributeService;
use OCA\EduAdaptiveAccess\Service\UserFileBrowserService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Http\DataDisplayResponse;
use OCP\AppFramework\Http\DataDownloadResponse;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserSession;
use OCP\Util;

class PageController extends Controller {
    public function __construct(
        string $appName,
        IRequest $request,
        private IUserSession $userSession,
        private IURLGenerator $urlGenerator,
        private PolicyConfigService $policyConfigService,
        private UserAttributeService $userAttributeService,
        private ResourceRegistryService $resourceRegistryService,
        private AdaptiveAccessService $adaptiveAccessService,
        private IRootFolder $rootFolder,
        private UserFileBrowserService $userFileBrowserService,
        private AcademicCatalogService $academicCatalogService,
    ) {
        parent::__construct($appName, $request);
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function index(): TemplateResponse {
        Util::addStyle(Application::APP_ID, 'style');
        Util::addScript(Application::APP_ID, 'index');

        $user = $this->requireUser();
        $profile = $this->userAttributeService->getUserProfile($user);
        $policy = $this->policyConfigService->getGlobalPolicy();
        $selectableFiles = $this->userFileBrowserService->listSelectableFiles($user);
        $directionOptions = $this->academicCatalogService->getDirectionOptions();
        $disciplineOptions = $this->academicCatalogService->getDisciplinesForDirection($profile['direction_code'] ?? '');
        $groupDirectionMap = $this->academicCatalogService->getGroupDirectionMap();

        $resources = [];
        foreach ($this->resourceRegistryService->getAll() as $resource) {
            if (!$this->shouldShowResourceInCatalog($resource, $profile)) {
                continue;
            }

            $resource['read_eval'] = $this->adaptiveAccessService->evaluate($resource, $user, 'read');
            $resource['download_eval'] = $this->adaptiveAccessService->evaluate($resource, $user, 'download');
            $resource['supports_browser_read'] = $this->supportsBrowserRead($resource);
            $resource['is_pdf'] = $this->isPdfResource($resource);

            $resource['view_url'] = $this->urlGenerator->linkToRoute(
                Application::APP_ID . '.page.viewResource',
                ['id' => $resource['id']]
            );

            $resource['read_url'] = $this->urlGenerator->linkToRoute(
                Application::APP_ID . '.page.readResource',
                ['id' => $resource['id']]
            );

            $resource['download_url'] = $this->urlGenerator->linkToRoute(
                Application::APP_ID . '.page.downloadResource',
                ['id' => $resource['id']]
            );

            $resources[] = $resource;
        }
		
		$directionTitleMap = [];
		foreach ($directionOptions as $direction) {
			$directionTitleMap[$direction['code']] = $direction['title'];
		}

        return new TemplateResponse(Application::APP_ID, 'index', [
            'profile' => $profile,
            'policy' => $policy,
            'resources' => $resources,
            'selectable_files' => $selectableFiles,
            'direction_options' => $directionOptions,
            'discipline_options' => $disciplineOptions,
			'direction_title_map' => $directionTitleMap,
            'academic_catalog_json' => json_encode($this->academicCatalogService->getCatalog(), JSON_UNESCAPED_UNICODE),
            'group_direction_map' => $groupDirectionMap,
            'is_admin' => $profile['role'] === 'admin',
            'can_register_resource' => in_array($profile['role'], ['teacher', 'admin'], true)
                && ($profile['direction_code'] ?? '') !== ''
                && ($profile['discipline_name'] ?? '') !== '',
            'index_url' => $this->urlGenerator->linkToRoute(Application::APP_ID . '.page.index'),
            'save_profile_url' => $this->urlGenerator->linkToRoute(Application::APP_ID . '.page.saveProfile'),
            'save_global_url' => $this->urlGenerator->linkToRoute(Application::APP_ID . '.page.saveGlobal'),
            'save_resource_url' => $this->urlGenerator->linkToRoute(Application::APP_ID . '.page.saveResource'),
            'save_academic_url' => $this->urlGenerator->linkToRoute(Application::APP_ID . '.page.saveAcademic'),
            'delete_resource_url' => $this->urlGenerator->linkToRoute(Application::APP_ID . '.page.deleteResource'),
        ]);
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function saveProfile(): RedirectResponse {
        $user = $this->requireUser();
        $this->userAttributeService->saveUserProfile($user, $this->request->getParams());

        return $this->redirectToIndex();
    }

    /**
     * @AdminRequired
     * @NoCSRFRequired
     */
    public function saveGlobal(): RedirectResponse {
        $this->policyConfigService->saveGlobalPolicy($this->request->getParams());

        return $this->redirectToIndex();
    }

    /**
     * @AdminRequired
     * @NoCSRFRequired
     */
    public function saveAcademic(): RedirectResponse {
        $params = $this->request->getParams();

        $newDirectionCode = trim((string)($params['new_direction_code'] ?? ''));
        $newDirectionTitle = trim((string)($params['new_direction_title'] ?? ''));
        $disciplineDirectionCode = trim((string)($params['discipline_direction_code'] ?? ''));
        $newDisciplineName = trim((string)($params['new_discipline_name'] ?? ''));
        $mapGroupName = trim((string)($params['map_group_name'] ?? ''));
        $mapDirectionCode = trim((string)($params['map_direction_code'] ?? ''));

        if ($newDirectionCode !== '' && $newDirectionTitle !== '') {
            $this->academicCatalogService->addDirection($newDirectionCode, $newDirectionTitle);
        }

        if ($disciplineDirectionCode !== '' && $newDisciplineName !== '') {
            $this->academicCatalogService->addDiscipline($disciplineDirectionCode, $newDisciplineName);
        }

        if ($mapGroupName !== '' && $mapDirectionCode !== '') {
            $this->academicCatalogService->mapGroupToDirection($mapGroupName, $mapDirectionCode);
        }

        return $this->redirectToIndex();
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function saveResource(): RedirectResponse {
        $user = $this->requireUser();
        $profile = $this->userAttributeService->getUserProfile($user);

        if (!in_array($profile['role'], ['teacher', 'admin'], true)) {
            return $this->redirectToIndex();
        }

        if (($profile['direction_code'] ?? '') === '' || ($profile['discipline_name'] ?? '') === '') {
            return $this->redirectToIndex();
        }

        $params = $this->request->getParams();
        $params['direction_code'] = $profile['direction_code'];
        $params['direction_title'] = $profile['direction_title'];
        $params['discipline_name'] = $profile['discipline_name'];

        $path = trim((string)($params['owner_path'] ?? ''));

        if ($path !== '') {
            try {
                $userFolder = $this->rootFolder->getUserFolder($user->getUID());
                $userFolder->get($path[0] === '/' ? substr($path, 1) : $path);
            } catch (\Throwable $e) {
                // MVP: не валим процесс жёстко
            }
        }

        $this->resourceRegistryService->save($params, $user->getUID());

        return $this->redirectToIndex();
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function deleteResource(): RedirectResponse {
        $user = $this->requireUser();
        $resourceId = (string)$this->request->getParam('id', '');
        $resource = $this->resourceRegistryService->getById($resourceId);

        if ($resource !== null) {
            $profile = $this->userAttributeService->getUserProfile($user);
            $isOwner = ($resource['owner_uid'] ?? '') === $user->getUID();

            if ($isOwner || $profile['role'] === 'admin') {
                $this->resourceRegistryService->delete($resourceId);
            }
        }

        return $this->redirectToIndex();
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function viewResource(string $id): TemplateResponse {
        Util::addStyle(Application::APP_ID, 'style');

        $user = $this->requireUser();
        $resource = $this->resourceRegistryService->getById($id);

        if ($resource === null) {
            return new TemplateResponse(Application::APP_ID, 'resource', [
                'resource' => null,
                'message' => 'Ресурс не найден',
                'index_url' => $this->urlGenerator->linkToRoute(Application::APP_ID . '.page.index'),
                'supports_browser_read' => false,
                'is_pdf' => false,
                'read_url' => '',
                'download_url' => '',
            ]);
        }

        return new TemplateResponse(Application::APP_ID, 'resource', [
            'resource' => $resource,
            'read_eval' => $this->adaptiveAccessService->evaluate($resource, $user, 'read'),
            'download_eval' => $this->adaptiveAccessService->evaluate($resource, $user, 'download'),
            'supports_browser_read' => $this->supportsBrowserRead($resource),
            'is_pdf' => $this->isPdfResource($resource),
            'read_url' => $this->urlGenerator->linkToRoute(
                Application::APP_ID . '.page.readResource',
                ['id' => $resource['id']]
            ),
            'download_url' => $this->urlGenerator->linkToRoute(
                Application::APP_ID . '.page.downloadResource',
                ['id' => $resource['id']]
            ),
            'index_url' => $this->urlGenerator->linkToRoute(Application::APP_ID . '.page.index'),
            'message' => '',
        ]);
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function readResource(string $id): TemplateResponse {
        Util::addStyle(Application::APP_ID, 'style');

        $user = $this->requireUser();
        $resource = $this->resourceRegistryService->getById($id);

        if ($resource === null) {
            return new TemplateResponse(Application::APP_ID, 'resource', [
                'resource' => null,
                'message' => 'Ресурс не найден',
                'index_url' => $this->urlGenerator->linkToRoute(Application::APP_ID . '.page.index'),
                'supports_browser_read' => false,
                'is_pdf' => false,
                'read_url' => '',
                'download_url' => '',
            ]);
        }

        $readEval = $this->adaptiveAccessService->evaluate($resource, $user, 'read');

        if ($readEval['decision'] !== 'ALLOW') {
            return new TemplateResponse(Application::APP_ID, 'resource', [
                'resource' => $resource,
                'read_eval' => $readEval,
                'download_eval' => $this->adaptiveAccessService->evaluate($resource, $user, 'download'),
                'supports_browser_read' => $this->supportsBrowserRead($resource),
                'is_pdf' => $this->isPdfResource($resource),
                'read_url' => $this->urlGenerator->linkToRoute(
                    Application::APP_ID . '.page.readResource',
                    ['id' => $resource['id']]
                ),
                'download_url' => $this->urlGenerator->linkToRoute(
                    Application::APP_ID . '.page.downloadResource',
                    ['id' => $resource['id']]
                ),
                'index_url' => $this->urlGenerator->linkToRoute(Application::APP_ID . '.page.index'),
                'message' => $readEval['decision'] === 'STEP_UP'
                    ? 'Чтение в браузере переведено в STEP_UP и для MVP заблокировано'
                    : 'Чтение в браузере запрещено',
            ]);
        }

        if (!$this->supportsBrowserRead($resource)) {
            return new TemplateResponse(Application::APP_ID, 'resource', [
                'resource' => $resource,
                'read_eval' => $readEval,
                'download_eval' => $this->adaptiveAccessService->evaluate($resource, $user, 'download'),
                'supports_browser_read' => false,
                'is_pdf' => $this->isPdfResource($resource),
                'read_url' => '',
                'download_url' => $this->urlGenerator->linkToRoute(
                    Application::APP_ID . '.page.downloadResource',
                    ['id' => $resource['id']]
                ),
                'index_url' => $this->urlGenerator->linkToRoute(Application::APP_ID . '.page.index'),
                'message' => 'Для текущего MVP чтение в браузере поддержано только для PDF.',
            ]);
        }

        $streamUrl = $this->urlGenerator->linkToRoute(
            Application::APP_ID . '.page.streamResource',
            ['id' => $resource['id']]
        ) . '#toolbar=0&navpanes=0&scrollbar=1&view=FitH';

        $response = new TemplateResponse(Application::APP_ID, 'pdfviewer', [
            'resource' => $resource,
            'stream_url' => $streamUrl,
            'index_url' => $this->urlGenerator->linkToRoute(Application::APP_ID . '.page.index'),
            'card_url' => $this->urlGenerator->linkToRoute(
                Application::APP_ID . '.page.viewResource',
                ['id' => $resource['id']]
            ),
            'message' => 'PDF открыт через демонстрационную обёртку просмотра.',
        ]);

        $csp = new ContentSecurityPolicy();
        $csp->addAllowedFrameDomain('\'self\'');
        $response->setContentSecurityPolicy($csp);

        return $response;
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function streamResource(string $id) {
        $user = $this->requireUser();
        $resource = $this->resourceRegistryService->getById($id);

        if ($resource === null || !$this->supportsBrowserRead($resource)) {
            return new TemplateResponse(Application::APP_ID, 'resource', [
                'resource' => $resource,
                'message' => 'Ресурс не найден или не поддерживает browser-view',
                'index_url' => $this->urlGenerator->linkToRoute(Application::APP_ID . '.page.index'),
                'supports_browser_read' => false,
                'is_pdf' => false,
                'read_url' => '',
                'download_url' => '',
            ]);
        }

        $readEval = $this->adaptiveAccessService->evaluate($resource, $user, 'read');

        if ($readEval['decision'] !== 'ALLOW') {
            return new TemplateResponse(Application::APP_ID, 'resource', [
                'resource' => $resource,
                'read_eval' => $readEval,
                'download_eval' => $this->adaptiveAccessService->evaluate($resource, $user, 'download'),
                'supports_browser_read' => true,
                'is_pdf' => true,
                'read_url' => '',
                'download_url' => '',
                'index_url' => $this->urlGenerator->linkToRoute(Application::APP_ID . '.page.index'),
                'message' => 'Чтение PDF запрещено политикой доступа',
            ]);
        }

        try {
            $userFolder = $this->rootFolder->getUserFolder((string)$resource['owner_uid']);
            $relativePath = ltrim((string)$resource['owner_path'], '/');
            $node = $userFolder->get($relativePath);

            if (!$node instanceof File) {
                return new TemplateResponse(Application::APP_ID, 'resource', [
                    'resource' => $resource,
                    'message' => 'Указанный путь не ведёт к файлу',
                    'index_url' => $this->urlGenerator->linkToRoute(Application::APP_ID . '.page.index'),
                    'supports_browser_read' => true,
                    'is_pdf' => true,
                    'read_url' => '',
                    'download_url' => '',
                ]);
            }

            $mimeType = $node->getMimeType();
            $safeFilename = str_replace('"', '', $node->getName());

            $response = new DataDisplayResponse(
                $node->getContent(),
                200,
                [
                    'Content-Type' => $mimeType,
                    'Content-Disposition' => 'inline; filename="' . $safeFilename . '"',
                    'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
                    'Pragma' => 'no-cache',
                ]
            );

            $csp = new ContentSecurityPolicy();
            $csp->addAllowedFrameAncestorDomain('\'self\'');
            $response->setContentSecurityPolicy($csp);

            return $response;
        } catch (NotFoundException $e) {
            return new TemplateResponse(Application::APP_ID, 'resource', [
                'resource' => $resource,
                'message' => 'Файл по указанному пути не найден',
                'index_url' => $this->urlGenerator->linkToRoute(Application::APP_ID . '.page.index'),
                'supports_browser_read' => true,
                'is_pdf' => true,
                'read_url' => '',
                'download_url' => '',
            ]);
        }
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function downloadResource(string $id) {
        $user = $this->requireUser();
        $resource = $this->resourceRegistryService->getById($id);

        if ($resource === null) {
            return new TemplateResponse(Application::APP_ID, 'resource', [
                'resource' => null,
                'message' => 'Ресурс не найден',
                'index_url' => $this->urlGenerator->linkToRoute(Application::APP_ID . '.page.index'),
                'supports_browser_read' => false,
                'is_pdf' => false,
                'read_url' => '',
                'download_url' => '',
            ]);
        }

        $downloadEval = $this->adaptiveAccessService->evaluate($resource, $user, 'download');

        if ($downloadEval['decision'] !== 'ALLOW') {
            Util::addStyle(Application::APP_ID, 'style');

            return new TemplateResponse(Application::APP_ID, 'resource', [
                'resource' => $resource,
                'read_eval' => $this->adaptiveAccessService->evaluate($resource, $user, 'read'),
                'download_eval' => $downloadEval,
                'supports_browser_read' => $this->supportsBrowserRead($resource),
                'is_pdf' => $this->isPdfResource($resource),
                'read_url' => $this->urlGenerator->linkToRoute(
                    Application::APP_ID . '.page.readResource',
                    ['id' => $resource['id']]
                ),
                'download_url' => $this->urlGenerator->linkToRoute(
                    Application::APP_ID . '.page.downloadResource',
                    ['id' => $resource['id']]
                ),
                'index_url' => $this->urlGenerator->linkToRoute(Application::APP_ID . '.page.index'),
                'message' => $downloadEval['decision'] === 'STEP_UP'
                    ? 'Скачивание переведено в STEP_UP и для MVP заблокировано'
                    : 'Скачивание запрещено',
            ]);
        }

        try {
            $userFolder = $this->rootFolder->getUserFolder((string)$resource['owner_uid']);
            $relativePath = ltrim((string)$resource['owner_path'], '/');
            $node = $userFolder->get($relativePath);

            if (!$node instanceof File) {
                return new TemplateResponse(Application::APP_ID, 'resource', [
                    'resource' => $resource,
                    'read_eval' => $this->adaptiveAccessService->evaluate($resource, $user, 'read'),
                    'download_eval' => $downloadEval,
                    'supports_browser_read' => $this->supportsBrowserRead($resource),
                    'is_pdf' => $this->isPdfResource($resource),
                    'read_url' => $this->urlGenerator->linkToRoute(
                        Application::APP_ID . '.page.readResource',
                        ['id' => $resource['id']]
                    ),
                    'download_url' => $this->urlGenerator->linkToRoute(
                        Application::APP_ID . '.page.downloadResource',
                        ['id' => $resource['id']]
                    ),
                    'index_url' => $this->urlGenerator->linkToRoute(Application::APP_ID . '.page.index'),
                    'message' => 'Указанный путь не ведёт к файлу',
                ]);
            }

            return new DataDownloadResponse(
                $node->getContent(),
                $node->getName(),
                $node->getMimeType()
            );
        } catch (NotFoundException $e) {
            return new TemplateResponse(Application::APP_ID, 'resource', [
                'resource' => $resource,
                'read_eval' => $this->adaptiveAccessService->evaluate($resource, $user, 'read'),
                'download_eval' => $downloadEval,
                'supports_browser_read' => $this->supportsBrowserRead($resource),
                'is_pdf' => $this->isPdfResource($resource),
                'read_url' => $this->urlGenerator->linkToRoute(
                    Application::APP_ID . '.page.readResource',
                    ['id' => $resource['id']]
                ),
                'download_url' => $this->urlGenerator->linkToRoute(
                    Application::APP_ID . '.page.downloadResource',
                    ['id' => $resource['id']]
                ),
                'index_url' => $this->urlGenerator->linkToRoute(Application::APP_ID . '.page.index'),
                'message' => 'Файл по указанному пути не найден',
            ]);
        }
    }

    private function requireUser() {
        $user = $this->userSession->getUser();
        if ($user === null) {
            throw new \RuntimeException('User must be logged in');
        }

        return $user;
    }

    private function redirectToIndex(): RedirectResponse {
        return new RedirectResponse(
            $this->urlGenerator->linkToRoute(Application::APP_ID . '.page.index')
        );
    }

    private function supportsBrowserRead(array $resource): bool {
        return $this->isPdfResource($resource);
    }

    private function isPdfResource(array $resource): bool {
        $path = (string)($resource['owner_path'] ?? '');
        $extension = strtolower((string)pathinfo($path, PATHINFO_EXTENSION));

        return $extension === 'pdf';
    }

    private function shouldShowResourceInCatalog(array $resource, array $profile): bool {
        $profileDirection = trim((string)($profile['direction_code'] ?? ''));
        $profileDiscipline = trim((string)($profile['discipline_name'] ?? ''));

        $resourceDirection = trim((string)($resource['direction_code'] ?? ''));
        $resourceDiscipline = trim((string)($resource['discipline_name'] ?? ''));

        if ($profileDiscipline !== '') {
            return $profileDirection !== ''
                && $resourceDirection === $profileDirection
                && $resourceDiscipline === $profileDiscipline;
        }

        if ($profileDirection !== '') {
            return $resourceDirection === $profileDirection;
        }

        return true;
    }
}