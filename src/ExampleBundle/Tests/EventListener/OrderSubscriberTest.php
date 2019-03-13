<?php
/**
 *
 */

namespace Commercetools\Symfony\ExampleBundle\Tests\EventListener;

use Commercetools\Symfony\CartBundle\Event\OrderCreateEvent;
use Commercetools\Symfony\CartBundle\Event\OrderPostCreateEvent;
use Commercetools\Symfony\CartBundle\Model\Repository\CartRepository;
use Commercetools\Symfony\ExampleBundle\EventListener\OrderSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class OrderSubscriberTest extends TestCase
{
    public function testGetSubscribedEvents()
    {
        $session = $this->prophesize(SessionInterface::class);
        $subscriber = new OrderSubscriber($session->reveal());

        $this->assertArrayHasKey(OrderCreateEvent::class, $subscriber->getSubscribedEvents());
        $this->assertArrayHasKey(OrderPostCreateEvent::class, $subscriber->getSubscribedEvents());
    }

    public function testOnOrderCreate()
    {
        $session = $this->prophesize(SessionInterface::class);
        $subscriber = new OrderSubscriber($session->reveal());
        $this->assertTrue($subscriber->onOrderCreate());
    }

    public function testOnOrderPostCreate()
    {
        $session = $this->prophesize(SessionInterface::class);
        $session->remove(CartRepository::CART_ID)->shouldBeCalledOnce();
        $session->remove(CartRepository::CART_ITEM_COUNT)->shouldBeCalledOnce();
        $event = $this->prophesize(OrderPostCreateEvent::class);

        $subscriber = new OrderSubscriber($session->reveal());
        $subscriber->onOrderPostCreate($event->reveal());
    }
}
