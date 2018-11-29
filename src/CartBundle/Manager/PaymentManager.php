<?php
/**
 */

namespace Commercetools\Symfony\CartBundle\Manager;

use Commercetools\Core\Error\InvalidArgumentException;
use Commercetools\Core\Model\Common\Money;
use Commercetools\Core\Model\Customer\CustomerReference;
use Commercetools\Core\Model\Payment\Payment;
use Commercetools\Core\Model\Payment\PaymentCollection;
use Commercetools\Core\Model\Payment\PaymentStatus;
use Commercetools\Core\Request\AbstractAction;
use Commercetools\Symfony\CartBundle\Event\PaymentCreateEvent;
use Commercetools\Symfony\CartBundle\Event\PaymentPostCreateEvent;
use Commercetools\Symfony\CartBundle\Event\PaymentPostUpdateEvent;
use Commercetools\Symfony\CartBundle\Event\PaymentUpdateEvent;
use Commercetools\Symfony\CartBundle\Model\PaymentUpdateBuilder;
use Commercetools\Symfony\CartBundle\Model\Repository\PaymentRepository;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\User\UserInterface;

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
     * @param $locale
     * @param $paymentId
     * @return Payment
     */
    public function getPaymentById($locale, $paymentId)
    {
        return $this->repository->getPaymentById($locale, $paymentId);
    }

    /**
     * @param $locale
     * @param $paymentId
     * @param CustomerReference|null $customer
     * @param string|null $anonymousId
     * @return PaymentCollection
     */
    public function getPaymentForUser($locale, $paymentId, CustomerReference $customer = null, $anonymousId = null)
    {
        if (is_null($customer) && is_null($anonymousId)) {
            throw new InvalidArgumentException('At least one of `customer` or `anonymousId` should be present');
        }

        return $this->repository->getPayment($locale, $paymentId, $customer, $anonymousId);
    }

    /**
     * @param $locale
     * @param array $payments
     * @return array|mixed
     */
    public function getMultiplePayments($locale, array $payments)
    {
        return $this->repository->getMultiplePayments($locale, $payments);
    }

    /**
     * @param $locale
     * @param Money $amountPlanned
     * @param CustomerReference|null $customerReference
     * @param null $anonymousId
     * @param PaymentStatus|null $paymentStatus
     * @return Payment
     */
    public function createPaymentForUser(
        $locale,
        Money $amountPlanned,
        CustomerReference $customerReference = null,
        $anonymousId = null,
        PaymentStatus $paymentStatus = null
    ) {
        if (is_null($customerReference) && is_null($anonymousId)) {
            throw new InvalidArgumentException('At least one of `customerReference` or `anonymousId` should be present');
        }

        $event = new PaymentCreateEvent();
        $this->dispatcher->dispatch(PaymentCreateEvent::class, $event);

        $payment = $this->repository->createPayment($locale, $amountPlanned, $customerReference, $anonymousId, $paymentStatus);

        $eventPost = new PaymentPostCreateEvent($payment);
        $this->dispatcher->dispatch(PaymentPostCreateEvent::class, $eventPost);

        return $payment;
    }

    /**
     * @param Payment $payment
     * @return PaymentUpdateBuilder
     */
    public function update(Payment $payment)
    {
        return new PaymentUpdateBuilder($payment, $this);
    }

    /**
     * @param Payment $payment
     * @param AbstractAction $action
     * @param null $eventName
     * @return AbstractAction[]
     */
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

    /**
     * @param Payment $payment
     * @param array $actions
     * @return AbstractAction[]
     */
    public function dispatchPostUpdate(Payment $payment, array $actions)
    {
        $event = new PaymentPostUpdateEvent($payment, $actions);
        $event = $this->dispatcher->dispatch(PaymentPostUpdateEvent::class, $event);

        return $event->getActions();
    }
}
