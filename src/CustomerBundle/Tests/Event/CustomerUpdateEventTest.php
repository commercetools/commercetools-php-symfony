<?php
/**
 *
 */

namespace Commercetools\Symfony\CustomerBundle\Tests\Event;


use Commercetools\Core\Model\Customer\Customer;
use Commercetools\Core\Request\Customers\Command\CustomerSetCustomTypeAction;
use Commercetools\Core\Request\Customers\Command\CustomerSetFirstNameAction;
use Commercetools\Symfony\CustomerBundle\Event\CustomerUpdateEvent;

class CustomerUpdateEventTest extends \PHPUnit_Framework_TestCase
{
    public function testCustomerUpdateEvent()
    {
        $customer = $this->prophesize(Customer::class);
        $action = $this->prophesize(CustomerSetFirstNameAction::class);
        $secondAction = $this->prophesize(CustomerSetCustomTypeAction::class);

        $updateEvent = new CustomerUpdateEvent($customer->reveal(), $action->reveal());

        $this->assertInstanceOf(Customer::class, $updateEvent->getCustomer());
        $this->assertEquals([$action->reveal()], $updateEvent->getActions());

        $updateEvent->addAction($secondAction->reveal());

        $this->assertEquals([$action->reveal(), $secondAction->reveal()], $updateEvent->getActions());

        $updateEvent->setActions([$secondAction->reveal()]);

        $this->assertEquals([$secondAction->reveal()], $updateEvent->getActions());
    }

}
