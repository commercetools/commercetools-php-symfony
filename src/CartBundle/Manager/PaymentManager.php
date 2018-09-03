<?php
/**
 */

namespace Commercetools\Symfony\CartBundle\Manager;

use Commercetools\Core\Model\Payment\Payment;
use Commercetools\Core\Request\AbstractAction;
use Commercetools\Symfony\CartBundle\Event\PaymentUpdateEvent;
use Commercetools\Symfony\CartBundle\Event\PaymentPostUpdateEvent;
use Commercetools\Symfony\CartBundle\Model\PaymentUpdateBuilder;
use Commercetools\Symfony\CartBundle\Model\Repository\PaymentRepository;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class PaymentManager
{
    /**
     * @var PaymentRepository
     */
    private $repository;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * PaymentManager constructor.
     * @param PaymentRepository $repository
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(PaymentRepository $repository, EventDispatcherInterface $dispatcher)
    {
        $this->repository = $repository;
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param Payment $payment
     * @return PaymentUpdateBuilder
     */
    public function update(Payment $payment)
    {
        return new PaymentUpdateBuilder($payment, $this);
    }

    public function dispatch(Payment $payment, AbstractAction $action, $eventName = null)
    {
        $eventName = is_null($eventName) ? get_class($action) : $eventName;

        $event = new PaymentUpdateEvent($payment, $action);
        $event = $this->dispatcher->dispatch($eventName, $event);

        return $event->getActions();
    }

    /**
     * @param Payment $payment
     * @param array $actions
     * @return Payment
     */
    public function apply(Payment $payment, array $actions)
    {
        $payment = $this->repository->update($payment, $actions);

        $this->dispatchPostUpdate($payment, $actions);

        return $payment;
    }

    public function dispatchPostUpdate(Payment $payment, array $actions)
    {
        $event = new PaymentPostUpdateEvent($payment, $actions);
        $event = $this->dispatcher->dispatch(PaymentPostUpdateEvent::class, $event);

        return $event->getActions();
    }
}
