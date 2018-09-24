<?php
/**
 *
 */

namespace Commercetools\Symfony\CustomerBundle\Event;

use Commercetools\Core\Model\Customer\Customer;
use Symfony\Component\EventDispatcher\Event;

class CustomerPostCreateEvent extends Event
{
    /**
     * @var Customer
     */
    private $customer;

    /**
     * CustomerPostCreateEvent constructor.
     * @param Customer $customer
     */
    public function __construct(Customer $customer)
    {
        $this->customer = $customer;
    }

    /**
     * @return Customer
     */
    public function getCustomer()
    {
        return $this->customer;
    }
}
