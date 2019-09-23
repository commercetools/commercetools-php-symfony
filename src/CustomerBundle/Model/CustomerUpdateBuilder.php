<?php
/**
 */

namespace Commercetools\Symfony\CustomerBundle\Model;

use Commercetools\Core\Builder\Update\CustomersActionBuilder;
use Commercetools\Core\Model\Customer\Customer;
use Commercetools\Core\Request\AbstractAction;
use Commercetools\Symfony\CustomerBundle\Manager\CustomerManager;
use Commercetools\Symfony\CustomerBundle\Manager\CustomerManagerInterface;

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
     * @param Customer $customer
     * @param CustomerManagerInterface $manager
     */
    public function __construct(Customer $customer, CustomerManagerInterface $manager)
    {
        $this->manager = $manager;
        $this->customer = $customer;
    }

    /**
     * @param AbstractAction $action
     * @param string|null $eventName
     * @return $this
     */
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
