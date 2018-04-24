<?php
declare(strict_types=1);

namespace Commercetools\Symfony\ShoppingListBundle\Tests\Model;

use Commercetools\Core\Model\Common\LocalizedString;
use Commercetools\Core\Model\ShoppingList\ShoppingList;
use Commercetools\Core\Request\ShoppingLists\Command\ShoppingListAddLineItemAction;
use Commercetools\Core\Request\ShoppingLists\Command\ShoppingListChangeNameAction;
use Commercetools\Symfony\ShoppingListBundle\Event\ShoppingListUpdateEvent;
use Commercetools\Symfony\ShoppingListBundle\Manager\ShoppingListManager;
use Commercetools\Symfony\ShoppingListBundle\Model\ShoppingListUpdate;
use Prophecy\Argument;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ShoppingListUpdateTest extends \PHPUnit_Framework_TestCase
{
    private function getShoppingList()
    {
        $shoppingList = $this->prophesize(ShoppingList::class);
        return $shoppingList;
    }

    private function getDispatcher($class)
    {
        $dispatcher = $this->prophesize(EventDispatcherInterface::class);
        $dispatcher->dispatch($class, Argument::type(ShoppingListUpdateEvent::class))
            ->will(function ($args) { return $args[1]; } )->shouldBeCalledTimes(1);

        return $dispatcher;
    }

    private function getManager($shoppingList, $class, $callback)
    {
        $manager = $this->prophesize(ShoppingListManager::class);
        $manager->apply(
            $shoppingList,
            Argument::allOf(
                Argument::containing(Argument::type($class)),
                Argument::that($callback)
            )
        )->shouldBeCalledTimes(1);

        return $manager;

    }
    public function testAddLineItem()
    {
        $shoppingList = $this->getShoppingList();
        $shoppingListMock = $shoppingList->reveal();

        $manager = $this->getManager(
            $shoppingListMock,
            ShoppingListAddLineItemAction::class,
            function ($actions) {
                $action = current($actions);
                static::assertSame('12345', $action->getProductId());
                return true;
            }
        );
        $dispatcher = $this->getDispatcher(ShoppingListAddLineItemAction::class);


        $update = new ShoppingListUpdate($shoppingListMock, $dispatcher->reveal(), $manager->reveal());

        $action = ShoppingListAddLineItemAction::of()->setProductId('12345');
        $update->addLineItem($action);

        $update->flush();
    }

    public function testAddLineItemCallback()
    {
        $shoppingList = $this->getShoppingList();
        $shoppingListMock = $shoppingList->reveal();

        $manager = $this->getManager(
            $shoppingListMock,
            ShoppingListAddLineItemAction::class,
            function ($actions) {
                $action = current($actions);
                static::assertSame('12345', $action->getProductId());
                return true;
            }
        );
        $dispatcher = $this->getDispatcher(ShoppingListAddLineItemAction::class);


        $update = new ShoppingListUpdate($shoppingListMock, $dispatcher->reveal(), $manager->reveal());

        $callback = function (ShoppingListAddLineItemAction $action) : ShoppingListAddLineItemAction {
            $action->setProductId('12345');
            return $action;
        };

        $update->addLineItem($callback);

        $update->flush();
    }

    public function testChangeName()
    {
        $shoppingList = $this->getShoppingList();
        $shoppingListMock = $shoppingList->reveal();

        $manager = $this->getManager(
            $shoppingListMock,
            ShoppingListChangeNameAction::class,
            function ($actions) {
                $action = current($actions);
                static::assertSame('new name', $action->getName()->en);
                return true;
            }
        );
        $dispatcher = $this->getDispatcher(ShoppingListChangeNameAction::class);


        $update = new ShoppingListUpdate($shoppingListMock, $dispatcher->reveal(), $manager->reveal());

        $action = ShoppingListChangeNameAction::ofName(LocalizedString::ofLangAndText('en', 'new name'));

        $update->changeName($action);

        $update->flush();
    }

    public function testChangeNameCallback()
    {
        $shoppingList = $this->getShoppingList();
        $shoppingListMock = $shoppingList->reveal();

        $manager = $this->getManager(
            $shoppingListMock,
            ShoppingListChangeNameAction::class,
            function ($actions) {
                $action = current($actions);
                static::assertSame('new name', $action->getName()->en);
                return true;
            }
        );
        $dispatcher = $this->getDispatcher(ShoppingListChangeNameAction::class);


        $update = new ShoppingListUpdate($shoppingListMock, $dispatcher->reveal(), $manager->reveal());

        $callback = function (ShoppingListChangeNameAction $action) : ShoppingListChangeNameAction {
            $action->setName(LocalizedString::ofLangAndText('en', 'new name'));
            return $action;
        };

        $update->changeName($callback);

        $update->flush();
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
