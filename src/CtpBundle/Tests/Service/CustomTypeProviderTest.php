<?php
/**
 *
 */

namespace Commercetools\Symfony\CtpBundle\Tests\Service;


use Commercetools\Core\Model\Type\Type;
use Commercetools\Core\Model\Type\TypeCollection;
use Commercetools\Core\Model\Type\TypeReference;
use Commercetools\Symfony\CtpBundle\Service\CustomTypeProvider;
use PHPUnit\Framework\TestCase;

class CustomTypeProviderTest extends TestCase
{
    public function testGetType()
    {
        $customTypeProvider = new CustomTypeProvider(TypeCollection::fromArray([
            [
                'id' => 'bar',
                'key' => 'foo',
                'name' =>
                    ['en' => 'custom-type-name'],
                'description' =>
                    ['en' => 'custom-type-description']
            ]
        ]));

        $type = $customTypeProvider->getType('foo');

        $this->assertInstanceOf(Type::class, $type);
        $this->assertSame('bar', $type->getId());

        $typeReference = $customTypeProvider->getTypeReference('foo');
        $this->assertInstanceOf(TypeReference::class, $typeReference);
        $this->assertSame('bar', $typeReference->getId());

        $typeReference = $customTypeProvider->getTypeReference('bar');
        $this->assertNull($typeReference);
    }
}
