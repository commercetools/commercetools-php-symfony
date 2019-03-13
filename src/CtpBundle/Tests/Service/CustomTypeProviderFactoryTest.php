<?php
/**
 *
 */

namespace Commercetools\Symfony\CtpBundle\Tests\Service;


use Commercetools\Symfony\CtpBundle\Service\CustomTypeProvider;
use Commercetools\Symfony\CtpBundle\Service\CustomTypeProviderFactory;
use PHPUnit\Framework\TestCase;

class CustomTypeProviderFactoryTest extends TestCase
{
    public function testBuild()
    {
        $factory = new CustomTypeProviderFactory();

        $customTypeProvider = $factory->build([]);

        $this->assertInstanceOf(CustomTypeProvider::class, $customTypeProvider);
    }
}
