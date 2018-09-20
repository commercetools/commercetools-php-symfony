<?php
/**
 *
 */

namespace Commercetools\Symfony\CartBundle\Tests\DependencyInjection;


use Commercetools\Symfony\CartBundle\DependencyInjection\CartExtension;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;

class CartExtensionTest extends AbstractExtensionTestCase
{
    protected function getContainerExtensions()
    {
        return [
            new CartExtension()
        ];
    }

    public function testExtensionLoads()
    {
        $this->load();

        $this->assertContainerBuilderHasParameter('commercetools.cache.cart', 'false');
        $this->assertContainerBuilderHasParameter('commercetools.cache.shipping_method', 'false');
        $this->assertContainerBuilderHasParameter('commercetools.cache.order', 'false');
        $this->assertContainerBuilderHasParameter('commercetools.cache.payment', 'false');

        $this->assertContainerBuilderHasService('Commercetools\Symfony\CartBundle\Model\Repository\CartRepository');
        $this->assertContainerBuilderHasService('Commercetools\Symfony\CartBundle\Model\Repository\PaymentRepository');
        $this->assertContainerBuilderHasService('Commercetools\Symfony\CartBundle\Model\Repository\OrderRepository');
        $this->assertContainerBuilderHasService('Commercetools\Symfony\CartBundle\Model\Repository\ShippingMethodRepository');

        $this->assertContainerBuilderHasService('Commercetools\Symfony\CartBundle\Manager\CartManager');
        $this->assertContainerBuilderHasService('Commercetools\Symfony\CartBundle\Manager\PaymentManager');
        $this->assertContainerBuilderHasService('Commercetools\Symfony\CartBundle\Manager\OrderManager');
        $this->assertContainerBuilderHasService('Commercetools\Symfony\CartBundle\Manager\ShippingMethodManager');
    }
}
