<?php
/**
 *
 */

namespace Commercetools\Symfony\CartBundle\Tests\Event;


use Commercetools\Core\Model\Order\Order;
use Commercetools\Core\Request\Orders\Command\OrderSetCustomerEmail;
use Commercetools\Symfony\CartBundle\Event\OrderPostUpdateEvent;
use PHPUnit\Framework\TestCase;

class OrderPostUpdateEventTest extends TestCase
{
    public function testOrderPostUpdateEvent()
    {
        $order = $this->prophesize(Order::class);
        $action = $this->prophesize(OrderSetCustomerEmail::class);
        $secondOrder = $this->prophesize(Order::class);

        $postUpdateEvent = new OrderPostUpdateEvent($order->reveal(), [$action->reveal()]);
        $postUpdateEvent->setOrder($secondOrder->reveal());

        $this->assertNotSame($order->reveal(),$secondOrder->reveal());
        $this->assertSame($secondOrder->reveal(), $postUpdateEvent->getOrder());
        $this->assertNotSame($order->reveal(), $postUpdateEvent->getOrder());

        $this->assertEquals([$action->reveal()], $postUpdateEvent->getActions());
    }
}
