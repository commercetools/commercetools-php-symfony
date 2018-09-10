<?php
/**
 */

namespace Commercetools\Symfony\CartBundle\Event;

use Commercetools\Core\Model\Payment\Payment;
use Symfony\Component\EventDispatcher\Event;

class PaymentPostCreateEvent extends Event
{
    private $payment;

    public function __construct(Payment $payment)
    {
        $this->payment = $payment;
    }

    /**
     * @return Payment
     */
    public function getPayment()
    {
        return $this->payment;
    }
}
