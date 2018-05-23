<?php
/**
 */

namespace Commercetools\Symfony\CartBundle\Manager;

use Commercetools\Core\Model\Order\Order;
use Commercetools\Core\Request\AbstractAction;
use Commercetools\Symfony\CartBundle\Event\OrderCreateEvent;
use Commercetools\Symfony\CartBundle\Event\OrderPostCreateEvent;
use Commercetools\Symfony\CartBundle\Event\OrderPostUpdateEvent;
use Commercetools\Symfony\CartBundle\Event\OrderUpdateEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Commercetools\Symfony\CartBundle\Model\Repository\OrderRepository;
use Commercetools\Symfony\CartBundle\Model\OrderUpdateBuilder;

class OrderManager
{
    /**
     * @var OrderRepository
     */
    private $repository;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * OrderManager constructor.
     * @param OrderRepository $repository
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(OrderRepository $repository, EventDispatcherInterface $dispatcher)
    {
        $this->repository = $repository;
        $this->dispatcher = $dispatcher;
    }

    public function getOrders($locale, $customerId)
    {
        return $this->repository->getOrders($locale, $customerId);
    }

    public function getOrder($locale, $orderId)
    {
        return $this->repository->getOrder($locale, $orderId);
    }

    public function createOrderFromCart($locale, $cart)
    {
        $event = new OrderCreateEvent();
        $this->dispatcher->dispatch(OrderCreateEvent::class, $event);

        $order = $this->repository->createOrderFromCart($locale, $cart);

        $eventPost = new OrderPostCreateEvent();
        $this->dispatcher->dispatch(OrderPostCreateEvent::class, $eventPost);

        return $order;
    }

    /**
     * @param Order $order
     * @return OrderUpdateBuilder
     */
    public function update(Order $order)
    {
        return new OrderUpdateBuilder($order, $this);
    }

    public function dispatch(Order $order, AbstractAction $action, $eventName = null)
    {
        $eventName = is_null($eventName) ? get_class($action) : $eventName;

        $event = new OrderUpdateEvent($order, $action);
        $event = $this->dispatcher->dispatch($eventName, $event);

        return $event->getActions();
    }

    /**
     * @param Order $order
     * @param array $actions
     * @return Order
     */
    public function apply(Order $order, array $actions)
    {
        $order = $this->repository->update($order, $actions);

        $this->dispatchPostUpdate($order, $actions);

        return $order;
    }

    public function dispatchPostUpdate(Order $order, array $actions)
    {
        $event = new OrderPostUpdateEvent($order, $actions);
        $event = $this->dispatcher->dispatch(OrderPostUpdateEvent::class, $event);

        return $event->getActions();
    }
}
