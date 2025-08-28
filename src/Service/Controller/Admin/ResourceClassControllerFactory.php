<?php
namespace VocabularyAddon\Service\Controller\Admin;

use Interop\Container\ContainerInterface;
use VocabularyAddon\Controller\Admin\ResourceClassController;
use Laminas\ServiceManager\Factory\FactoryInterface;

class ResourceClassControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new ResourceClassController($services, $requestedName, $options);
    }
}
