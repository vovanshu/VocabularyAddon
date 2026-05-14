<?php declare(strict_types=1);

namespace VocabularyAddon\Mvc\Controller\Plugin;

use Laminas\Mvc\Controller\Plugin\AbstractPlugin;
use VocabularyAddon\TraitGeneral;

class GeneralPlugin extends AbstractPlugin
{

    use TraitGeneral;

    public function __construct($serviceLocator, $requestedName = Null, ?array $options = null)
    {
        $this->setServiceLocator($serviceLocator);
    }

    public function __invoke()
    {
        return $this;
    }

}
