<?php
/**
 */

namespace Commercetools\Symfony\CartBundle\Event;

use Commercetools\Core\Model\Payment\Payment;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Class PaymentPostCreateEvent
 * @package Commercetools\Symfony\CartBundle\Event
 */
class PaymentPostCreateEvent extends Event
{
    /**
     * @var Payment
     */
    private $payment;

    /**
     * PaymentPostCreateEvent constructor.
     * @param Payment $payment
     */
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
