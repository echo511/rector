<?php

declare(strict_types=1);

use Rector\TypeDeclaration\Rector\MethodCall\FormerNullableArgumentToScalarTypedRector;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();
    $services->set(FormerNullableArgumentToScalarTypedRector::class);
};