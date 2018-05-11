<?php
/**
 *
 */

namespace Commercetools\Symfony\CartBundle\Tests\Event;


use Commercetools\Core\Model\Cart\Cart;
use Commercetools\Core\Request\Carts\Command\CartSetCustomerEmailAction;
use Commercetools\Core\Request\Carts\Command\CartSetShippingMethodTaxAmountAction;
use Commercetools\Symfony\CartBundle\Event\CartUpdateEvent;
use PHPUnit\Framework\TestCase;

class CartUpdateEventTest extends TestCase
{
    public function testCartUpdateEvent()
    {
        $cart = $this->prophesize(Cart::class);
        $action = $this->prophesize(CartSetCustomerEmailAction::class);
        $secondAction = $this->prophesize(CartSetShippingMethodTaxAmountAction::class);

        $updateEvent = new CartUpdateEvent($cart->reveal(), $action->reveal());

        $this->assertInstanceOf(Cart::class, $updateEvent->getCart());
        $this->assertEquals([$action->reveal()], $updateEvent->getActions());

        $updateEvent->addAction($secondAction->reveal());

        $this->assertEquals([$action->reveal(), $secondAction->reveal()], $updateEvent->getActions());

        $updateEvent->setActions([$secondAction->reveal()]);

        $this->assertEquals([$secondAction->reveal()], $updateEvent->getActions());
    }
}
