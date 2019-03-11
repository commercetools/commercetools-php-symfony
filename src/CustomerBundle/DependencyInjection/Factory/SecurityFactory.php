<?php
/**
 * @author: Ylambers <yaron.lambers@commercetools.de>
 */

namespace Commercetools\Symfony\CustomerBundle\DependencyInjection\Factory;

use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\FormLoginFactory;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class SecurityFactory extends FormLoginFactory
{
    public function getKey()
    {
        return 'commercetools-login';
    }

    protected function getListenerId()
    {
        return 'security.authentication.listener.form';
    }

    protected function createAuthProvider(ContainerBuilder $container, $id, $config, $userProviderId)
    {
        $provider = 'security.authentication_provider.commercetools.'.$id;
        $container
            ->setDefinition($provider, new ChildDefinition('security.authentication_provider.commercetools'))
            ->replaceArgument(1, new Reference($userProviderId))
            ->replaceArgument(3, $id);

        return $provider;
    }
}
