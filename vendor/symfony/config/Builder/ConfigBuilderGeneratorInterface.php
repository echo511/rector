<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace RectorPrefix20210708\Symfony\Component\Config\Builder;

use RectorPrefix20210708\Symfony\Component\Config\Definition\ConfigurationInterface;
/**
 * Generates ConfigBuilders to help create valid config.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
interface ConfigBuilderGeneratorInterface
{
    /**
     * @return \Closure that will return the root config class
     * @param \Symfony\Component\Config\Definition\ConfigurationInterface $configuration
     */
    public function build($configuration) : \Closure;
}