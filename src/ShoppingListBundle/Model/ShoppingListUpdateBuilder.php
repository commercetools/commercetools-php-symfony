<?php
/**
 * Created by PhpStorm.
 * User: nsotiropoulos
 * Date: 18/04/2018
 * Time: 14:51
 */

namespace Commercetools\Symfony\ShoppingListBundle\Model;


use Commercetools\Core\Builder\Update\ShoppingListsActionBuilder;
use Commercetools\Core\Model\ShoppingList\ShoppingList;
use Commercetools\Core\Request\AbstractAction;
use Commercetools\Symfony\ShoppingListBundle\Manager\ShoppingListManager;

class ShoppingListUpdateBuilder extends ShoppingListsActionBuilder
{
    /**
     * @var ShoppingListManager
     */
    private $manager;

    /**
     * @var ShoppingList
     */
    private $shoppingList;

    /**
     * ShoppingListUpdate constructor.
     * @param ShoppingListManager $manager
     * @param ShoppingList $shoppingList
     */
    public function __construct(ShoppingList $shoppingList, ShoppingListManager $manager)
    {
        $this->manager = $manager;
        $this->shoppingList = $shoppingList;
    }


    public function addAction(AbstractAction $action, $eventName = null)
    {
        $actions = $this->manager->dispatch($this->shoppingList, $action, $eventName);

        $this->setActions(array_merge($this->getActions(), $actions));

        return $this;
    }

    /**
     * @return ShoppingList
     */
    public function flush()
    {
        return $this->manager->apply($this->shoppingList, $this->getActions());
    }
}
