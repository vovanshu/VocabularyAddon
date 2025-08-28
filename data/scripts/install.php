<?php

$eventManager = $this->getServiceLocator()->get('EventManager');
$args = $eventManager->prepareArgs(['module' => static::NAMESPACE, 'config' => $this->getConfig()]);
$eventManager->trigger('module.install', null, $args);
