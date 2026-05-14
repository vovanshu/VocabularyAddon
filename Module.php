<?php

namespace VocabularyAddon;

require_once __DIR__ . '/src/TraitGeneral.php';
require_once __DIR__ . '/src/TraitModule.php';

use Laminas\EventManager\SharedEventManagerInterface;
use Laminas\EventManager\Event;
use Laminas\Mvc\Controller\AbstractController;
use Laminas\View\Renderer\PhpRenderer;
use Laminas\ModuleManager\ModuleManager;
use Laminas\Mvc\MvcEvent;
// use Laminas\Permissions\Acl\Assertion\AssertionAggregate;
use Omeka\Module\AbstractModule;
// use Omeka\Entity\Job;
use VocabularyAddon\TraitGeneral;
use VocabularyAddon\TraitModule;

class Module extends AbstractModule
{

    use TraitGeneral;
    use TraitModule;

    public function getConfigForm(PhpRenderer $renderer)
    {

        return $this->redirecToURL($renderer->url('admin/vocabulary-settings', ['action' => 'edit']));

    }

    public function onBootstrap(MvcEvent $event): void
    {

        parent::onBootstrap($event);
        $this->setMvcEvent($event);
        $this->addDefAclRules();

    }


    /**
     * Add ACL rules for this module.
     */

     protected function addDefAclRules()
     {

        $this->getAcl()
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
        $this->getAcl()
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
