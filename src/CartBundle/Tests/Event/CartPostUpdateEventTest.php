<?php
/**
 *
 */

namespace Commercetools\Symfony\CartBundle\Tests\Event;

use Commercetools\Core\Model\Cart\Cart;
use Commercetools\Core\Request\Carts\Command\CartSetCustomerEmailAction;
use Commercetools\Symfony\CartBundle\Event\CartPostUpdateEvent;
use PHPUnit\Framework\TestCase;

class CartPostUpdateEventTest extends TestCase
{
    public function testCartPostUpdateEvent()
    {
        $cart = $this->prophesize(Cart::class);
        $action = $this->prophesize(CartSetCustomerEmailAction::class);
        $secondCart = $this->prophesize(Cart::class);

        $postUpdateEvent = new CartPostUpdateEvent($cart->reveal(), [$action->reveal()]);
        $postUpdateEvent->setCart($secondCart->reveal());

        $this->assertNotSame($cart->reveal(), $secondCart->reveal());
        $this->assertSame($secondCart->reveal(), $postUpdateEvent->getCart());
        $this->assertNotSame($cart->reveal(), $postUpdateEvent->getCart());

        $this->assertEquals([$action->reveal()], $postUpdateEvent->getActions());
    }
}
