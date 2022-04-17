<?php

declare (strict_types=1);
namespace RectorPrefix20220417;

use Rector\Config\RectorConfig;
use Rector\PHPUnit\Rector\MethodCall\UseSpecificWillMethodRector;
return static function (\Rector\Config\RectorConfig $rectorConfig) : void {
    $services = $rectorConfig->services();
    $services->set(\Rector\PHPUnit\Rector\MethodCall\UseSpecificWillMethodRector::class);
};
