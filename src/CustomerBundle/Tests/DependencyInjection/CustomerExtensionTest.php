<?php
/**
 *
 */

namespace Commercetools\Symfony\CustomerBundle\Tests\DependencyInjection;


use Commercetools\Symfony\CustomerBundle\DependencyInjection\CustomerExtension;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;

class CustomerExtensionTest extends AbstractExtensionTestCase
{
    protected function getContainerExtensions()
    {
        return [
            new CustomerExtension()
        ];
    }

    public function testExtensionLoads()
    {
        $this->load();

        $this->assertContainerBuilderHasParameter('commercetools.cache.customer', 'false');

        $this->assertContainerBuilderHasService('Commercetools\Symfony\CustomerBundle\Model\Repository\CustomerRepository');
        $this->assertContainerBuilderHasService('Commercetools\Symfony\CustomerBundle\Manager\CustomerManager');
        $this->assertContainerBuilderHasService('Commercetools\Symfony\CustomerBundle\Security\User\UserProvider');
        $this->assertContainerBuilderHasService(
            'security.authentication_provider.commercetools',
            'Commercetools\Symfony\CustomerBundle\Security\Authentication\Provider\AuthenticationProvider'
        );

//        $this->assertContainerBuilderHasService('security.authentication_provider.commercetools.main');
    }
}
