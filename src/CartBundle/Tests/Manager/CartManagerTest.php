<?php
/**
 *
 */

namespace Commercetools\Symfony\CartBundle\Tests\Manager;

use Commercetools\Core\Model\Cart\Cart;
use Commercetools\Core\Model\Cart\LineItemDraftCollection;
use Commercetools\Core\Model\Zone\Location;
use Commercetools\Core\Request\AbstractAction;
use Commercetools\Symfony\CartBundle\Event\CartPostUpdateEvent;
use Commercetools\Symfony\CartBundle\Event\CartUpdateEvent;
use Commercetools\Symfony\CartBundle\Manager\CartManager;
use Commercetools\Symfony\CartBundle\Model\CartUpdateBuilder;
use Commercetools\Symfony\CartBundle\Model\Repository\CartRepository;
use Commercetools\Symfony\CustomerBundle\Security\User\User;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class CartManagerTest extends TestCase
{
    public function testApply()
    {
        $cart = $this->prophesize(Cart::class);
        $repository = $this->prophesize(CartRepository::class);
        $dispatcher = $this->prophesize(EventDispatcherInterface::class);

        $repository->update($cart->reveal(), Argument::type('array'))
            ->will(function ($args) {
                return $args[0];
            })->shouldBeCalled();

        $dispatcher->dispatch(
            Argument::containingString(CartPostUpdateEvent::class),
            Argument::type(CartPostUpdateEvent::class)
        )->will(function ($args) {
            return $args[1];
        })->shouldBeCalled();

        $manager = new CartManager($repository->reveal(), $dispatcher->reveal());
        $cart = $manager->apply($cart->reveal(), []);

        $this->assertInstanceOf(Cart::class, $cart);
    }

    public function testDispatch()
    {
        $cart = $this->prophesize(Cart::class);
        $repository = $this->prophesize(CartRepository::class);
        $dispatcher = $this->prophesize(EventDispatcherInterface::class);
        $dispatcher->dispatch(
            Argument::containingString(AbstractAction::class),
            Argument::type(CartUpdateEvent::class)
        )->will(function ($args) {
            return $args[1];
        })->shouldBeCalled();
        $action = $this->prophesize(AbstractAction::class);

        $manager = new CartManager($repository->reveal(), $dispatcher->reveal());

        $actions = $manager->dispatch($cart->reveal(), $action->reveal());
        $this->assertInstanceOf(AbstractAction::class, current($actions));
        $this->assertCount(1, $actions);
    }

    public function testCreateCart()
    {
        $repository = $this->prophesize(CartRepository::class);
        $lineItemDraftCollection = LineItemDraftCollection::of();
        $location = $this->prophesize(Location::class);

        $repository->createCart('en', 'EUR', $location->reveal(), $lineItemDraftCollection, null, '123')
            ->willReturn(Cart::of())->shouldBeCalled();

        $dispatcher = $this->prophesize(EventDispatcherInterface::class);

        $manager = new CartManager($repository->reveal(), $dispatcher->reveal());

        $cart = $manager->createCartForUser('en', 'EUR', $location->reveal(), $lineItemDraftCollection, null, '123');
        $this->assertInstanceOf(Cart::class, $cart);
    }

    public function testUpdate()
    {
        $cart = $this->prophesize(Cart::class);
        $repository = $this->prophesize(CartRepository::class);
        $dispatcher = $this->prophesize(EventDispatcherInterface::class);

        $manager = new CartManager($repository->reveal(), $dispatcher->reveal());
        $this->assertInstanceOf(CartUpdateBuilder::class, $manager->update($cart->reveal()));
    }

    public function testGetCart()
    {
        $repository = $this->prophesize(CartRepository::class);
        $dispatcher = $this->prophesize(EventDispatcherInterface::class);
        $user = $this->prophesize(User::class);

        $repository->getCart('en', '123', $user->reveal(), null)
            ->willReturn(Cart::of())->shouldBeCalled();

        $manager = new CartManager($repository->reveal(), $dispatcher->reveal());
        $cart = $manager->getCart('en', '123', $user->reveal());

        $this->assertInstanceOf(Cart::class, $cart);
    }

    public function testGetCartNotFound()
    {
        $repository = $this->prophesize(CartRepository::class);
        $dispatcher = $this->prophesize(EventDispatcherInterface::class);

        $repository->getCart('en', '123', null, 'anon-1')
            ->willReturn(null)->shouldBeCalled();

        $manager = new CartManager($repository->reveal(), $dispatcher->reveal());
        $cart = $manager->getCart('en', '123', null, 'anon-1');

        $this->assertNull($cart);
    }
}
