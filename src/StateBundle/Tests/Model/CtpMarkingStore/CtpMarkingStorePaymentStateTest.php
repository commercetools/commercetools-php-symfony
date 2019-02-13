<?php
/**
 *
 */

namespace Commercetools\Symfony\StateBundle\Tests\Model\CtpMarkingStore;

use Commercetools\Core\Model\Order\Order;
use Commercetools\Core\Model\Payment\Payment;
use Commercetools\Core\Model\Payment\PaymentStatus;
use Commercetools\Symfony\StateBundle\Cache\StateKeyResolver;
use Commercetools\Symfony\StateBundle\Model\CtpMarkingStore\CtpMarkingStorePaymentState;
use PHPUnit\Framework\TestCase;

class CtpMarkingStorePaymentStateTest extends TestCase
{
    public function testGetStateReferenceForPayment()
    {
        $stateKeyResolver = $this->prophesize(StateKeyResolver::class);
        $ctpMarkingStore = new TestCtpMarkingStorePaymentState($stateKeyResolver->reveal(), 'initial-state');
        $paymentStatus = $this->prophesize(PaymentStatus::class);
        $paymentStatus->getState()->shouldBeCalled();

        $subject = $this->prophesize(Payment::class);
        $subject->getPaymentStatus()->willReturn($paymentStatus->reveal())->shouldBeCalledOnce();

        $ctpMarkingStore->getStateReference($subject->reveal());
    }

    public function testGetStateReferenceForNonPayment()
    {
        $stateKeyResolver = $this->prophesize(StateKeyResolver::class);
        $ctpMarkingStore = new TestCtpMarkingStorePaymentState($stateKeyResolver->reveal(), 'initial-state');

        $subject = $this->prophesize(Order::class);
        $subject->getState()->shouldBeCalled();

        $ctpMarkingStore->getStateReference($subject->reveal());
    }

    public function testGetStateReferenceForPaymentInitial()
    {
        $stateKeyResolver = $this->prophesize(StateKeyResolver::class);
        $ctpMarkingStore = new TestCtpMarkingStorePaymentState($stateKeyResolver->reveal(), 'initial-state');

        $subject = $this->prophesize(Payment::class);
        $subject->getPaymentStatus()->willReturn(null)->shouldBeCalledOnce();

        $initial = $ctpMarkingStore->getStateReference($subject->reveal());
        $this->assertSame('initial-state', $initial);
    }
}

class TestCtpMarkingStorePaymentState extends CtpMarkingStorePaymentState
{
    public function getStateReference($subject)
    {
        return parent::getStateReference($subject);
    }
}
