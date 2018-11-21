<?php
/**
 *
 */

namespace Commercetools\Symfony\ExampleBundle\EventListener;


use Commercetools\Core\Model\Cart\Cart;
use Commercetools\Symfony\CartBundle\Event\CartCreateEvent;
use Commercetools\Symfony\CartBundle\Event\CartGetEvent;
use Commercetools\Symfony\CartBundle\Event\CartNotFoundEvent;
use Commercetools\Symfony\CartBundle\Event\CartPostCreateEvent;
use Commercetools\Symfony\CartBundle\Event\CartPostUpdateEvent;
use Commercetools\Symfony\CartBundle\Event\CartUpdateEvent;
use Commercetools\Symfony\CartBundle\Model\Repository\CartRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class CartSubscriber implements EventSubscriberInterface
{
    private $session;

    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    public static function getSubscribedEvents()
    {
        return [
            CartCreateEvent::class => 'onCartCreate',
            CartPostCreateEvent::class => 'onCartPostCreate',
            CartUpdateEvent::class => 'onCartUpdate',
            CartPostUpdateEvent::class => 'onCartPostUpdate',
            CartGetEvent::class => 'onCartGet',
            CartNotFoundEvent::class => 'onCartNotFound'
        ];
    }

    public function onCartCreate()
    {
        return true;
    }

    public function onCartGet(CartGetEvent $event)
    {
        $cart = $event->getCart();

        $this->session->set(CartRepository::CART_ID, $cart->getId());
        $this->session->set(CartRepository::CART_ITEM_COUNT, $cart->getLineItemCount());
    }

    public function onCartPostCreate(CartPostCreateEvent $event)
    {
        $cart = $event->getCart();

        $this->session->set(CartRepository::CART_ID, $cart->getId());
        $this->session->set(CartRepository::CART_ITEM_COUNT, $cart->getLineItemCount());
    }

    public function onCartPostUpdate(CartPostUpdateEvent $event)
    {
        $cart = $event->getCart();

        $this->session->set(CartRepository::CART_ID, $cart->getId());
        $this->session->set(CartRepository::CART_ITEM_COUNT, $cart->getLineItemCount());
    }

    public function onCartNotFound()
    {
        $this->session->remove(CartRepository::CART_ID);
        $this->session->remove(CartRepository::CART_ITEM_COUNT);
    }

    public function onCartUpdate(CartUpdateEvent $event)
    {
        return true;
    }

}
