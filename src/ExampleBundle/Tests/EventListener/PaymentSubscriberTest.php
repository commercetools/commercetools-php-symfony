<?php
/**
 *
 */

namespace Commercetools\Symfony\ExampleBundle\Tests\EventListener;

use Commercetools\Core\Model\Cart\Cart;
use Commercetools\Core\Model\Common\Context;
use Commercetools\Core\Model\CustomField\CustomFieldObject;
use Commercetools\Core\Model\CustomField\FieldContainer;
use Commercetools\Core\Model\Order\Order;
use Commercetools\Core\Model\Payment\Payment;
use Commercetools\Core\Request\Carts\Command\CartAddPaymentAction;
use Commercetools\Core\Request\Orders\Command\OrderAddPaymentAction;
use Commercetools\Symfony\CartBundle\Event\PaymentPostCreateEvent;
use Commercetools\Symfony\CartBundle\Manager\CartManager;
use Commercetools\Symfony\CartBundle\Manager\OrderManager;
use Commercetools\Symfony\CartBundle\Model\CartUpdateBuilder;
use Commercetools\Symfony\CartBundle\Model\OrderUpdateBuilder;
use Commercetools\Symfony\ExampleBundle\EventListener\PaymentSubscriber;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Workflow\Event\Event;
use Symfony\Component\Workflow\Exception\InvalidArgumentException;
use Symfony\Component\Workflow\Registry;
use Symfony\Component\Workflow\Workflow;

class PaymentSubscriberTest extends TestCase
{
    public function testGetSubscribedEvents()
    {
        $orderManager = $this->prophesize(OrderManager::class);
        $cartManager = $this->prophesize(CartManager::class);
        $registry = $this->prophesize(Registry::class);
        $session = $this->prophesize(Session::class);
        $subscriber = new PaymentSubscriber($orderManager->reveal(), $cartManager->reveal(), $registry->reveal(), $session->reveal());

        $this->assertArrayHasKey(PaymentPostCreateEvent::class, $subscriber->getSubscribedEvents());
        $this->assertArrayHasKey('workflow.PaymentState.transition.toCompleted', $subscriber->getSubscribedEvents());
    }

    public function testOnPaymentPostCreateWithoutCustomFields()
    {
        $orderManager = $this->prophesize(OrderManager::class);
        $cartManager = $this->prophesize(CartManager::class);
        $registry = $this->prophesize(Registry::class);
        $session = $this->prophesize(Session::class);
        $subscriber = new PaymentSubscriber($orderManager->reveal(), $cartManager->reveal(), $registry->reveal(), $session->reveal());
        $event = $this->prophesize(PaymentPostCreateEvent::class);
        $event->getPayment()->willReturn(Payment::of())->shouldBeCalledOnce();

        $this->assertTrue($subscriber->onPaymentPostCreate($event->reveal()));
    }

    public function testOnPaymentCompletedWithoutCustomFields()
    {
        $orderManager = $this->prophesize(OrderManager::class);
        $cartManager = $this->prophesize(CartManager::class);
        $registry = $this->prophesize(Registry::class);
        $session = $this->prophesize(Session::class);
        $subscriber = new PaymentSubscriber($orderManager->reveal(), $cartManager->reveal(), $registry->reveal(), $session->reveal());
        $event = $this->prophesize(Event::class);
        $event->getSubject()->willReturn(Payment::of())->shouldBeCalledOnce();

        $this->assertTrue($subscriber->onPaymentCompleted($event->reveal()));
    }

    public function testOnPaymentPostCreateWithOrderId()
    {
        /** @var OrderManager $orderManager */
        $orderManager = $this->prophesize(OrderManager::class);
        $cartManager = $this->prophesize(CartManager::class);
        $registry = $this->prophesize(Registry::class);
        $session = $this->prophesize(Session::class);

        $payment = Payment::of()
            ->setCustom(CustomFieldObject::of()->setFields(FieldContainer::of()
                ->set('orderReference', 'foo')
                ->set('cartReference', 'bar')))
            ->setId('foobar')
            ->setContext(Context::of()->setLocale('en'));

        $orderUpdateBuilder = $this->prophesize(OrderUpdateBuilder::class);
        $orderUpdateBuilder->flush()->shouldBeCalledOnce();
        $orderUpdateBuilder->addAction(Argument::type(OrderAddPaymentAction::class))->shouldBeCalledOnce();

        $orderManager->getOrderById('en', 'foo')->willReturn(Order::of())->shouldBeCalledOnce();
        $orderManager->update(Argument::type(Order::class))->willReturn($orderUpdateBuilder->reveal())->shouldBeCalledOnce();

        $subscriber = new PaymentSubscriber($orderManager->reveal(), $cartManager->reveal(), $registry->reveal(), $session->reveal());
        $event = $this->prophesize(PaymentPostCreateEvent::class);
        $event->getPayment()->willReturn($payment)->shouldBeCalledTimes(5);

        $this->assertTrue($subscriber->onPaymentPostCreate($event->reveal()));
    }

