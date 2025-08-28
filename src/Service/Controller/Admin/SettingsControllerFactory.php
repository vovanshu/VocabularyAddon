<?php
namespace VocabularyAddon\Service\Controller\Admin;

use Laminas\ServiceManager\Factory\FactoryInterface;
use VocabularyAddon\Controller\Admin\SettingsController;

class SettingsControllerFactory implements FactoryInterface
{
    public function __invoke($services, $requestedName, array $options = null)
    {
        return new SettingsController($services, $requestedName, $options);
    }
}
