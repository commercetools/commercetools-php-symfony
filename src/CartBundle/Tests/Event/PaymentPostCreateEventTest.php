<?php
/**
 *
 */

namespace Commercetools\Symfony\CartBundle\Tests\Event;

use Commercetools\Core\Model\Payment\Payment;
use Commercetools\Symfony\CartBundle\Event\PaymentPostCreateEvent;
use PHPUnit\Framework\TestCase;

class PaymentPostCreateEventTest extends TestCase
{
    public function testPaymentPostCreateEvent()
    {
        $event = new PaymentPostCreateEvent(Payment::of());
        $this->assertInstanceOf(Payment::class, $event->getPayment());
    }
}
