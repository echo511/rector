<?php

declare (strict_types=1);
namespace RectorPrefix20210516;

use Rector\NetteToSymfony\Rector\MethodCall\NetteFormToSymfonyFormRector;
use RectorPrefix20210516\Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
return static function (\RectorPrefix20210516\Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator $containerConfigurator) : void {
    $containerConfigurator->import(__DIR__ . '/../../../../../config/config.php');
    $services = $containerConfigurator->services();
    $services->set(\Rector\NetteToSymfony\Rector\MethodCall\NetteFormToSymfonyFormRector::class);
};
