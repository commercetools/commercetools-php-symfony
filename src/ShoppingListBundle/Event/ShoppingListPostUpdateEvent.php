<?php
/**
 */

namespace Commercetools\Symfony\ShoppingListBundle\Event;

use Commercetools\Core\Model\ShoppingList\ShoppingList;
use Commercetools\Core\Request\AbstractAction;
use Symfony\Contracts\EventDispatcher\Event;

class ShoppingListPostUpdateEvent extends Event
{
    /**
     * @var ShoppingList
     */
    private $shoppingList;

    /**
     * @var AbstractAction[]
     */
    private $actions;

    public function __construct(ShoppingList $shoppingList, array $actions)
    {
        $this->shoppingList = $shoppingList;
        $this->actions = $actions;
    }

    /**
     * @return AbstractAction[]
     */
    public function getActions()
    {
        return $this->actions;
    }

    /**
     * @return ShoppingList
     */
    public function getShoppingList()
    {
        return $this->shoppingList;
    }
}
