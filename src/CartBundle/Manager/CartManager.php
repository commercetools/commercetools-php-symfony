<?php
/**
 */

namespace Commercetools\Symfony\CartBundle\Manager;

use Commercetools\Core\Error\InvalidArgumentException;
use Commercetools\Core\Model\Cart\Cart;
use Commercetools\Core\Model\Cart\LineItemDraftCollection;
use Commercetools\Core\Model\Zone\Location;
use Commercetools\Core\Request\AbstractAction;
use Commercetools\Symfony\CartBundle\Event\CartCreateEvent;
use Commercetools\Symfony\CartBundle\Event\CartGetEvent;
use Commercetools\Symfony\CartBundle\Event\CartPostCreateEvent;
use Commercetools\Symfony\CartBundle\Event\CartPostUpdateEvent;
use Commercetools\Symfony\CartBundle\Event\CartNotFoundEvent;
use Commercetools\Symfony\CartBundle\Event\CartUpdateEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Commercetools\Symfony\CartBundle\Model\Repository\CartRepository;
use Commercetools\Symfony\CartBundle\Model\CartUpdateBuilder;
use Symfony\Component\Security\Core\User\UserInterface;

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

    /**
     * @param string $locale
     * @param string|null $cartId
     * @param UserInterface|null $user
     * @param string|null $anonymousId
     * @return Cart|null
     */
    public function getCart($locale, $cartId = null, UserInterface $user = null, $anonymousId = null)
    {
        $cart = $this->repository->getCart($locale, $cartId, $user, $anonymousId);

        $this->dispatchPostGet($cart);

        return $cart;
    }

    /**
     * @param string $locale
     * @param string $currency
     * @param Location $location
     * @param LineItemDraftCollection|null $lineItemDraftCollection
     * @param string|null $customerId
     * @param string|null $anonymousId
     * @return Cart|null
     */
    public function createCartForUser($locale, $currency, Location $location, LineItemDraftCollection $lineItemDraftCollection = null, $customerId = null, $anonymousId = null)
    {
        if (is_null($customerId) && is_null($anonymousId)) {
            throw new InvalidArgumentException('At least one of `customerId` or `anonymousId` should be present');
        }

        $event = new CartCreateEvent();
        $this->dispatcher->dispatch(CartCreateEvent::class, $event);

        $cart = $this->repository->createCart($locale, $currency, $location, $lineItemDraftCollection, $customerId, $anonymousId);

        $eventPost = new CartPostCreateEvent($cart);
        $this->dispatcher->dispatch(CartPostCreateEvent::class, $eventPost);

        return $cart;
    }

    /**
     * @param Cart $cart
     * @return CartUpdateBuilder
     */
    public function update(Cart $cart)
    {
        return new CartUpdateBuilder($cart, $this);
    }

    /**
     * @param Cart $cart
     * @param AbstractAction $action
     * @param string|null $eventName
     * @return AbstractAction[]
     */
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

    /**
     * @param Cart $cart
     * @param array $actions
     * @return AbstractAction[]
     */
    public function dispatchPostUpdate(Cart $cart, array $actions)
    {
        $event = new CartPostUpdateEvent($cart, $actions);
        $event = $this->dispatcher->dispatch(CartPostUpdateEvent::class, $event);

        return $event->getActions();
    }

    /**
     * @param Cart|null $cart
     */
    public function dispatchPostGet(Cart $cart = null)
    {
        if (is_null($cart)){
            $event = new CartNotFoundEvent();
            $this->dispatcher->dispatch(CartNotFoundEvent::class, $event);
        } else {
            $event = new CartGetEvent($cart);
            $this->dispatcher->dispatch(CartGetEvent::class, $event);
        }
    }
}
