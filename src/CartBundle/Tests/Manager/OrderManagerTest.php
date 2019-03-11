<?php
/**
 *
 */

namespace Commercetools\Symfony\CartBundle\Tests\Manager;

use Commercetools\Core\Error\InvalidArgumentException;
use Commercetools\Core\Model\Cart\Cart;
use Commercetools\Core\Model\Order\Order;
use Commercetools\Core\Model\Order\OrderCollection;
use Commercetools\Core\Model\State\StateReference;
use Commercetools\Core\Request\AbstractAction;
use Commercetools\Symfony\CartBundle\Event\OrderPostUpdateEvent;
use Commercetools\Symfony\CartBundle\Event\OrderUpdateEvent;
use Commercetools\Symfony\CartBundle\Manager\OrderManager;
use Commercetools\Symfony\CartBundle\Model\OrderUpdateBuilder;
use Commercetools\Symfony\CartBundle\Model\Repository\OrderRepository;
use Commercetools\Symfony\CustomerBundle\Security\User\User;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class OrderManagerTest extends TestCase
{
    public function testApply()
    {
        $order = $this->prophesize(Order::class);
        $repository = $this->prophesize(OrderRepository::class);
        $dispatcher = $this->prophesize(EventDispatcherInterface::class);

        $repository->update($order, Argument::type('array'))
            ->will(function ($args) {
                return $args[0];
            })->shouldBeCalled();

        $dispatcher->dispatch(
            Argument::containingString(OrderPostUpdateEvent::class),
            Argument::type(OrderPostUpdateEvent::class)
        )->will(function ($args) {
            return $args[1];
        })->shouldBeCalled();

        $manager = new OrderManager($repository->reveal(), $dispatcher->reveal());
        $order = $manager->apply($order->reveal(), []);

        $this->assertInstanceOf(Order::class, $order);
    }

    public function testDispatch()
    {
        $order = $this->prophesize(Order::class);
        $repository = $this->prophesize(OrderRepository::class);
        $dispatcher = $this->prophesize(EventDispatcherInterface::class);
        $dispatcher->dispatch(
            Argument::containingString(AbstractAction::class),
            Argument::type(OrderUpdateEvent::class)
        )->will(function ($args) {
            return $args[1];
        })->shouldBeCalled();
        $action = $this->prophesize(AbstractAction::class);

        $manager = new OrderManager($repository->reveal(), $dispatcher->reveal());

        $actions = $manager->dispatch($order->reveal(), $action->reveal());
        $this->assertInstanceOf(AbstractAction::class, current($actions));
        $this->assertCount(1, $actions);
    }

    public function testCreateOrderFromCart()
    {
        $repository = $this->prophesize(OrderRepository::class);
        $dispatcher = $this->prophesize(EventDispatcherInterface::class);
        $cart = $this->prophesize(Cart::class);
        $stateReference = $this->prophesize(StateReference::class);

        $repository->createOrderFromCart('en', $cart->reveal(), $stateReference->reveal())
            ->willReturn(Order::of())->shouldBeCalled();

        $manager = new OrderManager($repository->reveal(), $dispatcher->reveal());
        $order = $manager->createOrderFromCart('en', $cart->reveal(), $stateReference->reveal());

        $this->assertInstanceOf(Order::class, $order);
    }

    public function testUpdate()
    {
        $order = $this->prophesize(Order::class);
        $repository = $this->prophesize(OrderRepository::class);
        $dispatcher = $this->prophesize(EventDispatcherInterface::class);

        $manager = new OrderManager($repository->reveal(), $dispatcher->reveal());
        $this->assertInstanceOf(OrderUpdateBuilder::class, $manager->update($order->reveal()));
    }

    public function testGetOrderForCustomer()
    {
        $repository = $this->prophesize(OrderRepository::class);
        $dispatcher = $this->prophesize(EventDispatcherInterface::class);
        $user = $this->prophesize(User::class);

        $repository->getOrder('en', 'order-id-1', $user->reveal(), null)
            ->willReturn(Order::of())->shouldBeCalled();

        $manager = new OrderManager($repository->reveal(), $dispatcher->reveal());
        $order = $manager->getOrderForUser('en', 'order-id-1', $user->reveal());

        $this->assertInstanceOf(Order::class, $order);
    }

    public function testGetOrderForAnonymous()
    {
        $repository = $this->prophesize(OrderRepository::class);
        $dispatcher = $this->prophesize(EventDispatcherInterface::class);

        $repository->getOrder('en', 'order-id-1', null, 'anonymous-id-1')
            ->willReturn(Order::of())->shouldBeCalled();

        $manager = new OrderManager($repository->reveal(), $dispatcher->reveal());
        $order = $manager->getOrderForUser('en', 'order-id-1', null, 'anonymous-id-1');

        $this->assertInstanceOf(Order::class, $order);
    }

    public function testGetOrdersForCustomer()
    {
        $repository = $this->prophesize(OrderRepository::class);
        $dispatcher = $this->prophesize(EventDispatcherInterface::class);
        $user = $this->prophesize(User::class);

        $repository->getOrders('en', $user->reveal(), null)
            ->willReturn(OrderCollection::of())->shouldBeCalled();

        $manager = new OrderManager($repository->reveal(), $dispatcher->reveal());
        $orders = $manager->getOrdersForUser('en', $user->reveal());

        $this->assertInstanceOf(OrderCollection::class, $orders);
    }

    public function testGetOrdersFromPaymentForUser()
    {
        /** @var OrderRepository $repository */
        $repository = $this->prophesize(OrderRepository::class);
        $dispatcher = $this->prophesize(EventDispatcherInterface::class);
        $user = $this->prophesize(User::class);

        $repository->getOrdersFromPayment('en', 'payment-1', Argument::type(User::class), null)
            ->willReturn(OrderCollection::of()->add(Order::of()->setId('order-1')))->shouldBeCalled();

        $manager = new OrderManager($repository->reveal(), $dispatcher->reveal());
        $order = $manager->getFirstOrderFromPayment('en', 'payment-1', $user->reveal());

        $this->assertInstanceOf(Order::class, $order);
        $this->assertSame('order-1', $order->getId());
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testGetOrderFromPaymentWithoutUser()
    {
        /** @var OrderRepository $repository */
        $repository = $this->prophesize(OrderRepository::class);
        $dispatcher = $this->prophesize(EventDispatcherInterface::class);

        $repository->getOrdersFromPayment('en', 'payment-1')->shouldNotBeCalled();

        $manager = new OrderManager($repository->reveal(), $dispatcher->reveal());
        $manager->getFirstOrderFromPayment('en', 'payment-1');
    }
}
