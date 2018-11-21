<?php
/**
 */

namespace Commercetools\Symfony\ShoppingListBundle\Event;

use Commercetools\Core\Model\ShoppingList\ShoppingList;
use Commercetools\Core\Request\AbstractAction;
use Symfony\Component\EventDispatcher\Event;

class ShoppingListUpdateEvent extends Event
{
    /**
     * @var ShoppingList
     */
    private $shoppingList;

    /**
     * @var AbstractAction[]
     */
    private $actions;

    public function __construct(ShoppingList $shoppingList, AbstractAction $action)
    {
        $this->shoppingList = $shoppingList;
        $this->actions = [$action];
    }

    /**
     * @return ShoppingList
     */
    public function getShoppingList()
    {
        return $this->shoppingList;
    }

    /**
     * @return AbstractAction[]
     */
    public function getActions()
    {
        return $this->actions;
    }

    /**
     * @param array $actions
     */
    public function setActions(array $actions)
    {
        $this->actions = $actions;
    }

    /**
     * @param AbstractAction $action
     */
    public function addAction(AbstractAction $action)
    {
        $this->actions[] = $action;
    }
}
