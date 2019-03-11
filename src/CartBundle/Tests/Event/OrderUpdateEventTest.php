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
        $event = new OrderUpdateEvent(Order::of(), OrderSetCustomerEmail::of());
        $this->assertInstanceOf(Order::class, $event->getOrder());
        $this->assertSame(1, count($event->getActions()));
        $this->assertInstanceOf(OrderSetCustomerEmail::class, current($event->getActions()));

        $event->addAction(OrderSetBillingAddress::of());
        $this->assertSame(2, count($event->getActions()));

        $event->setActions([OrderSetBillingAddress::of()]);
        $this->assertSame(1, count($event->getActions()));
        $this->assertInstanceOf(OrderSetBillingAddress::class, current($event->getActions()));
    }
}
