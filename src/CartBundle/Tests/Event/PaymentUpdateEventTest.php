<?php
/**
 *
 */

namespace Commercetools\Symfony\CartBundle\Tests\Event;

use Commercetools\Core\Model\Payment\Payment;
use Commercetools\Core\Request\Payments\Command\PaymentChangeAmountPlannedAction;
use Commercetools\Core\Request\Payments\Command\PaymentSetCustomerAction;
use Commercetools\Symfony\CartBundle\Event\PaymentUpdateEvent;
use PHPUnit\Framework\TestCase;

class PaymentUpdateEventTest extends TestCase
{
    public function testPaymentUpdateEvent()
    {
        $event = new PaymentUpdateEvent(Payment::of(), PaymentSetCustomerAction::of());
        $this->assertInstanceOf(Payment::class, $event->getPayment());
        $this->assertSame(1, count($event->getActions()));
        $this->assertInstanceOf(PaymentSetCustomerAction::class, current($event->getActions()));

        $event->addAction(PaymentChangeAmountPlannedAction::of());
        $this->assertSame(2, count($event->getActions()));

        $event->setActions([PaymentChangeAmountPlannedAction::of()]);
        $this->assertSame(1, count($event->getActions()));
        $this->assertInstanceOf(PaymentChangeAmountPlannedAction::class, current($event->getActions()));
    }
}
