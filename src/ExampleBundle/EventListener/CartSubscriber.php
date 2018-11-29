<?php
/**
 *
 */

namespace Commercetools\Symfony\ExampleBundle\EventListener;


use Commercetools\Core\Request\AbstractAction;
use Commercetools\Symfony\CartBundle\Event\CartCreateEvent;
use Commercetools\Symfony\CartBundle\Event\CartGetEvent;
use Commercetools\Symfony\CartBundle\Event\CartNotFoundEvent;
use Commercetools\Symfony\CartBundle\Event\CartPostCreateEvent;
use Commercetools\Symfony\CartBundle\Event\CartPostUpdateEvent;
use Commercetools\Symfony\CartBundle\Event\CartUpdateEvent;
use Commercetools\Symfony\CartBundle\Model\Repository\CartRepository;
use Commercetools\Symfony\ShoppingListBundle\Manager\ShoppingListManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class CartSubscriber implements EventSubscriberInterface
{
    private $session;

    /**
     * @var ShoppingListManager
     */
    private $shoppingListManager;

    public function __construct(SessionInterface $session, ShoppingListManager $shoppingListManager = null)
    {
        $this->session = $session;
        $this->shoppingListManager = $shoppingListManager;
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

    /**
     * @param CartGetEvent $event
     */
    public function onCartGet(CartGetEvent $event)
    {
        $cart = $event->getCart();

        $this->session->set(CartRepository::CART_ID, $cart->getId());
        $this->session->set(CartRepository::CART_ITEM_COUNT, $cart->getLineItemCount());
    }

    /**
     * @param CartPostCreateEvent $event
     */
    public function onCartPostCreate(CartPostCreateEvent $event)
    {
        $cart = $event->getCart();

        $this->session->set(CartRepository::CART_ID, $cart->getId());
        $this->session->set(CartRepository::CART_ITEM_COUNT, $cart->getLineItemCount());
    }

    /**
     * @param CartPostUpdateEvent $event
     */
    public function onCartPostUpdate(CartPostUpdateEvent $event)
    {
        $cart = $event->getCart();

        $this->session->set(CartRepository::CART_ID, $cart->getId());
        $this->session->set(CartRepository::CART_ITEM_COUNT, $cart->getLineItemCount());

        $this->handleSpecificEvents($event);
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

    /**
     * @param CartPostUpdateEvent $event
     */
    private function handleSpecificEvents(CartPostUpdateEvent $event)
    {
        foreach ($event->getActions() as $action) {
            if (method_exists($this, $action->getAction())) {
                call_user_func([$this, $action->getAction()], $action);
            }
        }
    }

    /**
     * @param AbstractAction $action
     */
    private function addShoppingList(AbstractAction $action)
    {
        $shoppingList = $this->shoppingListManager->getById($action->getContext()->getLocale(), $action->getShoppingList()->getId());

        $this->shoppingListManager->deleteShoppingList($action->getContext()->getLocale(), $shoppingList);
    }

}
