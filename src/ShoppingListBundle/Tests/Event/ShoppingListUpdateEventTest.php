<?php
/**
 *
 */

namespace Commercetools\Symfony\ShoppingListBundle\Tests\Event;

use Commercetools\Core\Model\ShoppingList\ShoppingList;
use Commercetools\Core\Request\ShoppingLists\Command\ShoppingListChangeNameAction;
use Commercetools\Core\Request\ShoppingLists\Command\ShoppingListSetCustomerAction;
use Commercetools\Symfony\ShoppingListBundle\Event\ShoppingListUpdateEvent;
use PHPUnit\Framework\TestCase;

class ShoppingListUpdateEventTest extends TestCase
{
    public function testShoppingListUpdateEvent()
    {
        $event = new ShoppingListUpdateEvent(ShoppingList::of(), ShoppingListChangeNameAction::of());
        $this->assertInstanceOf(ShoppingList::class, $event->getShoppingList());
        $this->assertSame(1, count($event->getActions()));
        $this->assertInstanceOf(ShoppingListChangeNameAction::class, current($event->getActions()));

        $event->addAction(ShoppingListSetCustomerAction::of());
        $this->assertSame(2, count($event->getActions()));

        $event->setActions([ShoppingListSetCustomerAction::of()]);
        $this->assertSame(1, count($event->getActions()));
        $this->assertInstanceOf(ShoppingListSetCustomerAction::class, current($event->getActions()));
    }
}
