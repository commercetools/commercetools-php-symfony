<?php
/**
 */

namespace Commercetools\Symfony\CustomerBundle\Event;

use Commercetools\Core\Model\Customer\Customer;
use Commercetools\Core\Request\AbstractAction;
use Symfony\Contracts\EventDispatcher\Event;

class CustomerPostUpdateEvent extends Event
{
    /**
     * @var Customer
     */
    private $customer;

    /**
     * @var AbstractAction[]
     */
    private $actions;

    /**
     * CustomerPostUpdateEvent constructor.
     * @param Customer $customer
     * @param array $actions
     */
    public function __construct(Customer $customer, array $actions)
    {
        $this->customer = $customer;
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
     * @return Customer
     */
    public function getCustomer()
    {
        return $this->customer;
    }

    /**
     * @param Customer $customer
     */
    public function setCustomer(Customer $customer)
    {
        $this->customer = $customer;
    }
}
