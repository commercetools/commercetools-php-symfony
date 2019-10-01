<?php
/**
 */

namespace Commercetools\Symfony\CustomerBundle\Event;

use Commercetools\Core\Model\Customer\Customer;
use Commercetools\Core\Request\AbstractAction;
use Symfony\Contracts\EventDispatcher\Event;

class CustomerUpdateEvent extends Event
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
     * CustomerUpdateEvent constructor.
     * @param Customer $customer
     * @param AbstractAction $action
     */
    public function __construct(Customer $customer, AbstractAction $action)
    {
        $this->customer = $customer;
        $this->actions = [$action];
    }

    /**
     * @return Customer
     */
    public function getCustomer()
    {
        return $this->customer;
    }

    /**
     * @return AbstractAction[]
     */
    public function getActions()
    {
        return $this->actions;
    }

    /**
     * @param array $actions
     */
    public function setActions(array $actions)
    {
        $this->actions = $actions;
    }

    /**
     * @param AbstractAction $action
     */
    public function addAction(AbstractAction $action)
    {
        $this->actions[] = $action;
    }
}
