<?php
declare(strict_types=1);

namespace OCA\EduAdaptiveAccess\Controller;

use OCA\EduAdaptiveAccess\AppInfo\Application;
use OCA\EduAdaptiveAccess\Service\AdaptiveAccessService;
use OCA\EduAdaptiveAccess\Service\PolicyConfigService;
use OCA\EduAdaptiveAccess\Service\ResourceRegistryService;
use OCA\EduAdaptiveAccess\Service\UserAttributeService;
use OCA\EduAdaptiveAccess\Service\UserFileBrowserService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataDownloadResponse;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\DataDisplayResponse;
use OCP\AppFramework\Http\ContentSecurityPolicy;
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

        $resources = [];
        foreach ($this->resourceRegistryService->getAll() as $resource) {
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

        return new TemplateResponse(Application::APP_ID, 'index', [
            'profile' => $profile,
            'policy' => $policy,
            'resources' => $resources,
			'selectable_files' => $selectableFiles,
            'is_admin' => $profile['role'] === 'admin',
            'index_url' => $this->urlGenerator->linkToRoute(Application::APP_ID . '.page.index'),
            'save_profile_url' => $this->urlGenerator->linkToRoute(Application::APP_ID . '.page.saveProfile'),
            'save_global_url' => $this->urlGenerator->linkToRoute(Application::APP_ID . '.page.saveGlobal'),
            'save_resource_url' => $this->urlGenerator->linkToRoute(Application::APP_ID . '.page.saveResource'),
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
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function saveResource(): RedirectResponse {
        $user = $this->requireUser();

        $params = $this->request->getParams();
        $path = trim((string)($params['owner_path'] ?? ''));

        if ($path !== '') {
            try {
                $userFolder = $this->rootFolder->getUserFolder($user->getUID());
                $userFolder->get($path[0] === '/' ? substr($path, 1) : $path);
            } catch (\Throwable $e) {
                // Для MVP не валим процесс, но лучше сразу исправить путь.
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
	
	/*public function viewResource(string $id): TemplateResponse {
		Util::addStyle(Application::APP_ID, 'style');

		$user = $this->requireUser();
		$resource = $this->resourceRegistryService->getById($id);

		if ($resource === null) {
			return new TemplateResponse(Application::APP_ID, 'resource', [
				'resource' => null,
				'message' => 'Ресурс не найден',
				'index_url' => $this->urlGenerator->linkToRoute(Application::APP_ID . '.page.index'),
				'supports_browser_read' => false,
				'read_url' => '',
				'download_url' => '',
			]);
		}

		return new TemplateResponse(Application::APP_ID, 'resource', [
			'resource' => $resource,
			'read_eval' => $this->adaptiveAccessService->evaluate($resource, $user, 'read'),
			'download_eval' => $this->adaptiveAccessService->evaluate($resource, $user, 'download'),
			'supports_browser_read' => $this->supportsBrowserRead($resource),
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
	}*/

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
            ]);
        }

        $downloadEval = $this->adaptiveAccessService->evaluate($resource, $user, 'download');

        if ($downloadEval['decision'] !== 'ALLOW') {
            Util::addStyle(Application::APP_ID, 'style');

            return new TemplateResponse(Application::APP_ID, 'resource', [
                'resource' => $resource,
                'read_eval' => $this->adaptiveAccessService->evaluate($resource, $user, 'read'),
                'download_eval' => $downloadEval,
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
                'download_url' => $this->urlGenerator->linkToRoute(
                    Application::APP_ID . '.page.downloadResource',
                    ['id' => $resource['id']]
                ),
                'index_url' => $this->urlGenerator->linkToRoute(Application::APP_ID . '.page.index'),
                'message' => 'Файл по указанному пути не найден',
            ]);
        }
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
	
	
	/*public function readResource(string $id) {
		$user = $this->requireUser();
		$resource = $this->resourceRegistryService->getById($id);

		if ($resource === null) {
			Util::addStyle(Application::APP_ID, 'style');

			return new TemplateResponse(Application::APP_ID, 'resource', [
				'resource' => null,
				'message' => 'Ресурс не найден',
				'index_url' => $this->urlGenerator->linkToRoute(Application::APP_ID . '.page.index'),
				'supports_browser_read' => false,
				'read_url' => '',
				'download_url' => '',
			]);
		}

		$readEval = $this->adaptiveAccessService->evaluate($resource, $user, 'read');

		if ($readEval['decision'] !== 'ALLOW') {
			Util::addStyle(Application::APP_ID, 'style');

			return new TemplateResponse(Application::APP_ID, 'resource', [
				'resource' => $resource,
				'read_eval' => $readEval,
				'download_eval' => $this->adaptiveAccessService->evaluate($resource, $user, 'download'),
				'supports_browser_read' => $this->supportsBrowserRead($resource),
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
			Util::addStyle(Application::APP_ID, 'style');

			return new TemplateResponse(Application::APP_ID, 'resource', [
				'resource' => $resource,
				'read_eval' => $readEval,
				'download_eval' => $this->adaptiveAccessService->evaluate($resource, $user, 'download'),
				'supports_browser_read' => false,
				'read_url' => '',
				'download_url' => $this->urlGenerator->linkToRoute(
					Application::APP_ID . '.page.downloadResource',
					['id' => $resource['id']]
				),
				'index_url' => $this->urlGenerator->linkToRoute(Application::APP_ID . '.page.index'),
				'message' => 'Для текущего MVP чтение в браузере поддержано только для PDF.',
			]);
		}

		try {
			$userFolder = $this->rootFolder->getUserFolder((string)$resource['owner_uid']);
			$relativePath = ltrim((string)$resource['owner_path'], '/');
			$node = $userFolder->get($relativePath);

			if (!$node instanceof File) {
				Util::addStyle(Application::APP_ID, 'style');

				return new TemplateResponse(Application::APP_ID, 'resource', [
					'resource' => $resource,
					'read_eval' => $readEval,
					'download_eval' => $this->adaptiveAccessService->evaluate($resource, $user, 'download'),
					'supports_browser_read' => $this->supportsBrowserRead($resource),
					'read_url' => '',
					'download_url' => $this->urlGenerator->linkToRoute(
						Application::APP_ID . '.page.downloadResource',
						['id' => $resource['id']]
					),
					'index_url' => $this->urlGenerator->linkToRoute(Application::APP_ID . '.page.index'),
					'message' => 'Указанный путь не ведёт к файлу',
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
				]
			);

			return $response;
		} catch (NotFoundException $e) {
			Util::addStyle(Application::APP_ID, 'style');

			return new TemplateResponse(Application::APP_ID, 'resource', [
				'resource' => $resource,
				'read_eval' => $readEval,
				'download_eval' => $this->adaptiveAccessService->evaluate($resource, $user, 'download'),
				'supports_browser_read' => $this->supportsBrowserRead($resource),
				'read_url' => '',
				'download_url' => $this->urlGenerator->linkToRoute(
					Application::APP_ID . '.page.downloadResource',
					['id' => $resource['id']]
				),
				'index_url' => $this->urlGenerator->linkToRoute(Application::APP_ID . '.page.index'),
				'message' => 'Файл по указанному пути не найден',
			]);
		}
	}*/

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
	
	/*private function supportsBrowserRead(array $resource): bool {
		$path = (string)($resource['owner_path'] ?? '');
		$extension = strtolower((string)pathinfo($path, PATHINFO_EXTENSION));

		return $extension === 'pdf';
	}*/
	
	private function supportsBrowserRead(array $resource): bool {
		return $this->isPdfResource($resource);
	}

	private function isPdfResource(array $resource): bool {
		$path = (string)($resource['owner_path'] ?? '');
		$extension = strtolower((string)pathinfo($path, PATHINFO_EXTENSION));

		return $extension === 'pdf';
	}
}