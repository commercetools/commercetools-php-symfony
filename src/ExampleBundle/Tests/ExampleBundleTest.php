<?php
/**
 *
 */

namespace Commercetools\Symfony\ExampleBundle\Tests;


use Commercetools\Symfony\CtpBundle\DependencyInjection\Compiler\ProfilerControllerPass;
use Commercetools\Symfony\ExampleBundle\DependencyInjection\ExampleExtension;
use Commercetools\Symfony\ExampleBundle\ExampleBundle;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ExampleBundleTest extends TestCase
{
    public function testBuild()
    {
        $container = $this->prophesize(ContainerBuilder::class);
        $container->addCompilerPass(Argument::type(ProfilerControllerPass::class))->shouldBeCalledOnce();

        $exampleBundle = new ExampleBundle();

        $this->assertInstanceOf(ExampleExtension::class, $exampleBundle->getContainerExtension());
        $exampleBundle->build($container->reveal());
    }
}
