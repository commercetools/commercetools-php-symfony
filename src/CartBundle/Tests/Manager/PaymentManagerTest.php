<?php
/**
 *
 */

namespace Commercetools\Symfony\CartBundle\Tests\Manager;


use Commercetools\Core\Model\Common\Money;
use Commercetools\Core\Model\Payment\Payment;
use Commercetools\Core\Model\Payment\PaymentCollection;
use Commercetools\Core\Request\Payments\Command\PaymentSetAmountPaidAction;
use Commercetools\Symfony\CartBundle\Event\PaymentPostUpdateEvent;
use Commercetools\Symfony\CartBundle\Event\PaymentUpdateEvent;
use Commercetools\Symfony\CartBundle\Manager\PaymentManager;
use Commercetools\Symfony\CartBundle\Model\PaymentUpdateBuilder;
use Commercetools\Symfony\CartBundle\Model\Repository\PaymentRepository;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class PaymentManagerTest extends TestCase
{
    private $paymentRepository;
    private $eventDispatcher;

    public function setUp()
    {
        $this->paymentRepository = $this->prophesize(PaymentRepository::class);
        $this->eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
    }

    public function testGetPaymentById()
    {
        $this->paymentRepository->getPaymentById('en', '123')
            ->willReturn(Payment::of()->setId('123'))->shouldBeCalled();

        $manager = new PaymentManager($this->paymentRepository->reveal(), $this->eventDispatcher->reveal());
        $payment = $manager->getPaymentById('en', '123');

        $this->assertInstanceOf(Payment::class, $payment);
        $this->assertSame('123', $payment->getId());
    }

    public function testGetPaymentForUser()
    {
        $this->paymentRepository->getPaymentForUser('en', '123', null, 'anon-1')
            ->willReturn(Payment::of())->shouldBeCalled();

        $manager = new PaymentManager($this->paymentRepository->reveal(), $this->eventDispatcher->reveal());
        $payment = $manager->getPaymentForUser('en', '123', null, 'anon-1');

        $this->assertInstanceOf(Payment::class, $payment);
    }

    public function testGetMultiplePayments()
    {
        $this->paymentRepository->getMultiplePayments('en', ['payment-1', 'payment-2'])
            ->willReturn(PaymentCollection::of())->shouldBeCalled();

        $manager = new PaymentManager($this->paymentRepository->reveal(), $this->eventDispatcher->reveal());
        $payment = $manager->getMultiplePayments('en', ['payment-1', 'payment-2']);

        $this->assertInstanceOf(PaymentCollection::class, $payment);
    }

    public function testCreatePayment()
    {
        $this->paymentRepository->createPayment('en', Money::of(), null, null, null)
            ->willReturn(Payment::of())->shouldBeCalled();

        $manager = new PaymentManager($this->paymentRepository->reveal(), $this->eventDispatcher->reveal());
        $payment = $manager->createPayment('en', Money::of());

        $this->assertInstanceOf(Payment::class, $payment);
    }

    public function testUpdate()
    {
        $manager = new PaymentManager($this->paymentRepository->reveal(), $this->eventDispatcher->reveal());
        $update = $manager->update(Payment::of()->setKey('payment-1'));

        $this->assertInstanceOf(PaymentUpdateBuilder::class, $update);
    }

    public function testApply()
    {
        $payment = Payment::of()->setId('1');

        $this->paymentRepository->update($payment, Argument::type('array'))
            ->will(function ($args) { return $args[0]; })->shouldBeCalled();

        $this->eventDispatcher->dispatch(
            Argument::containingString(PaymentPostUpdateEvent::class),
            Argument::type(PaymentPostUpdateEvent::class)
        )->will(function ($args) { return $args[1]; })->shouldBeCalled();

        $manager = new PaymentManager($this->paymentRepository->reveal(), $this->eventDispatcher->reveal());
        $payment = $manager->apply($payment, []);

        $this->assertInstanceOf(Payment::class, $payment);
        $this->assertSame('1', $payment->getId());
    }

    public function testDispatch()
    {
        $payment = Payment::of()->setId('1');
        $action = PaymentSetAmountPaidAction::of();

        $this->eventDispatcher->dispatch(
            Argument::containingString(PaymentSetAmountPaidAction::class),
            Argument::type(PaymentUpdateEvent::class)
        )->will(function ($args) { return $args[1]; })->shouldBeCalled();

        $manager = new PaymentManager($this->paymentRepository->reveal(), $this->eventDispatcher->reveal());

        $actions = $manager->dispatch($payment, $action);
        $this->assertCount(1, $actions);
        $this->assertInstanceOf(PaymentSetAmountPaidAction::class, current($actions));
    }

    public function testDispatchPostUpdate()
    {
        $payment = Payment::of()->setId('1');
        $action = PaymentSetAmountPaidAction::of();

        $this->eventDispatcher->dispatch(
            Argument::containingString(PaymentPostUpdateEvent::class),
            Argument::type(PaymentPostUpdateEvent::class)
        )->will(function ($args) { return $args[1]; })->shouldBeCalled();

        $manager = new PaymentManager($this->paymentRepository->reveal(), $this->eventDispatcher->reveal());

        $actions = $manager->dispatchPostUpdate($payment, [$action]);
        $this->assertCount(1, $actions);
        $this->assertInstanceOf(PaymentSetAmountPaidAction::class, current($actions));
    }
}
