<?php
/**
 *
 */

namespace Commercetools\Symfony\ShoppingListBundle\Tests\Event;


use Commercetools\Core\Model\ShoppingList\ShoppingList;
use Commercetools\Core\Request\ShoppingLists\Command\ShoppingListSetCustomerAction;
use Commercetools\Symfony\ShoppingListBundle\Event\ShoppingListPostUpdateEvent;
use PHPUnit\Framework\TestCase;

class ShoppingListPostUpdateEventTest extends TestCase
{
    public function testShoppingListPostUpdateEvent()
    {
        $event = new ShoppingListPostUpdateEvent(ShoppingList::of(), [ShoppingListSetCustomerAction::of()]);
        $this->assertInstanceOf(ShoppingList::class, $event->getShoppingList());
        $this->assertInstanceOf(ShoppingListSetCustomerAction::class, current($event->getActions()));
    }
}
