<?php
/**
 *
 */

namespace Commercetools\Symfony\CustomerBundle\Tests;


use Commercetools\Symfony\CustomerBundle\CustomerBundle;
use Commercetools\Symfony\CustomerBundle\DependencyInjection\CustomerExtension;
use Commercetools\Symfony\CustomerBundle\DependencyInjection\Factory\SecurityFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;

class CustomerBundleTest extends TestCase
{
    public function testGetContainerExtension()
    {
        $customerBundle = new CustomerBundle();
        $this->assertInstanceOf(CustomerExtension::class, $customerBundle->getContainerExtension());
    }
}
