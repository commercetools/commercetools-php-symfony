<?php
/**
 *
 */

namespace Commercetools\Symfony\CartBundle\Tests\Event;


use Commercetools\Core\Model\Order\Order;
use Commercetools\Core\Request\Orders\Command\OrderSetBillingAddress;
use Commercetools\Core\Request\Orders\Command\OrderSetCustomerEmail;
use Commercetools\Symfony\CartBundle\Event\OrderUpdateEvent;
use PHPUnit\Framework\TestCase;

class OrderUpdateEventTest extends TestCase
{
    public function testOrderUpdateEvent()
    {
        $order = $this->prophesize(Order::class);
        $action = $this->prophesize(OrderSetCustomerEmail::class);
        $secondAction = $this->prophesize(OrderSetBillingAddress::class);

        $updateEvent = new OrderUpdateEvent($order->reveal(), $action->reveal());

        $this->assertInstanceOf(Order::class, $updateEvent->getOrder());
        $this->assertEquals([$action->reveal()], $updateEvent->getActions());

        $updateEvent->addAction($secondAction->reveal());

        $this->assertEquals([$action->reveal(), $secondAction->reveal()], $updateEvent->getActions());

        $updateEvent->setActions([$secondAction->reveal()]);

        $this->assertEquals([$secondAction->reveal()], $updateEvent->getActions());
    }
}
