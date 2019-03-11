<?php
/**
 *
 */

namespace Commercetools\Symfony\CartBundle\Tests;

use Commercetools\Symfony\CartBundle\CartBundle;
use Commercetools\Symfony\CartBundle\DependencyInjection\CartExtension;
use PHPUnit\Framework\TestCase;

class CartBundleTest extends TestCase
{
    public function testGetContainerExtension()
    {
        $cartBundle = new CartBundle();
        $this->assertInstanceOf(CartExtension::class, $cartBundle->getContainerExtension());
    }
}
