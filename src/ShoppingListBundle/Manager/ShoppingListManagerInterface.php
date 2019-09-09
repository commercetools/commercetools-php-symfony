<?php
/**
 *
 */

namespace Commercetools\Symfony\ShoppingListBundle\Manager;

use Commercetools\Core\Model\ShoppingList\ShoppingList;
use Commercetools\Core\Request\AbstractAction;
use Commercetools\Symfony\ShoppingListBundle\Model\ShoppingListUpdateBuilder;

interface ShoppingListManagerInterface
{
    /**
     * @param ShoppingList $list
     * @return ShoppingListUpdateBuilder
     */
    public function update(ShoppingList $list);

    /**
     * @param ShoppingList $shoppingList
     * @param AbstractAction $action
     * @param null $eventName
     * @return AbstractAction[]
     */
    public function dispatch(ShoppingList $shoppingList, AbstractAction $action, $eventName = null);

    /**
     * @param ShoppingList $shoppingList
     * @param array $actions
     * @return ShoppingList
     */
    public function apply(ShoppingList $shoppingList, array $actions);

    /**
     * @param ShoppingList $shoppingList
     * @param array $actions
     * @return ShoppingList
     */
    public function dispatchPostUpdate(ShoppingList $shoppingList, array $actions);
}
