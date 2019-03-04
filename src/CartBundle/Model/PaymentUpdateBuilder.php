<?php
/**
 */

namespace Commercetools\Symfony\CartBundle\Model;


use Commercetools\Core\Builder\Update\PaymentsActionBuilder;
use Commercetools\Core\Model\Payment\Payment;
use Commercetools\Core\Request\AbstractAction;
use Commercetools\Symfony\CartBundle\Manager\PaymentManager;

class PaymentUpdateBuilder extends PaymentsActionBuilder
{
    /**
     * @var PaymentManager
     */
    private $manager;

    /**
     * @var Payment
     */
    private $payment;

    /**
     * PaymentUpdate constructor.
     * @param PaymentManager $manager
     * @param Payment $payment
     */
    public function __construct(Payment $payment, PaymentManager $manager)
    {
        $this->manager = $manager;
        $this->payment = $payment;
    }

    /**
     * @param AbstractAction $action
     * @param string|null $eventName
     * @return $this
     */
    public function addAction(AbstractAction $action, $eventName = null)
    {
        $actions = $this->manager->dispatch($this->payment, $action, $eventName);

        $this->setActions(array_merge($this->getActions(), $actions));

        return $this;
    }

    /**
     * @return Payment
     */
    public function flush()
    {
        return $this->manager->apply($this->payment, $this->getActions());
    }
}
