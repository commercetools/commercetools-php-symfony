<?php

namespace Commercetools\Symfony\CtpBundle;

use Commercetools\Symfony\CtpBundle\DependencyInjection\CommercetoolsExtension;
use Commercetools\Symfony\CtpBundle\DependencyInjection\Factory\SecurityFactory;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class CtpBundle extends Bundle
{
    public function getContainerExtension()
    {
        return new CommercetoolsExtension();
    }

    public function build(ContainerBuilder $container)
    {
        $extension = $container->getExtension('security');
        $extension->addSecurityListenerFactory(new SecurityFactory());
    }
}
