<?php declare(strict_types=1);

namespace VocabularyAddon\Mvc\Controller\Plugin;

use Laminas\Mvc\Controller\Plugin\AbstractPlugin;
use VocabularyAddon\Common;

class CommonPlugin extends AbstractPlugin
{

    use Common;

    public function __construct($serviceLocator, $requestedName = Null, array $options = null)
    {
        $this->setServiceLocator($serviceLocator);
    }

    public function __invoke()
    {
        return $this;
    }

}
