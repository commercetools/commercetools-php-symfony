<?php
/**
 */

namespace Commercetools\Symfony\CustomerBundle\Model;


use Commercetools\Core\Builder\Update\CustomersActionBuilder;
use Commercetools\Core\Model\Customer\Customer;
use Commercetools\Core\Request\AbstractAction;
use Commercetools\Symfony\CustomerBundle\Manager\CustomerManager;

class CustomerUpdateBuilder extends CustomersActionBuilder
{
    /**
     * @var CustomerManager
     */
    private $manager;

    /**
     * @var Customer
     */
    private $customer;

    /**
     * CustomerUpdate constructor.
     * @param CustomerManager $manager
     * @param Customer $customer
     */
    public function __construct(Customer $customer, CustomerManager $manager)
    {
        $this->manager = $manager;
        $this->customer = $customer;
    }


    public function addAction(AbstractAction $action, $eventName = null)
    {
        $actions = $this->manager->dispatch($this->customer, $action, $eventName);

        $this->setActions(array_merge($this->getActions(), $actions));

        return $this;
    }

    /**
     * @return Customer
     */
    public function flush()
    {
        return $this->manager->apply($this->customer, $this->getActions());
    }
}
