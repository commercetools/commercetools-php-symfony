<?php
/**
 */

namespace Commercetools\Symfony\CartBundle\Manager;

use Commercetools\Core\Model\Cart\Cart;
use Commercetools\Core\Model\Order\Order;
use Commercetools\Core\Model\Order\OrderCollection;
use Commercetools\Symfony\CartBundle\Event\OrderCreateEvent;
use Commercetools\Symfony\CartBundle\Event\OrderPostCreateEvent;
use Commercetools\Symfony\CartBundle\Model\Repository\MeOrderRepository;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class MeOrderManager
{
    /**
     * @var MeOrderRepository
     */
    private $repository;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * OrderManager constructor.
     * @param MeOrderRepository $repository
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(MeOrderRepository $repository, EventDispatcherInterface $dispatcher)
    {
        $this->repository = $repository;
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param string $locale
     * @param string $orderId
     * @return Order
     */
    public function getOrderById($locale, $orderId)
    {
        return $this->repository->getOrderById($locale, $orderId);
    }

    /**
     * @param string $locale
     * @return OrderCollection
     */
    public function getOrdersForUser($locale)
    {
        return $this->repository->getOrders($locale);
    }

    /**
     * @param string $locale
     * @param string $paymentId
     * @return Order
     */
    public function getFirstOrderFromPayment($locale, $paymentId)
    {
        $orders = $this->repository->getOrdersFromPayment($locale, $paymentId);

        return $orders->current();
    }

    /**
     * @param string $locale
     * @param string $paymentId
     * @return OrderCollection
     */
    public function getOrdersFromPayment($locale, $paymentId)
    {
        return $this->repository->getOrdersFromPayment($locale, $paymentId);
    }

    /**
     * @param string $locale
     * @param Cart $cart
     * @return Order
     */
    public function createOrderFromCart($locale, Cart $cart)
    {
        $this->dispatcher->dispatch(new OrderCreateEvent());

        $order = $this->repository->createOrderFromCart($locale, $cart);

        $this->dispatcher->dispatch(new OrderPostCreateEvent());

        return $order;
    }
}
