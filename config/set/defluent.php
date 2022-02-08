<?php

declare (strict_types=1);
namespace RectorPrefix20220208;

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
// @see https://ocramius.github.io/blog/fluent-interfaces-are-evil/
// @see https://www.yegor256.com/2018/03/13/fluent-interfaces.html
return static function (\Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator $containerConfigurator) : void {
    $deprecatedMessage = \sprintf('The DEFLUENT set is deprecated for high number of assumptions and reported bugs. Better use PHPStan rule "%s" to warn about these cases and refactor manually.', 'https://github.com/symplify/phpstan-rules/blob/main/docs/rules_overview.md#nochainmethodcallrule');
    \trigger_error($deprecatedMessage);
    \sleep(3);
};
