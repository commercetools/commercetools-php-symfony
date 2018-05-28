<?php

namespace Commercetools\Symfony\CustomerBundle;

use Commercetools\Symfony\CustomerBundle\DependencyInjection\CustomerExtension;
use Commercetools\Symfony\CustomerBundle\DependencyInjection\Factory\SecurityFactory;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class CustomerBundle extends Bundle
{
    public function getContainerExtension()
    {
        return new CustomerExtension();
    }

    public function build(ContainerBuilder $container)
    {
        $extension = $container->getExtension('security');
        $extension->addSecurityListenerFactory(new SecurityFactory());
    }
}
