<?php
/**
 */

namespace Commercetools\Symfony\CustomerBundle\Tests\Manager;

use Commercetools\Core\Model\Customer\Customer;
use Commercetools\Core\Request\AbstractAction;
use Commercetools\Symfony\CustomerBundle\Event\CustomerUpdateEvent;
use Commercetools\Symfony\CustomerBundle\Event\CustomerPostUpdateEvent;
use Commercetools\Symfony\CustomerBundle\Manager\CustomerManager;
use Commercetools\Symfony\CustomerBundle\Model\Repository\CustomerRepository;
use Commercetools\Symfony\CustomerBundle\Model\CustomerUpdateBuilder;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class CustomerManagerTest extends TestCase
{

    public function testApply()
    {
        $customer = $this->prophesize(Customer::class);
        $repository = $this->prophesize(CustomerRepository::class);
        $dispatcher = $this->prophesize(EventDispatcherInterface::class);

        $repository->update($customer, Argument::type('array'))
            ->will(function ($args) { return $args[0]; })->shouldBeCalled();

        $dispatcher->dispatch(
            Argument::containingString(CustomerPostUpdateEvent::class),
            Argument::type(CustomerPostUpdateEvent::class)
        )->will(function ($args) { return $args[1]; })->shouldBeCalled();

        $manager = new CustomerManager($repository->reveal(), $dispatcher->reveal());
        $customer = $manager->apply($customer->reveal(), []);

        $this->assertInstanceOf(Customer::class, $customer);
    }

    public function testDispatch()
    {
        $customer = $this->prophesize(Customer::class);
        $repository = $this->prophesize(CustomerRepository::class);
        $dispatcher = $this->prophesize(EventDispatcherInterface::class);
        $dispatcher->dispatch(
            Argument::containingString(AbstractAction::class),
            Argument::type(CustomerUpdateEvent::class)
        )->will(function ($args) { return $args[1]; })->shouldBeCalled();
        $action = $this->prophesize(AbstractAction::class);

        $manager = new CustomerManager($repository->reveal(), $dispatcher->reveal());

        $actions = $manager->dispatch($customer->reveal(), $action->reveal());
        $this->assertInstanceOf(AbstractAction::class, current($actions));
        $this->assertCount(1, $actions);
    }

    public function testCreateCustomer()
    {

    }

    public function testUpdate()
    {
        $customer = $this->prophesize(Customer::class);
        $repository = $this->prophesize(CustomerRepository::class);
        $dispatcher = $this->prophesize(EventDispatcherInterface::class);

        $manager = new CustomerManager($repository->reveal(), $dispatcher->reveal());
        $this->assertInstanceOf(CustomerUpdateBuilder::class, $manager->update($customer->reveal()));

    }

    public function testGetById()
    {
        $repository = $this->prophesize(CustomerRepository::class);
        $dispatcher = $this->prophesize(EventDispatcherInterface::class);

        $repository->getCustomerById('en', '123456', null)
            ->willReturn(Customer::of())->shouldBeCalled();

        $manager = new CustomerManager($repository->reveal(), $dispatcher->reveal());
        $list = $manager->getById('en', '123456');

        $this->assertInstanceOf(Customer::class, $list);
    }
}
