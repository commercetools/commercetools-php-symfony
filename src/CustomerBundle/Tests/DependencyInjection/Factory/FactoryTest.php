<?php
/**
 *
 */

namespace Commercetools\Symfony\CustomerBundle\Tests\DependencyInjection\Factory;

use Commercetools\Symfony\CustomerBundle\DependencyInjection\Factory\SecurityFactory;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class FactoryTest extends TestCase
{
    public function testCreateAuthProvider()
    {
        $container = $this->prophesize(ContainerBuilder::class);
        $definition = $this->prophesize(ChildDefinition::class);

        $definition->replaceArgument(1, Argument::type(Reference::class))
            ->willReturn($definition)->shouldBeCalledOnce();

        $definition->replaceArgument(3, Argument::is('foo'))
            ->willReturn($definition)->shouldBeCalledOnce();

        $container->setDefinition(
            Argument::is('security.authentication_provider.commercetools.foo'),
            Argument::type(ChildDefinition::class)
        )->willReturn($definition)->shouldBeCalledOnce();

        $factory = new TestSecurityFactory();
        $factory->createAuthProvider($container->reveal(), 'foo', null, 'bar');
    }

    public function testGetListenerId()
    {
        $factory = new TestSecurityFactory();
        $this->assertSame('security.authentication.listener.form', $factory->getListenerId());
    }

    public function testGetKey()
    {
        $factory = new SecurityFactory();
        $this->assertSame('commercetools-login', $factory->getKey());
    }
}

class TestSecurityFactory extends SecurityFactory
{
    public function createAuthProvider(ContainerBuilder $container, $id, $config, $userProviderId)
    {
        return parent::createAuthProvider($container, $id, $config, $userProviderId);
    }

    public function getListenerId()
    {
        return parent::getListenerId();
    }
}
