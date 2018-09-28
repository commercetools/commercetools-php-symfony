<?php
/**
 *
 */

namespace Commercetools\Symfony\StateBundle\Tests\DependencyInjection\Compiler;


use Commercetools\Symfony\CtpBundle\Tests\ContainerTrait;
use Commercetools\Symfony\StateBundle\DependencyInjection\Compiler\CtpStateMachinePass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Definition;

class CtpStateMachinePassTest extends TestCase
{
    use ContainerTrait;

    public function testProcessWhenProfilerIsPresent()
    {
        $container = $this->getContainer();
        $compilerPass = new CtpStateMachinePass();

        $fooDefinition = new Definition('bar', [2 => 'arg2', 3 => 'foobar']);
        $fooDefinition->addTag('workflow.definition');
        $container->addDefinitions(['foo' => $fooDefinition]);

        $wrongDefinition = new Definition('wrong', [2 => 'arg2', 3 => 'ignore']);
        $container->addDefinitions(['random' => $wrongDefinition]);

        $container->addCompilerPass($compilerPass);
        $compilerPass->process($container);

        $this->assertTrue($container->hasDefinition('transition_listener.foobar'));
        $this->assertFalse($container->hasDefinition('transition_listener.ignore'));
    }
}
