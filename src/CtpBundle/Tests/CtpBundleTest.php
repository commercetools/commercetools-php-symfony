<?php
/**
 *
 */

namespace Commercetools\Symfony\Ctp\Tests;

use Commercetools\Symfony\CtpBundle\CtpBundle;
use Commercetools\Symfony\CtpBundle\DependencyInjection\CommercetoolsExtension;
use Commercetools\Symfony\CtpBundle\DependencyInjection\Compiler\ProfilerControllerPass;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class CtpBundleTest extends TestCase
{
    public function testGetContainerExtension()
    {
        $ctpBundle = new CtpBundle();
        $this->assertInstanceOf(CommercetoolsExtension::class, $ctpBundle->getContainerExtension());
    }

    public function testBuild()
    {
        $ctpBundle = new CtpBundle();
        $containerBuilder = $this->prophesize(ContainerBuilder::class);
        $containerBuilder->addCompilerPass(Argument::type(ProfilerControllerPass::class))->shouldBeCalled();

        $ctpBundle->build($containerBuilder->reveal());
    }
}
