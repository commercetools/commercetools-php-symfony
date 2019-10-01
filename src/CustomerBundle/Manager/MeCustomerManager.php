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
use Commercetools\Symfony\CustomerBundle\Model\CustomerUpdateBuilder;
use Commercetools\Symfony\CustomerBundle\Model\Repository\MeCustomerRepository;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class MeCustomerManager implements CustomerManagerInterface
{
    /**
     * @var MeCustomerRepository
     */
    private $repository;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * CustomerManager constructor.
     * @param MeCustomerRepository $repository
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(MeCustomerRepository $repository, EventDispatcherInterface $dispatcher)
    {
        $this->repository = $repository;
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param string $locale
     * @param QueryParams|null $params
     * @return Customer
     */
    public function getMeInfo($locale, QueryParams $params = null)
    {
        return $this->repository->getMeInfo($locale, $params);
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
        $event = $this->dispatcher->dispatch($event, $eventName);

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
        $event = $this->dispatcher->dispatch($event);

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
     * @return CustomerSigninResult
     */
    public function createCustomer($locale, $email, $password)
    {
        $this->dispatcher->dispatch(new CustomerCreateEvent());

        $customer = $this->repository->createCustomer($locale, $email, $password);

        $this->dispatcher->dispatch(new CustomerPostCreateEvent($customer->getCustomer()));

        return $customer;
    }
}
