<?php
/**
 *
 */

namespace Commercetools\Symfony\ExampleBundle\Tests\Entity;

use Commercetools\Core\Model\Cart\Cart;
use Commercetools\Core\Model\Common\Address;
use Commercetools\Symfony\ExampleBundle\Entity\CartEntity;
use PHPUnit\Framework\TestCase;

class CartEntityTest extends TestCase
{
    public function testOfCart()
    {
        $cart = Cart::of()->setShippingAddress(Address::of()->setCountry('DE'));

        $cartEntity = CartEntity::ofCart($cart);

        $this->assertInstanceOf(CartEntity::class, $cartEntity);
        $this->assertSame(['country' => 'DE'], $cartEntity->getShippingAddress());
        $this->assertSame([], $cartEntity->getBillingAddress());
        $this->assertNull($cartEntity->getCheck());
    }

    public function testSetShippingAddress()
    {
        $cart = new CartEntity();
        $cart->setShippingAddress(['foo' => 'bar']);
        $this->assertSame(['foo' => 'bar'], $cart->getShippingAddress());
    }

    public function testSetBillingAddress()
    {
        $cart = new CartEntity();
        $cart->setBillingAddress(['foo' => 'bar']);
        $this->assertSame(['foo' => 'bar'], $cart->getBillingAddress());
    }

    public function testSetName()
    {
        $cart = new CartEntity();
        $cart->setName('foo');
        $this->assertSame('foo', $cart->getName());
    }

    public function testSetCheck()
    {
        $cart = new CartEntity();
        $cart->setCheck(true);
        $this->assertSame(true, $cart->getCheck());
    }
}
