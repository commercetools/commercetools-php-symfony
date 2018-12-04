<?php
/**
 *
 */

namespace Commercetools\Symfony\CustomerBundle\Tests\Event;


use Commercetools\Core\Model\Customer\Customer;
use Commercetools\Symfony\CustomerBundle\Event\CustomerPostCreateEvent;
use PHPUnit\Framework\TestCase;

class CustomerPostCreateEventTest extends TestCase
{
    public function testCustomerPostCreateEvent()
    {
        $event = new CustomerPostCreateEvent(Customer::of());
        $this->assertInstanceOf(Customer::class, $event->getCustomer());
    }
}
