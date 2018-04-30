<?php
declare(strict_types=1);

namespace Commercetools\Symfony\ShoppingListBundle\Tests\Model;

use Commercetools\Core\Model\ShoppingList\ShoppingList;
use Commercetools\Core\Request\ShoppingLists\Command\ShoppingListAddLineItemAction;
use Commercetools\Core\Request\ShoppingLists\Command\ShoppingListAddTextLineItemAction;
use Commercetools\Core\Request\ShoppingLists\Command\ShoppingListChangeLineItemQuantityAction;
use Commercetools\Core\Request\ShoppingLists\Command\ShoppingListChangeLineItemsOrderAction;
use Commercetools\Core\Request\ShoppingLists\Command\ShoppingListChangeNameAction;
use Commercetools\Core\Request\ShoppingLists\Command\ShoppingListChangeTextLineItemNameAction;
use Commercetools\Core\Request\ShoppingLists\Command\ShoppingListChangeTextLineItemQuantityAction;
use Commercetools\Core\Request\ShoppingLists\Command\ShoppingListChangeTextLineItemsOrderAction;
use Commercetools\Core\Request\ShoppingLists\Command\ShoppingListRemoveLineItemAction;
use Commercetools\Core\Request\ShoppingLists\Command\ShoppingListRemoveTextLineItemAction;
use Commercetools\Core\Request\ShoppingLists\Command\ShoppingListSetCustomerAction;
use Commercetools\Core\Request\ShoppingLists\Command\ShoppingListSetCustomFieldAction;
use Commercetools\Core\Request\ShoppingLists\Command\ShoppingListSetCustomTypeAction;
use Commercetools\Core\Request\ShoppingLists\Command\ShoppingListSetDeleteDaysAfterLastModificationAction;
use Commercetools\Core\Request\ShoppingLists\Command\ShoppingListSetDescriptionAction;
use Commercetools\Core\Request\ShoppingLists\Command\ShoppingListSetKeyAction;
use Commercetools\Core\Request\ShoppingLists\Command\ShoppingListSetLineItemCustomFieldAction;
use Commercetools\Core\Request\ShoppingLists\Command\ShoppingListSetLineItemCustomTypeAction;
use Commercetools\Core\Request\ShoppingLists\Command\ShoppingListSetSlugAction;
use Commercetools\Core\Request\ShoppingLists\Command\ShoppingListSetTextLineItemCustomFieldAction;
use Commercetools\Core\Request\ShoppingLists\Command\ShoppingListSetTextLineItemCustomTypeAction;
use Commercetools\Core\Request\ShoppingLists\Command\ShoppingListSetTextLineItemDescriptionAction;
use Commercetools\Symfony\ShoppingListBundle\Manager\ShoppingListManager;
use Commercetools\Symfony\ShoppingListBundle\Model\ShoppingListUpdateBuilder;
use Prophecy\Argument;

class ShoppingListUpdateTest extends \PHPUnit_Framework_TestCase
{
    public function getActionProvider()
    {
        return [
            ['addLineItem', ShoppingListAddLineItemAction::class],
            ['addTextLineItem', ShoppingListAddTextLineItemAction::class],
            ['changeLineItemQuantity', ShoppingListChangeLineItemQuantityAction::class],
            ['changeLineItemsOrder', ShoppingListChangeLineItemsOrderAction::class],
            ['changeName', ShoppingListChangeNameAction::class],
            ['changeTextLineItemName', ShoppingListChangeTextLineItemNameAction::class],
            ['changeTextLineItemQuantity', ShoppingListChangeTextLineItemQuantityAction::class],
            ['changeTextLineItemsOrder', ShoppingListChangeTextLineItemsOrderAction::class],
            ['removeLineItem', ShoppingListRemoveLineItemAction::class],
            ['removeTextLineItem', ShoppingListRemoveTextLineItemAction::class],
            ['setCustomField', ShoppingListSetCustomFieldAction::class],
            ['setCustomType', ShoppingListSetCustomTypeAction::class],
            ['setCustomer', ShoppingListSetCustomerAction::class],
            ['setDeleteDaysAfterLastModification', ShoppingListSetDeleteDaysAfterLastModificationAction::class],
            ['setDescription', ShoppingListSetDescriptionAction::class],
            ['setKey', ShoppingListSetKeyAction::class],
            ['setLineItemCustomField', ShoppingListSetLineItemCustomFieldAction::class],
            ['setLineItemCustomType', ShoppingListSetLineItemCustomTypeAction::class],
            ['setSlug', ShoppingListSetSlugAction::class],
            ['setTextLineItemCustomField', ShoppingListSetTextLineItemCustomFieldAction::class],
            ['setTextLineItemCustomType', ShoppingListSetTextLineItemCustomTypeAction::class],
            ['setTextLineItemDescription', ShoppingListSetTextLineItemDescriptionAction::class],
        ];
    }

    /**
     * @dataProvider getActionProvider
     */
    public function testUpdateMethods($updateMethod, $actionClass)
    {
        $shoppingList = $this->prophesize(ShoppingList::class);

        $manager = $this->prophesize(ShoppingListManager::class);
        $manager->apply(
            $shoppingList,
            Argument::allOf(
                Argument::containing(Argument::type($actionClass))
            )
        )->shouldBeCalledTimes(1);

        $manager->dispatch(
            $shoppingList,
            Argument::type($actionClass),
            Argument::is(null)
        )->will(function ($args) { return [$args[1]]; })->shouldBeCalledTimes(1);

        $update = new ShoppingListUpdateBuilder($shoppingList->reveal(), $manager->reveal());

        $action = $actionClass::of();
        $update->$updateMethod($action);

        $update->flush();
    }

    /**
     * @dataProvider getActionProvider
     */
    public function testUpdateMethodsCallback($updateMethod, $actionClass)
    {
        $shoppingList = $this->prophesize(ShoppingList::class);

        $manager = $this->prophesize(ShoppingListManager::class);
        $manager->apply(
            $shoppingList,
            Argument::allOf(
                Argument::containing(Argument::type($actionClass))
            )
        )->shouldBeCalledTimes(1);

        $manager->dispatch(
            $shoppingList,
            Argument::type($actionClass),
            Argument::is(null)
        )->will(function ($args) { return [$args[1]]; })->shouldBeCalledTimes(1);

        $update = new ShoppingListUpdateBuilder($shoppingList->reveal(), $manager->reveal());

        $callback = function ($action) use ($actionClass) {
            static::assertInstanceOf($actionClass, $action);
            return $action;
        };
        $update->$updateMethod($callback);

        $update->flush();
    }
}
