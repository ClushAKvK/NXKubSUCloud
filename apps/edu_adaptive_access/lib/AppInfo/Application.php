<?php
declare(strict_types=1);

namespace OCA\EduAdaptiveAccess\AppInfo;

use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;

class Application extends App implements IBootstrap {
    public const APP_ID = 'edu_adaptive_access';

    public function __construct(array $urlParams = []) {
        parent::__construct(self::APP_ID, $urlParams);
    }

    public function register(IRegistrationContext $context): void {
        // Для MVP ничего регистрировать дополнительно не нужно.
    }

    public function boot(IBootContext $context): void {
        // Для MVP ничего не делаем.
    }
}