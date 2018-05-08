<?php
/**
 *
 */

namespace Commercetools\Symfony\CartBundle\Tests\Manager;


use Commercetools\Core\Model\Cart\Cart;
use Commercetools\Core\Request\AbstractAction;
use Commercetools\Symfony\CartBundle\Event\CartPostUpdateEvent;
use Commercetools\Symfony\CartBundle\Event\CartUpdateEvent;
use Commercetools\Symfony\CartBundle\Manager\CartManager;
use Commercetools\Symfony\CartBundle\Model\CartUpdateBuilder;
use Commercetools\Symfony\CartBundle\Model\Repository\CartRepository;
use Prophecy\Argument;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class CartManagerTest extends \PHPUnit_Framework_TestCase
{
    public function testApply()
    {
        $cart = $this->prophesize(Cart::class);
        $repository = $this->prophesize(CartRepository::class);
        $dispatcher = $this->prophesize(EventDispatcherInterface::class);

        $repository->update($cart, Argument::type('array'))
            ->will(function ($args) { return $args[0]; })->shouldBeCalled();

        $dispatcher->dispatch(
            Argument::containingString(CartPostUpdateEvent::class),
            Argument::type(CartPostUpdateEvent::class)
        )->will(function ($args) { return $args[1]; })->shouldBeCalled();

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
        )->will(function ($args) { return $args[1]; })->shouldBeCalled();
        $action = $this->prophesize(AbstractAction::class);

        $manager = new CartManager($repository->reveal(), $dispatcher->reveal());

        $actions = $manager->dispatch($cart->reveal(), $action->reveal());
        $this->assertInstanceOf(AbstractAction::class, current($actions));
        $this->assertCount(1, $actions);
    }

    public function testCreateCart()
    {

    }

    public function testUpdate()
    {
        $cart = $this->prophesize(Cart::class);
        $repository = $this->prophesize(CartRepository::class);
        $dispatcher = $this->prophesize(EventDispatcherInterface::class);

        $manager = new CartManager($repository->reveal(), $dispatcher->reveal());
        $this->assertInstanceOf(CartUpdateBuilder::class, $manager->update($cart->reveal()));

    }

    public function testGetById()
    {
        $repository = $this->prophesize(CartRepository::class);
        $dispatcher = $this->prophesize(EventDispatcherInterface::class);
//
//        $repository->getCartById('en', '123456', null)
//            ->willReturn(Cart::of())->shouldBeCalled();

        $manager = new CartManager($repository->reveal(), $dispatcher->reveal());
//        $list = $manager->getById('en', '123456');
//
//        $this->assertInstanceOf(Cart::class, $list);
    }
}
