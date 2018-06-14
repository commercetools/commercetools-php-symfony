<?php
/**
 *
 */

namespace Commercetools\Symfony\CartBundle\Tests\Manager;


use Commercetools\Core\Model\Cart\Cart;
use Commercetools\Core\Model\Order\Order;
use Commercetools\Core\Model\Order\OrderCollection;
use Commercetools\Core\Request\AbstractAction;
use Commercetools\Symfony\CartBundle\Event\OrderPostUpdateEvent;
use Commercetools\Symfony\CartBundle\Event\OrderUpdateEvent;
use Commercetools\Symfony\CartBundle\Manager\OrderManager;
use Commercetools\Symfony\CartBundle\Model\OrderUpdateBuilder;
use Commercetools\Symfony\CartBundle\Model\Repository\OrderRepository;
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
            ->will(function ($args) { return $args[0]; })->shouldBeCalled();

        $dispatcher->dispatch(
            Argument::containingString(OrderPostUpdateEvent::class),
            Argument::type(OrderPostUpdateEvent::class)
        )->will(function ($args) { return $args[1]; })->shouldBeCalled();

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
        )->will(function ($args) { return $args[1]; })->shouldBeCalled();
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

        $repository->createOrderFromCart('en', $cart->reveal())
            ->willReturn(Order::of())->shouldBeCalled();

        $manager = new OrderManager($repository->reveal(), $dispatcher->reveal());
        $order = $manager->createOrderFromCart('en', $cart->reveal());

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

    public function testGetOrder()
    {
        $repository = $this->prophesize(OrderRepository::class);
        $dispatcher = $this->prophesize(EventDispatcherInterface::class);

        $repository->getOrder('en', '123', null)
            ->willReturn(Order::of())->shouldBeCalled();

        $manager = new OrderManager($repository->reveal(), $dispatcher->reveal());
        $order = $manager->getOrder('en', '123');

        $this->assertInstanceOf(Order::class, $order);
    }

    public function testGetOrders()
    {
        $repository = $this->prophesize(OrderRepository::class);
        $dispatcher = $this->prophesize(EventDispatcherInterface::class);

        $repository->getOrders('en', '123')
            ->willReturn(OrderCollection::of())->shouldBeCalled();

        $manager = new OrderManager($repository->reveal(), $dispatcher->reveal());
        $orders = $manager->getOrders('en', '123');

        $this->assertInstanceOf(OrderCollection::class, $orders);
    }
}
