<?php
/**
 */

namespace Commercetools\Symfony\CtpBundle\Tests\DependencyInjection\Compiler;

use Commercetools\Symfony\CtpBundle\DependencyInjection\Compiler\ProfilerControllerPass;
use Commercetools\Symfony\CtpBundle\Tests\ContainerTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Definition;

class ProfilerControllerPassTest extends TestCase
{
    use ContainerTrait;

    public function testProcessWhenProfilerIsNotPresent()
    {
        $container = $this->getContainer();
        $compilerPass = new ProfilerControllerPass;

        $container->addCompilerPass($compilerPass);
        $compilerPass->process($container);

        $this->assertFalse($container->hasDefinition('commercetools.profiler_controller'));
    }

    public function testProcessWhenProfilerIsPresent()
    {
        $container = $this->getContainer();
        $container->setDefinition('profiler', new Definition());
        $container->setDefinition('twig', new Definition());
        $compilerPass = new ProfilerControllerPass;

        $container->addCompilerPass($compilerPass);
        $compilerPass->process($container);

        $this->assertTrue($container->hasDefinition('commercetools.profiler_controller'));
        $this->assertTrue($container->hasDefinition('commercetools.profiler.extension'));
    }
}
