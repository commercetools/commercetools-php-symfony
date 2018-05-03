<?php
/**
 * Created by PhpStorm.
 * User: nsotiropoulos
 * Date: 17/04/2018
 * Time: 15:13
 */

namespace Commercetools\Symfony\ShoppingListBundle\Event;

use Commercetools\Core\Model\ShoppingList\ShoppingList;
use Commercetools\Core\Request\AbstractAction;
use Symfony\Component\EventDispatcher\Event;

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

    /**
     * @param ShoppingList $shoppingList
     */
    public function setShoppingList(ShoppingList $shoppingList)
    {
        $this->shoppingList = $shoppingList;
    }
}
