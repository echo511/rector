<?php

declare (strict_types=1);
namespace RectorPrefix20210708\Symplify\SimplePhpDocParser\PhpDocNodeVisitor;

use PHPStan\PhpDocParser\Ast\Node;
use RectorPrefix20210708\Symplify\SimplePhpDocParser\ValueObject\PhpDocAttributeKey;
/**
 * Mimics https://github.com/nikic/PHP-Parser/blob/master/lib/PhpParser/NodeVisitor/ParentConnectingVisitor.php
 *
 * @see \Symplify\SimplePhpDocParser\Tests\PhpDocNodeVisitor\ParentConnectingPhpDocNodeVisitorTest
 */
final class ParentConnectingPhpDocNodeVisitor extends \RectorPrefix20210708\Symplify\SimplePhpDocParser\PhpDocNodeVisitor\AbstractPhpDocNodeVisitor
{
    /**
     * @var Node[]
     */
    private $stack = [];
    public function beforeTraverse(\PHPStan\PhpDocParser\Ast\Node $node) : void
    {
        $this->stack = [$node];
    }
    /**
     * @return int|Node|null
     */
    public function enterNode(\PHPStan\PhpDocParser\Ast\Node $node)
    {
        if ($this->stack !== []) {
            $parentNode = $this->stack[\count($this->stack) - 1];
            $node->setAttribute(\RectorPrefix20210708\Symplify\SimplePhpDocParser\ValueObject\PhpDocAttributeKey::PARENT, $parentNode);
        }
        $this->stack[] = $node;
        return $node;
    }
    /**
     * @return null|int|\PhpParser\Node|Node[] Replacement node (or special return
     */
    public function leaveNode(\PHPStan\PhpDocParser\Ast\Node $node)
    {
        \array_pop($this->stack);
        return null;
    }
}