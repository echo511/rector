<?php

declare (strict_types=1);
namespace Rector\Doctrine\NodeManipulator;

use PhpParser\Node\Attribute;
use PhpParser\Node\Expr;
use PhpParser\Node\Stmt\Property;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprTrueNode;
use Rector\BetterPhpDocParser\PhpDoc\DoctrineAnnotationTagValueNode;
use Rector\BetterPhpDocParser\PhpDocInfo\PhpDocInfo;
use Rector\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use Rector\Core\PhpParser\Node\Value\ValueResolver;
use Rector\Doctrine\NodeAnalyzer\AttributeArgValueResolver;
use Rector\Doctrine\NodeAnalyzer\AttributeFinder;
final class NullabilityColumnPropertyTypeResolver
{
    /**
     * @var string
     */
    private const COLUMN_CLASS = 'Doctrine\\ORM\\Mapping\\Column';
    /**
     * @readonly
     * @var \Rector\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory
     */
    private $phpDocInfoFactory;
    /**
     * @var \Rector\Doctrine\NodeAnalyzer\AttributeFinder
     */
    private $attributeFinder;
    /**
     * @var \Rector\Doctrine\NodeAnalyzer\AttributeArgValueResolver
     */
    private $attributeArgValueResolver;
    /**
     * @var \Rector\Core\PhpParser\Node\Value\ValueResolver
     */
    private $valueResolver;
    /**
     * @see https://www.doctrine-project.org/projects/doctrine-orm/en/2.6/reference/basic-mapping.html#doctrine-mapping-types
     */
    public function __construct(\Rector\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory $phpDocInfoFactory, \Rector\Doctrine\NodeAnalyzer\AttributeFinder $attributeFinder, \Rector\Doctrine\NodeAnalyzer\AttributeArgValueResolver $attributeArgValueResolver, \Rector\Core\PhpParser\Node\Value\ValueResolver $valueResolver)
    {
        $this->phpDocInfoFactory = $phpDocInfoFactory;
        $this->attributeFinder = $attributeFinder;
        $this->attributeArgValueResolver = $attributeArgValueResolver;
        $this->valueResolver = $valueResolver;
    }
    public function isNullable(\PhpParser\Node\Stmt\Property $property) : bool
    {
        $columnAttribute = $this->attributeFinder->findAttributeByClass($property, self::COLUMN_CLASS);
        if ($columnAttribute instanceof \PhpParser\Node\Attribute) {
            $nullableExpr = $this->attributeArgValueResolver->resolve($columnAttribute, 'nullable');
            if (!$nullableExpr instanceof \PhpParser\Node\Expr) {
                return \true;
            }
            return $this->valueResolver->isTrue($nullableExpr);
        }
        $phpDocInfo = $this->phpDocInfoFactory->createFromNodeOrEmpty($property);
        return $this->isNullableColumn($phpDocInfo);
    }
    private function isNullableColumn(\Rector\BetterPhpDocParser\PhpDocInfo\PhpDocInfo $phpDocInfo) : bool
    {
        $doctrineAnnotationTagValueNode = $phpDocInfo->findOneByAnnotationClass(self::COLUMN_CLASS);
        if (!$doctrineAnnotationTagValueNode instanceof \Rector\BetterPhpDocParser\PhpDoc\DoctrineAnnotationTagValueNode) {
            return \true;
        }
        $nullableValue = $doctrineAnnotationTagValueNode->getValue('nullable');
        return $nullableValue === null || $nullableValue instanceof \PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprTrueNode;
    }
}