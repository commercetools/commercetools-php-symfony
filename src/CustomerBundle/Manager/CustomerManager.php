<?php
/**
 * Created by PhpStorm.
 * User: nsotiropoulos
 * Date: 17/04/2018
 * Time: 16:39
 */

namespace Commercetools\Symfony\CustomerBundle\Manager;


use Commercetools\Core\Model\Common\Address;
use Commercetools\Core\Request\AbstractAction;
use Commercetools\Core\Model\Customer\Customer;
use Commercetools\Symfony\CtpBundle\Model\QueryParams;
use Commercetools\Symfony\CustomerBundle\Event\CustomerUpdateEvent;
use Commercetools\Symfony\CustomerBundle\Event\CustomerPostUpdateEvent;
use Commercetools\Symfony\CustomerBundle\Model\Repository\CustomerRepository;
use Commercetools\Symfony\CustomerBundle\Model\CustomerUpdateBuilder;
use Commercetools\Symfony\ExampleBundle\Model\Form\Type\AddressType;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Commercetools\Core\Model\Customer\CustomerReference;

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
     * ShoppingListManager constructor.
     * @param CustomerRepository $repository
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(CustomerRepository $repository, EventDispatcherInterface $dispatcher)
    {
        $this->repository = $repository;
        $this->dispatcher = $dispatcher;
    }

    public function getById($locale, $customerId, QueryParams $params = null)
    {
        return $this->repository->getCustomerById($locale, $customerId, $params);
    }

    public function setAddress($locale, Customer $customer, Address $address, $addressId)
    {
        return $this->repository->setAddress($locale, $customer, $address, $addressId);

    }

    /**
     * @param Customer $customer
     * @return CustomerUpdateBuilder
     */
    public function update(Customer $customer)
    {
        return new CustomerUpdateBuilder($customer, $this);
    }

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

        $this->dispatchPostUpdate($customer, $actions);

        return $customer;
    }

    public function dispatchPostUpdate(Customer $customer, array $actions)
    {
        $event = new CustomerPostUpdateEvent($customer, $actions);
        $event = $this->dispatcher->dispatch(CustomerPostUpdateEvent::class, $event);

        return $event->getActions();
    }
}
