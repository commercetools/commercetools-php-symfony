<?php
/**
 *
 */

namespace Commercetools\Symfony\ExampleBundle\Tests\EventListener;


use Commercetools\Core\Model\Payment\Payment;
use Commercetools\Symfony\CartBundle\Event\PaymentPostCreateEvent;
use Commercetools\Symfony\CartBundle\Manager\CartManager;
use Commercetools\Symfony\CartBundle\Manager\OrderManager;
use Commercetools\Symfony\ExampleBundle\EventListener\PaymentSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Workflow\Event\Event;
use Symfony\Component\Workflow\Registry;

class PaymentSubscriberTest extends TestCase
{
    public function testGetSubscribedEvents()
    {
        $orderManager = $this->prophesize(OrderManager::class);
        $cartManager = $this->prophesize(CartManager::class);
        $registry = $this->prophesize(Registry::class);
        $subscriber = new PaymentSubscriber($orderManager->reveal(), $cartManager->reveal(), $registry->reveal());

        $this->assertArrayHasKey(PaymentPostCreateEvent::class, $subscriber->getSubscribedEvents());
        $this->assertArrayHasKey('workflow.PaymentState.transition.toCompleted', $subscriber->getSubscribedEvents());
    }

    public function testOnPaymentPostCreate()
    {
        $orderManager = $this->prophesize(OrderManager::class);
        $cartManager = $this->prophesize(CartManager::class);
        $registry = $this->prophesize(Registry::class);
        $subscriber = new PaymentSubscriber($orderManager->reveal(), $cartManager->reveal(), $registry->reveal());
        $event = $this->prophesize(PaymentPostCreateEvent::class);
        $event->getPayment()->willReturn(Payment::of())->shouldBeCalledOnce();

        $this->assertTrue($subscriber->onPaymentPostCreate($event->reveal()));
    }

    public function testOnPaymentCompleted()
    {
        $orderManager = $this->prophesize(OrderManager::class);
        $cartManager = $this->prophesize(CartManager::class);
        $registry = $this->prophesize(Registry::class);
        $subscriber = new PaymentSubscriber($orderManager->reveal(), $cartManager->reveal(), $registry->reveal());
        $event = $this->prophesize(Event::class);
        $event->getSubject()->willReturn(Payment::of())->shouldBeCalledOnce();

        $this->assertTrue($subscriber->onPaymentCompleted($event->reveal()));
    }
}
