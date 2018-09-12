<?php
/**
 */

namespace Commercetools\Symfony\CartBundle\Event;

use Commercetools\Core\Model\Common\Resource;
use Commercetools\Core\Model\Payment\Payment;
use Symfony\Component\EventDispatcher\Event;

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
     * @var Resource
     */
    private $belongsTo;

    /**
     * PaymentPostCreateEvent constructor.
     * @param Payment $payment
     * @param Resource $belongsTo
     */
    public function __construct(Payment $payment, Resource $belongsTo = null)
    {
        $this->payment = $payment;
        $this->belongsTo = $belongsTo;
    }

    /**
     * @return Payment
     */
    public function getPayment()
    {
        return $this->payment;
    }

    /**
     * @return mixed
     */
    public function getBelongsTo()
    {
        return $this->belongsTo;
    }


}
