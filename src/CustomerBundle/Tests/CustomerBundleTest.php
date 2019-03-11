<?php
/**
 *
 */

namespace Commercetools\Symfony\CustomerBundle\Tests;

use Commercetools\Symfony\CustomerBundle\CustomerBundle;
use Commercetools\Symfony\CustomerBundle\DependencyInjection\CustomerExtension;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Bundle\SecurityBundle\DependencyInjection\SecurityExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class CustomerBundleTest extends TestCase
{
    public function testGetContainerExtension()
    {
        $customerBundle = new CustomerBundle();
        $this->assertInstanceOf(CustomerExtension::class, $customerBundle->getContainerExtension());
    }

    public function testBuild()
    {
        $ctpBundle = new CustomerBundle();
        $containerBuilder = $this->prophesize(ContainerBuilder::class);
        $containerBuilder->getExtension(Argument::is('security'))
            ->willReturn(new SecurityExtension())
            ->shouldBeCalled();

        $ctpBundle->build($containerBuilder->reveal());
    }
}
