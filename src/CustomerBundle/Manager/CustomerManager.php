<?php
/**
 */

namespace Commercetools\Symfony\CustomerBundle\Manager;

use Commercetools\Core\Model\Customer\CustomerSigninResult;
use Commercetools\Core\Request\AbstractAction;
use Commercetools\Core\Model\Customer\Customer;
use Commercetools\Symfony\CtpBundle\Model\QueryParams;
use Commercetools\Symfony\CustomerBundle\Event\CustomerCreateEvent;
use Commercetools\Symfony\CustomerBundle\Event\CustomerPostCreateEvent;
use Commercetools\Symfony\CustomerBundle\Event\CustomerUpdateEvent;
use Commercetools\Symfony\CustomerBundle\Event\CustomerPostUpdateEvent;
use Commercetools\Symfony\CustomerBundle\Model\Repository\CustomerRepository;
use Commercetools\Symfony\CustomerBundle\Model\CustomerUpdateBuilder;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class CustomerManager
{
    /**
     * @var CustomerRepository
     */
    private $repository;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * CustomerManager constructor.
     * @param CustomerRepository $repository
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(CustomerRepository $repository, EventDispatcherInterface $dispatcher)
    {
        $this->repository = $repository;
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param string $locale
     * @param string $customerId
     * @param QueryParams|null $params
     * @return Customer
     */
    public function getById($locale, $customerId, QueryParams $params = null)
    {
        return $this->repository->getCustomerById($locale, $customerId, $params);
    }

    /**
     * @param Customer $customer
     * @return CustomerUpdateBuilder
     */
    public function update(Customer $customer)
    {
        return new CustomerUpdateBuilder($customer, $this);
    }

    /**
     * @param Customer $customer
     * @param AbstractAction $action
     * @param string|null $eventName
     * @return AbstractAction[]
     */
    public function dispatch(Customer $customer, AbstractAction $action, $eventName = null)
    {
        $eventName = is_null($eventName) ? get_class($action) : $eventName;

        $event = new CustomerUpdateEvent($customer, $action);
        $event = $this->dispatcher->dispatch($eventName, $event);

        return $event->getActions();
    }

    /**
     * @param Customer $customer
     * @param array $actions
     * @return Customer
     */
    public function apply(Customer $customer, array $actions)
    {
        $customer = $this->repository->update($customer, $actions);

        return $this->dispatchPostUpdate($customer, $actions);
    }

    /**
     * @param Customer $customer
     * @param array $actions
     * @return Customer
     */
    public function dispatchPostUpdate(Customer $customer, array $actions)
    {
        $event = new CustomerPostUpdateEvent($customer, $actions);
        $event = $this->dispatcher->dispatch(CustomerPostUpdateEvent::class, $event);

        return $event->getCustomer();
    }

    /**
     * @param Customer $customer
     * @param string $currentPassword
     * @param string $newPassword
     * @return Customer
     */
    public function changePassword(Customer $customer, $currentPassword, $newPassword)
    {
        return $this->repository->changePassword($customer, $currentPassword, $newPassword);
    }

    /**
     * @param string $locale
     * @param string $email
     * @param string $password
     * @param SessionInterface|null $session
     * @return CustomerSigninResult
     */
    public function createCustomer($locale, $email, $password, SessionInterface $session = null)
    {
        $event = new CustomerCreateEvent();
        $this->dispatcher->dispatch(CustomerCreateEvent::class, $event);

        $customer = $this->repository->createCustomer($locale, $email, $password, $session);

        $eventPost = new CustomerPostCreateEvent($customer->getCustomer());
        $this->dispatcher->dispatch(CustomerPostCreateEvent::class, $eventPost);

        return $customer;
    }
}
