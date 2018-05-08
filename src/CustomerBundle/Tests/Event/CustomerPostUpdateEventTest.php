<?php
/**
 *
 */

namespace Commercetools\Symfony\CustomerBundle\Tests\Event;


use Commercetools\Core\Model\Customer\Customer;
use Commercetools\Symfony\CustomerBundle\Event\CustomerPostUpdateEvent;

class CustomerPostUpdateEventTest extends \PHPUnit_Framework_TestCase
{
    public function testCustomerPostUpdateEvent()
    {
        $customer = $this->prophesize(Customer::class);
        $action = $this->prophesize(CustomerSetFirstNameAction::class);
        $secondCustomer = $this->prophesize(Customer::class);

        $postUpdateEvent = new CustomerPostUpdateEvent($customer->reveal(), [$action->reveal()]);
        $postUpdateEvent->setCustomer($secondCustomer->reveal());

        $this->assertNotSame($customer->reveal(),$secondCustomer->reveal());
        $this->assertSame($secondCustomer->reveal(), $postUpdateEvent->getCustomer());
        $this->assertNotSame($customer->reveal(), $postUpdateEvent->getCustomer());

        $this->assertEquals([$action->reveal()], $postUpdateEvent->getActions());
    }
}
