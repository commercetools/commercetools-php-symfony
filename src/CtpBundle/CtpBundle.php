<?php

namespace Commercetools\Symfony\CtpBundle;

use Commercetools\Symfony\CtpBundle\DependencyInjection\CommercetoolsExtension;
use Commercetools\Symfony\CtpBundle\DependencyInjection\Compiler\ProfilerControllerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class CtpBundle extends Bundle
{
    const VERSION = '0.4.8-dev';

    public function getContainerExtension()
    {
        return new CommercetoolsExtension();
    }

    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new ProfilerControllerPass());
    }
}
