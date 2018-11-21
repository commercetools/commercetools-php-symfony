<?php
/**
 */

namespace Commercetools\Symfony\CartBundle\Event;

use Commercetools\Core\Model\Payment\Payment;
use Commercetools\Core\Request\AbstractAction;
use Symfony\Component\EventDispatcher\Event;

class PaymentPostUpdateEvent extends Event
{
    /**
     * @var Payment
     */
    private $payment;

    /**
     * @var AbstractAction[]
     */
    private $actions;

    public function __construct(Payment $payment, array $actions)
    {
        $this->payment = $payment;
        $this->actions = $actions;
    }

    /**
     * @return AbstractAction[]
     */
    public function getActions()
    {
        return $this->actions;
    }

    /**
     * @return Payment
     */
    public function getPayment()
    {
        return $this->payment;
    }
}
