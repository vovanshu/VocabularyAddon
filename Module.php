<?php

namespace VocabularyAddon;

if (!class_exists(\Common\TraitModule::class)) {
    require_once dirname(__DIR__) . '/Common/TraitModule.php';
}
if (!class_exists(\VocabularyAddon\Common::class)) {
    require_once __DIR__ . '/Common.php';
}

use Laminas\EventManager\SharedEventManagerInterface;
use Laminas\EventManager\Event;
use Laminas\Mvc\Controller\AbstractController;
use Laminas\View\Renderer\PhpRenderer;
use Laminas\ModuleManager\ModuleManager;
use Laminas\Mvc\MvcEvent;
// use Laminas\Permissions\Acl\Assertion\AssertionAggregate;
use Omeka\Module\AbstractModule;
// use Omeka\Entity\Job;
use Common\TraitModule;
use VocabularyAddon\Common;

class Module extends AbstractModule
{

    use TraitModule;
    use Common;

    const NAMESPACE = __NAMESPACE__;

    public function getConfigForm(PhpRenderer $renderer)
    {

        $url = $renderer->url('admin/vocabulary-settings', ['action' => 'edit']);
        return "<script>window.location.href = '$url';</script>";

    }

    public function onBootstrap(MvcEvent $event): void
    {

        parent::onBootstrap($event);
        $this->addDefAclRules();

    }


    /**
     * Add ACL rules for this module.
     */

     protected function addDefAclRules()
     {

        $acl = $this->getServiceLocator()->get('Omeka\Acl');

        $acl
            ->allow(
                [
                    \Omeka\Permissions\Acl::ROLE_GLOBAL_ADMIN,
                    \Omeka\Permissions\Acl::ROLE_SITE_ADMIN
                ],
                [
                    'Omeka\Controller\Admin\Vocabulary',
                    'Omeka\Controller\Admin\Property',
                    'Omeka\Controller\Admin\ResourceClass',
                ],
                [
                    'browse', 'show-details', 'properties', 'classes', 'add', 'edit', 'delete', 'delete-confirm'
                ]
            );
        $acl
            ->allow(
                [
                    \Omeka\Permissions\Acl::ROLE_GLOBAL_ADMIN,
                    \Omeka\Permissions\Acl::ROLE_SITE_ADMIN
                ],
                [
                    Controller\Admin\SettingsController::class
                ],
                [
                    'edit', 'info-about', 'details', 'delete-confirm', 'delete', 'backups', 'backuping', 'restore-confirm', 'restore'
                ]
            );

    }

}
