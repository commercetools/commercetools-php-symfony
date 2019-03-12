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
        $event = new OrderPostUpdateEvent(Order::of(), [OrderSetCustomerEmail::of()]);
        $this->assertInstanceOf(Order::class, $event->getOrder());
        $this->assertInstanceOf(OrderSetCustomerEmail::class, current($event->getActions()));
    }
}
