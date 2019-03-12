<?php
/**
 *
 */

namespace Commercetools\Symfony\CartBundle\Tests\Event;

use Commercetools\Core\Model\Payment\Payment;
use Commercetools\Core\Request\Payments\Command\PaymentSetCustomerAction;
use Commercetools\Symfony\CartBundle\Event\PaymentPostUpdateEvent;
use PHPUnit\Framework\TestCase;

class PaymentPostUpdateEventTest extends TestCase
{
    public function testPaymentPostUpdateEvent()
    {
        $event = new PaymentPostUpdateEvent(Payment::of(), [PaymentSetCustomerAction::of()]);
        $this->assertInstanceOf(Payment::class, $event->getPayment());
        $this->assertInstanceOf(PaymentSetCustomerAction::class, current($event->getActions()));
    }
}
