<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace RectorPrefix20220417\Symfony\Component\Yaml\Tag;

/**
 * @author Nicolas Grekas <p@tchwork.com>
 * @author Guilhem N. <egetick@gmail.com>
 */
final class TaggedValue
{
    /**
     * @var string
     */
    private $tag;
    /**
     * @var mixed
     */
    private $value;
    /**
     * @param mixed $value
     */
    public function __construct(string $tag, $value)
    {
        $this->tag = $tag;
        $this->value = $value;
    }
    public function getTag() : string
    {
        return $this->tag;
    }
    public function getValue()
    {
        return $this->value;
    }
}
