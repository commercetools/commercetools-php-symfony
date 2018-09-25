<?php
/**
 *
 */

namespace Commercetools\Symfony\ExampleBundle\Tests\DependencyInjection;


use Commercetools\Symfony\ExampleBundle\DependencyInjection\ExampleExtension;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;

class ExampleExtensionTest extends AbstractExtensionTestCase
{
    protected function getContainerExtensions()
    {
        return [
            new ExampleExtension()
        ];
    }

    public function testExtensionLoads()
    {
        $this->load();


        $this->assertContainerBuilderHasService(
            'commercetools.auth.listener',
            'Commercetools\Symfony\ExampleBundle\EventListener\AuthenticationSubscriber'
        );
        $this->assertContainerBuilderHasService('Commercetools\Symfony\ExampleBundle\EventListener\PaymentSubscriber');
        $this->assertContainerBuilderHasService('Commercetools\Symfony\ExampleBundle\EventListener\CartSubscriber');
        $this->assertContainerBuilderHasService('Commercetools\Symfony\ExampleBundle\EventListener\OrderSubscriber');


//        $this->assertContainerBuilderHasService('security.authentication_provider.commercetools.main');
    }
}
