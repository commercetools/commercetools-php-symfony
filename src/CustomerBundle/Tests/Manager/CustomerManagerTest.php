<?php
/**
 */

namespace Commercetools\Symfony\CustomerBundle\Tests\Manager;

use Commercetools\Core\Model\Customer\Customer;
use Commercetools\Core\Request\AbstractAction;
use Commercetools\Core\Request\Customers\Command\CustomerSetKeyAction;
use Commercetools\Symfony\CustomerBundle\Event\CustomerCreateEvent;
use Commercetools\Symfony\CustomerBundle\Event\CustomerPostCreateEvent;
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
    private $repository;
    private $dispatcher;

    public function setUp()
    {
        $this->repository = $this->prophesize(CustomerRepository::class);
        $this->dispatcher = $this->prophesize(EventDispatcherInterface::class);
    }

    public function testApply()
    {
        $this->repository->update(Customer::of(), Argument::type('array'))
            ->will(function ($args) {
                return $args[0];
            })->shouldBeCalled();

        $this->dispatcher->dispatch(
            Argument::containingString(CustomerPostUpdateEvent::class),
            Argument::type(CustomerPostUpdateEvent::class)
        )->will(function ($args) {
            return $args[1];
        })->shouldBeCalled();

        $manager = new CustomerManager($this->repository->reveal(), $this->dispatcher->reveal());
        $customer = $manager->apply(Customer::of(), []);

        $this->assertInstanceOf(Customer::class, $customer);
    }

    public function testDispatch()
    {
        $this->dispatcher->dispatch(
            Argument::containingString(CustomerSetKeyAction::class),
            Argument::type(CustomerUpdateEvent::class)
        )->will(function ($args) {
            return $args[1];
        })->shouldBeCalled();

        $action = CustomerSetKeyAction::of()->setKey('bar');
        $manager = new CustomerManager($this->repository->reveal(), $this->dispatcher->reveal());

        $actions = $manager->dispatch(Customer::of(), $action);
        $this->assertCount(1, $actions);

        $current = current($actions);
        $this->assertInstanceOf(CustomerSetKeyAction::class, $current);
        $this->assertSame('bar', $action->getKey());
    }

    public function testCreateCustomer()
    {
        $this->repository->createCustomer('en', 'user@localhost', 'password', null)
            ->willReturn(Customer::of())->shouldBeCalled();

        $this->dispatcher->dispatch(CustomerCreateEvent::class, Argument::type(CustomerCreateEvent::class))
            ->shouldBeCalledOnce();

        $this->dispatcher->dispatch(CustomerPostCreateEvent::class, Argument::type(CustomerPostCreateEvent::class))
            ->shouldBeCalledOnce();

        $manager = new CustomerManager($this->repository->reveal(), $this->dispatcher->reveal());
        $customer = $manager->createCustomer('en', 'user@localhost', 'password');

        $this->assertInstanceOf(Customer::class, $customer);
    }

    public function testChangePassword()
    {
        $customer = Customer::of()->setId('user-1');
        $this->repository->changePassword($customer, 'current-password', 'new-password')
            ->willReturn($customer)->shouldBeCalledOnce();

        $manager = new CustomerManager($this->repository->reveal(), $this->dispatcher->reveal());
        $updated = $manager->changePassword($customer, 'current-password', 'new-password');

        $this->assertInstanceOf(Customer::class, $updated);
        $this->assertSame('user-1', $updated->getId());
    }

    public function testUpdate()
    {
        $manager = new CustomerManager($this->repository->reveal(), $this->dispatcher->reveal());
        $this->assertInstanceOf(CustomerUpdateBuilder::class, $manager->update(Customer::of()));
    }

    public function testGetById()
    {
        $this->repository->getCustomerById('en', 'customer-1', null)
            ->willReturn(Customer::of()->setId('customer-1'))->shouldBeCalled();

        $manager = new CustomerManager($this->repository->reveal(), $this->dispatcher->reveal());
        $customer = $manager->getById('en', 'customer-1');

        $this->assertInstanceOf(Customer::class, $customer);
        $this->assertSame('customer-1', $customer->getId());
    }
}
