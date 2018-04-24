<?php
declare(strict_types=1);

namespace Commercetools\Symfony\ShoppingListBundle\Tests\Model;

use Commercetools\Core\Model\ShoppingList\ShoppingList;
use Commercetools\Core\Request\ShoppingLists\Command\ShoppingListAddLineItemAction;
use Commercetools\Symfony\ShoppingListBundle\Event\ShoppingListUpdateEvent;
use Commercetools\Symfony\ShoppingListBundle\Manager\ShoppingListManager;
use Commercetools\Symfony\ShoppingListBundle\Model\ShoppingListUpdate;
use Prophecy\Argument;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ShoppingListUpdateTest extends \PHPUnit_Framework_TestCase
{

    public function testAddLineItem()
    {
        $shoppingList = $this->prophesize(ShoppingList::class);

        $manager = $this->prophesize(ShoppingListManager::class);
        $manager->store(
            $shoppingList->reveal(),
            Argument::allOf(
                Argument::containing(Argument::type(ShoppingListAddLineItemAction::class)),
                Argument::that(
                    function ($actions) {
                        $action = current($actions);
                        static::assertSame('12345', $action->getProductId());
                    }
                )
            )
        )->shouldBeCalled();

        $dispatcher = $this->prophesize(EventDispatcherInterface::class);
        $dispatcher->dispatch(ShoppingListAddLineItemAction::class, Argument::type(ShoppingListUpdateEvent::class))
            ->will(function ($args) { return $args[1]; } )->shouldBeCalled();


        $update = new ShoppingListUpdate($shoppingList->reveal(), $dispatcher->reveal(), $manager->reveal());

        $action = ShoppingListAddLineItemAction::of()->setProductId('12345');
        $update->addLineItem($action);

        $update->flush();
    }

    public function testAddLineItemCallback()
    {
        $shoppingList = $this->prophesize(ShoppingList::class);

        $manager = $this->prophesize(ShoppingListManager::class);
        $manager->store(
            $shoppingList->reveal(),
            Argument::allOf(
                Argument::containing(Argument::type(ShoppingListAddLineItemAction::class)),
                Argument::that(
                    function ($actions) {
                        $action = current($actions);
                        static::assertSame('12345', $action->getProductId());
                    }
                )
            )
        )->shouldBeCalled();

        $dispatcher = $this->prophesize(EventDispatcherInterface::class);
        $dispatcher->dispatch(ShoppingListAddLineItemAction::class, Argument::type(ShoppingListUpdateEvent::class))
            ->will(function ($args) { return $args[1]; } )->shouldBeCalled();


        $update = new ShoppingListUpdate($shoppingList->reveal(), $dispatcher->reveal(), $manager->reveal());

        $callback = function (ShoppingListAddLineItemAction $action) : ShoppingListAddLineItemAction {
            $action->setProductId('12345');
            return $action;
        };

        $update->addLineItem($callback);

        $update->flush();
    }

    public function testChangeName()
    {

    }

    public function testHandle()
    {

    }

    public function testUpdateShoppingList()
    {

    }

    public function testFlush()
    {

    }
}
