<?php
/**
 *
 */

namespace Commercetools\Symfony\ShoppingListBundle\Tests;


use Commercetools\Symfony\ShoppingListBundle\DependencyInjection\ShoppingListExtension;
use Commercetools\Symfony\ShoppingListBundle\ShoppingListBundle;
use PHPUnit\Framework\TestCase;

class ShoppingListBundleTest extends TestCase
{
    public function testGetContainerExtension()
    {
        $shoppingListBundle = new ShoppingListBundle();
        $this->assertInstanceOf(ShoppingListExtension::class, $shoppingListBundle->getContainerExtension());
    }
}
