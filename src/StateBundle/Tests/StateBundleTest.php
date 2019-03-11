<?php
/**
 *
 */

namespace Commercetools\Symfony\StateBundle\Tests;

use Commercetools\Symfony\StateBundle\DependencyInjection\Compiler\CtpStateMachinePass;
use Commercetools\Symfony\StateBundle\DependencyInjection\StateExtension;
use Commercetools\Symfony\StateBundle\StateBundle;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class StateBundleTest extends TestCase
{
    public function testBuild()
    {
        $container = $this->prophesize(ContainerBuilder::class);
        $container->addCompilerPass(Argument::type(CtpStateMachinePass::class))->shouldBeCalledOnce();

        $stateBundle = new StateBundle();

        $this->assertInstanceOf(StateExtension::class, $stateBundle->getContainerExtension());
        $stateBundle->build($container->reveal());
    }
}
