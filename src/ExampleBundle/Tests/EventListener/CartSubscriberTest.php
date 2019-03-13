<?php
/**
 *
 */

namespace Commercetools\Symfony\ExampleBundle\Tests\EventListener;

use Commercetools\Core\Model\Cart\Cart;
use Commercetools\Core\Model\ShoppingList\ShoppingList;
use Commercetools\Core\Model\ShoppingList\ShoppingListReference;
use Commercetools\Core\Request\Carts\Command\CartAddShoppingListAction;
use Commercetools\Core\Request\Carts\Command\CartSetCountryAction;
use Commercetools\Symfony\CartBundle\Event\CartCreateEvent;
use Commercetools\Symfony\CartBundle\Event\CartGetEvent;
use Commercetools\Symfony\CartBundle\Event\CartNotFoundEvent;
use Commercetools\Symfony\CartBundle\Event\CartPostCreateEvent;
use Commercetools\Symfony\CartBundle\Event\CartPostUpdateEvent;
use Commercetools\Symfony\CartBundle\Event\CartUpdateEvent;
use Commercetools\Symfony\CartBundle\Model\Repository\CartRepository;
use Commercetools\Symfony\ExampleBundle\EventListener\CartSubscriber;
use Commercetools\Symfony\ShoppingListBundle\Manager\ShoppingListManager;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class CartSubscriberTest extends TestCase
{
    public function testGetSubscribedEvents()
    {
        $session = $this->prophesize(SessionInterface::class);
        $subscriber = new CartSubscriber($session->reveal());

        $this->assertArrayHasKey(CartCreateEvent::class, $subscriber->getSubscribedEvents());
        $this->assertArrayHasKey(CartPostCreateEvent::class, $subscriber->getSubscribedEvents());
        $this->assertArrayHasKey(CartUpdateEvent::class, $subscriber->getSubscribedEvents());
        $this->assertArrayHasKey(CartPostUpdateEvent::class, $subscriber->getSubscribedEvents());
        $this->assertArrayHasKey(CartGetEvent::class, $subscriber->getSubscribedEvents());
        $this->assertArrayHasKey(CartNotFoundEvent::class, $subscriber->getSubscribedEvents());
    }
    
    public function testOnCartCreate()
    {
        $session = $this->prophesize(SessionInterface::class);
        $subscriber = new CartSubscriber($session->reveal());
        $this->assertTrue($subscriber->onCartCreate());
    }

    public function testOnCartUpdate()
    {
        $session = $this->prophesize(SessionInterface::class);
        $event = $this->prophesize(CartUpdateEvent::class);
        $subscriber = new CartSubscriber($session->reveal());
        $this->assertTrue($subscriber->onCartUpdate($event->reveal()));
    }

    public function testOnCartGet()
    {
        $session = $this->prophesize(SessionInterface::class);
        $session->set(CartRepository::CART_ID, 'cart-1')->shouldBeCalledOnce();
        $session->set(CartRepository::CART_ITEM_COUNT, 2)->shouldBeCalledOnce();

        $subscriber = new CartSubscriber($session->reveal());

        $event = $this->prophesize(CartGetEvent::class);
        $cart = $this->prophesize(Cart::class);

        $cart->getId()->willReturn('cart-1')->shouldBeCalledOnce();
        $cart->getLineItemCount()->willReturn(2)->shouldBeCalledOnce();

        $event->getCart()->willReturn($cart->reveal())->shouldBeCalledOnce();

        $subscriber->onCartGet($event->reveal());
    }

    public function testOnCartPostCreate()
    {
        $session = $this->prophesize(SessionInterface::class);
        $session->set(CartRepository::CART_ID, 'cart-1')->shouldBeCalledOnce();
        $session->set(CartRepository::CART_ITEM_COUNT, 2)->shouldBeCalledOnce();

        $subscriber = new CartSubscriber($session->reveal());

        $event = $this->prophesize(CartPostCreateEvent::class);
        $cart = $this->prophesize(Cart::class);

        $cart->getId()->willReturn('cart-1')->shouldBeCalledOnce();
        $cart->getLineItemCount()->willReturn(2)->shouldBeCalledOnce();

        $event->getCart()->willReturn($cart->reveal())->shouldBeCalledOnce();

        $subscriber->onCartPostCreate($event->reveal());
    }

    public function testOnCartPostUpdate()
    {
        $session = $this->prophesize(SessionInterface::class);
        $session->set(CartRepository::CART_ID, 'cart-1')->shouldBeCalledOnce();
        $session->set(CartRepository::CART_ITEM_COUNT, 2)->shouldBeCalledOnce();

        /** @var ShoppingListManager $shoppingListManager */
        $shoppingListManager = $this->prophesize(ShoppingListManager::class);
        $shoppingListManager->getById(Argument::any(), Argument::any())
            ->shouldNotBeCalled();

        $subscriber = new CartSubscriber($session->reveal(), $shoppingListManager->reveal());

        $event = $this->prophesize(CartPostUpdateEvent::class);
        $cart = $this->prophesize(Cart::class);

        $cart->getId()->willReturn('cart-1')->shouldBeCalledOnce();
        $cart->getLineItemCount()->willReturn(2)->shouldBeCalledOnce();

        $actions = [
            CartSetCountryAction::of()->setCountry('FR')
        ];

        $event->getCart()->willReturn($cart->reveal())->shouldBeCalledOnce();
        $event->getActions()->willReturn($actions)->shouldBeCalledOnce();

        $subscriber->onCartPostUpdate($event->reveal());
    }

    public function testOnCartPostUpdateWithAddShoppingListAction()
    {
        $session = $this->prophesize(SessionInterface::class);
        $session->set(CartRepository::CART_ID, 'cart-1')->shouldBeCalledOnce();
        $session->set(CartRepository::CART_ITEM_COUNT, 2)->shouldBeCalledOnce();

        /** @var ShoppingListManager $shoppingListManager */
        $shoppingListManager = $this->prophesize(ShoppingListManager::class);
        $shoppingListManager->getById(Argument::type('string'), 'list-1')
            ->willReturn(ShoppingList::of()->setId('list-1'))->shouldBeCalledOnce();
        $shoppingListManager->deleteShoppingList(Argument::type('string'), Argument::type(ShoppingList::class))
            ->willReturn(ShoppingList::of())->shouldBeCalledOnce();

        $subscriber = new CartSubscriber($session->reveal(), $shoppingListManager->reveal());

        $event = $this->prophesize(CartPostUpdateEvent::class);
        $cart = $this->prophesize(Cart::class);

        $cart->getId()->willReturn('cart-1')->shouldBeCalledOnce();
        $cart->getLineItemCount()->willReturn(2)->shouldBeCalledOnce();

        $actions = [
            CartAddShoppingListAction::ofShoppingList(ShoppingListReference::ofId('list-1'))
        ];

        $event->getCart()->willReturn($cart->reveal())->shouldBeCalledOnce();
        $event->getActions()->willReturn($actions)->shouldBeCalledOnce();

        $subscriber->onCartPostUpdate($event->reveal());
    }

    public function testOnCartNotFound()
    {
        $session = $this->prophesize(SessionInterface::class);
        $session->remove(CartRepository::CART_ID)->shouldBeCalledOnce();
        $session->remove(CartRepository::CART_ITEM_COUNT)->shouldBeCalledOnce();

        $subscriber = new CartSubscriber($session->reveal());

        $subscriber->onCartNotFound();
    }
}
