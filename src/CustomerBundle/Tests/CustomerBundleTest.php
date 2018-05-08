<?php
/**
 *
 */

namespace Commercetools\Symfony\CustomerBundle\Tests;


use Commercetools\Symfony\CustomerBundle\CustomerBundle;
use Commercetools\Symfony\CustomerBundle\DependencyInjection\CustomerExtension;

class CustomerBundleTest extends \PHPUnit_Framework_TestCase
{
    public function testGetContainerExtension()
    {
        $customerBundle = new CustomerBundle();
        $this->assertInstanceOf(CustomerExtension::class, $customerBundle->getContainerExtension());
    }
}
