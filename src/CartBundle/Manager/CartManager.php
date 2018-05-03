<?php
/**
 * Created by PhpStorm.
 * User: nsotiropoulos
 * Date: 02/05/2018
 * Time: 12:02
 */

namespace Commercetools\Symfony\CartBundle\Manager;

use Commercetools\Core\Model\Cart\Cart;
use Commercetools\Core\Request\AbstractAction;
use Commercetools\Symfony\CartBundle\Event\CartPostUpdateEvent;
use Commercetools\Symfony\CartBundle\Event\CartUpdateEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Commercetools\Symfony\CartBundle\Model\Repository\CartRepository;
use Commercetools\Symfony\CartBundle\Model\CartUpdateBuilder;

class CartManager
{
    /**
     * @var CartRepository
     */
    private $repository;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * CartManager constructor.
     * @param CartRepository $repository
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(CartRepository $repository, EventDispatcherInterface $dispatcher)
    {
        $this->repository = $repository;
        $this->dispatcher = $dispatcher;
    }

    public function getCart($locale, $cartId = null, $customerId = null)
    {
        return $this->repository->getCart($locale, $cartId, $customerId);
    }

    /**
     * @param Cart $cart
     * @return CartUpdateBuilder
     */
    public function update(Cart $cart)
    {
        return new CartUpdateBuilder($cart, $this);
    }

    public function dispatch(Cart $cart, AbstractAction $action, $eventName = null)
    {
        $eventName = is_null($eventName) ? get_class($action) : $eventName;

        $event = new CartUpdateEvent($cart, $action);
        $event = $this->dispatcher->dispatch($eventName, $event);

        return $event->getActions();
    }

    /**
     * @param Cart $cart
     * @param array $actions
     * @return Cart
     */
    public function apply(Cart $cart, array $actions)
    {
        $cart = $this->repository->update($cart, $actions);

        $this->dispatchPostUpdate($cart, $actions);

        return $cart;
    }

    public function dispatchPostUpdate(Cart $cart, array $actions)
    {
        $event = new CartPostUpdateEvent($cart, $actions);
        $event = $this->dispatcher->dispatch(CartPostUpdateEvent::class, $event);

        return $event->getActions();
    }
}
