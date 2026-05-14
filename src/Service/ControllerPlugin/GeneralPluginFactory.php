<?php declare(strict_types=1);

namespace VocabularyAddon\Service\ControllerPlugin;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use VocabularyAddon\Mvc\Controller\Plugin\GeneralPlugin;

class GeneralPluginFactory implements FactoryInterface
{

    public function __invoke(ContainerInterface $serviceLocator, $requestedName, ?array $options = null)
    {
        return new GeneralPlugin($serviceLocator, $requestedName, $options);
    }
}
