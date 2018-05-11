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
        $customer = $this->prophesize(Cart::class);
        $action = $this->prophesize(CartSetCustomerEmailAction::class);
        $secondCart = $this->prophesize(Cart::class);

        $postUpdateEvent = new CartPostUpdateEvent($customer->reveal(), [$action->reveal()]);
        $postUpdateEvent->setCart($secondCart->reveal());

        $this->assertNotSame($customer->reveal(),$secondCart->reveal());
        $this->assertSame($secondCart->reveal(), $postUpdateEvent->getCart());
        $this->assertNotSame($customer->reveal(), $postUpdateEvent->getCart());

        $this->assertEquals([$action->reveal()], $postUpdateEvent->getActions());
    }
}
