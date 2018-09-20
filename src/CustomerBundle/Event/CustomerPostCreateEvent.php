<?php
/**
 *
 */

namespace Commercetools\Symfony\CustomerBundle\Event;

use Commercetools\Core\Model\Customer\Customer;
use Symfony\Component\EventDispatcher\Event;

class CustomerPostCreateEvent extends Event
{
    private $customer;

    public function __construct(Customer $customer)
    {
        $this->customer = $customer;
    }
}
