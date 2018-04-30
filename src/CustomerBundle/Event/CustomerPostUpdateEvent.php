<?php
/**
 * Created by PhpStorm.
 * User: nsotiropoulos
 * Date: 17/04/2018
 * Time: 15:13
 */

namespace Commercetools\Symfony\CustomerBundle\Event;

use Commercetools\Core\Model\Customer\Customer;
use Commercetools\Core\Request\AbstractAction;
use Symfony\Component\EventDispatcher\Event;

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
}
