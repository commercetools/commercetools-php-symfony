<?php
/**
 *
 */

namespace Commercetools\Symfony\StateBundle;


use Commercetools\Symfony\StateBundle\DependencyInjection\Compiler\CtpStateMachinePass;
use Commercetools\Symfony\StateBundle\DependencyInjection\StateExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class StateBundle extends Bundle
{
    public function getContainerExtension()
    {
        return new StateExtension();
    }

    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new CtpStateMachinePass());
    }
}

