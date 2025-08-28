<?php
namespace VocabularyAddon\Service\Controller\Admin;

use Interop\Container\ContainerInterface;
use VocabularyAddon\Controller\Admin\PropertyController;
use Laminas\ServiceManager\Factory\FactoryInterface;

class PropertyControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new PropertyController($services, $requestedName, $options);
    }
}