    public function testOnPaymentPostCreateWithCartId()
    {
        /** @var OrderManager $orderManager */
        $orderManager = $this->prophesize(OrderManager::class);
        $cartManager = $this->prophesize(CartManager::class);
        $registry = $this->prophesize(Registry::class);
        $session = $this->prophesize(Session::class);

        $payment = Payment::of()
            ->setCustom(CustomFieldObject::of()->setFields(FieldContainer::of()
                ->set('cartReference', 'bar')))
            ->setId('foobar')
            ->setContext(Context::of()->setLocale('en'));

        $cartUpdateBuilder = $this->prophesize(CartUpdateBuilder::class);
        $cartUpdateBuilder->flush()->shouldBeCalledOnce();
        $cartUpdateBuilder->addAction(Argument::type(CartAddPaymentAction::class))->shouldBeCalledOnce();

        $cartManager->getCart('en', 'bar')->willReturn(Cart::of())->shouldBeCalledOnce();
        $cartManager->update(Argument::type(Cart::class))->willReturn($cartUpdateBuilder->reveal())->shouldBeCalledOnce();

        $subscriber = new PaymentSubscriber($orderManager->reveal(), $cartManager->reveal(), $registry->reveal(), $session->reveal());
        $event = $this->prophesize(PaymentPostCreateEvent::class);
        $event->getPayment()->willReturn($payment)->shouldBeCalledTimes(5);

        $this->assertTrue($subscriber->onPaymentPostCreate($event->reveal()));
    }

    public function testOnPaymentCompletedWithOrderId()
    {
        /** @var OrderManager $orderManager */
        $orderManager = $this->prophesize(OrderManager::class);
        $cartManager = $this->prophesize(CartManager::class);
        $registry = $this->prophesize(Registry::class);
        $session = $this->prophesize(Session::class);

        $payment = Payment::of()
            ->setCustom(CustomFieldObject::of()->setFields(FieldContainer::of()
                ->set('orderReference', 'foo')))
            ->setId('foobar')
            ->setContext(Context::of()->setLocale('en'));

        $orderManager->getOrderById('en', 'foo')->willReturn(Order::of())->shouldBeCalledOnce();

        $workflow = $this->prophesize(Workflow::class);
        $workflow->can(Argument::type(Order::class), 'toPaid')->willReturn(true)->shouldBeCalledOnce();
        $workflow->apply(Argument::type(Order::class), 'toPaid')->shouldBeCalledOnce();

        $registry->get(Argument::type(Order::class))->willReturn($workflow)->shouldBeCalledOnce();

        $subscriber = new PaymentSubscriber($orderManager->reveal(), $cartManager->reveal(), $registry->reveal(), $session->reveal());
        $event = $this->prophesize(Event::class);
        $event->getSubject()->willReturn($payment)->shouldBeCalledTimes(3);

        $this->assertTrue($subscriber->onPaymentCompleted($event->reveal()));
    }

    public function testOnPaymentCompletedWithOrderIdAndWrongWorkflow()
    {
        /** @var OrderManager $orderManager */
        $orderManager = $this->prophesize(OrderManager::class);
        $cartManager = $this->prophesize(CartManager::class);
        $registry = $this->prophesize(Registry::class);
        $session = $this->prophesize(Session::class);

        $payment = Payment::of()
            ->setCustom(CustomFieldObject::of()->setFields(FieldContainer::of()
                ->set('orderReference', 'foo')))
            ->setId('foobar')
            ->setContext(Context::of()->setLocale('en'));

        $orderManager->getOrderById('en', 'foo')->willReturn(Order::of())->shouldBeCalledOnce();

        $workflow = $this->prophesize(Workflow::class);
        $workflow->can(Argument::type(Order::class), 'toPaid')->willReturn(false)->shouldBeCalledOnce();
        $workflow->apply(Argument::type(Order::class), 'toPaid')->shouldNotBeCalled();

        $registry->get(Argument::type(Order::class))->willReturn($workflow)->shouldBeCalledOnce();

        $flashBag = $this->prophesize(FlashBag::class);
        $flashBag->add(Argument::is('error'), Argument::is('Related Order could not be updated'))->shouldBeCalled();
        $session->getFlashBag()->willReturn($flashBag->reveal())->shouldBeCalled();

        $subscriber = new PaymentSubscriber($orderManager->reveal(), $cartManager->reveal(), $registry->reveal(), $session->reveal());
        $event = $this->prophesize(Event::class);
        $event->getSubject()->willReturn($payment)->shouldBeCalledTimes(3);

        $this->assertTrue($subscriber->onPaymentCompleted($event->reveal()));
    }

    public function testOnPaymentCompletedWithWrongOrder()
    {
        /** @var OrderManager $orderManager */
        $orderManager = $this->prophesize(OrderManager::class);
        $cartManager = $this->prophesize(CartManager::class);
        $registry = $this->prophesize(Registry::class);
        $session = $this->prophesize(Session::class);

        $payment = Payment::of()
            ->setCustom(CustomFieldObject::of()->setFields(FieldContainer::of()
                ->set('orderReference', 'foo')))
            ->setId('foobar')
            ->setContext(Context::of()->setLocale('en'));

        $orderManager->getOrderById('en', 'foo')->willReturn(Order::of())->shouldBeCalledOnce();

        $registry->get(Argument::type(Order::class))->will(function () {
            throw new InvalidArgumentException('error');
        })->shouldBeCalledOnce();

        $flashBag = $this->prophesize(FlashBag::class);
        $flashBag->add(Argument::is('error'), Argument::is('Related Order could not be updated'))->shouldBeCalled();
        $session->getFlashBag()->willReturn($flashBag->reveal())->shouldBeCalled();

        $subscriber = new PaymentSubscriber($orderManager->reveal(), $cartManager->reveal(), $registry->reveal(), $session->reveal());
        $event = $this->prophesize(Event::class);
        $event->getSubject()->willReturn($payment)->shouldBeCalledTimes(3);

        $this->assertFalse($subscriber->onPaymentCompleted($event->reveal()));
    }
}
