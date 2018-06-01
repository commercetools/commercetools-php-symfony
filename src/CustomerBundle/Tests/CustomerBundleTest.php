<?php
/**
 *
 */

namespace Commercetools\Symfony\CustomerBundle\Tests;


use Commercetools\Symfony\CustomerBundle\CustomerBundle;
use Commercetools\Symfony\CustomerBundle\DependencyInjection\CustomerExtension;
use PHPUnit\Framework\TestCase;

class CustomerBundleTest extends TestCase
{
    public function testGetContainerExtension()
    {
        $customerBundle = new CustomerBundle();
        $this->assertInstanceOf(CustomerExtension::class, $customerBundle->getContainerExtension());
    }
}
