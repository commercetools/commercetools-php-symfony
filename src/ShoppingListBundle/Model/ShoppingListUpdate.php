<?php
/**
 * Created by PhpStorm.
 * User: nsotiropoulos
 * Date: 18/04/2018
 * Time: 14:51
 */

namespace Commercetools\Symfony\ShoppingListBundle\Model;


use Commercetools\Core\Model\ShoppingList\ShoppingList;
use Commercetools\Core\Request\AbstractAction;
use Commercetools\Core\Request\ShoppingLists\Command\ShoppingListAddLineItemAction;
use Commercetools\Core\Request\ShoppingLists\Command\ShoppingListChangeNameAction;
use Commercetools\Symfony\ShoppingListBundle\Event\ShoppingListUpdateEvent;
use Commercetools\Symfony\ShoppingListBundle\Manager\ShoppingListManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ShoppingListUpdate
{
    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var ShoppingListManager
     */
    private $manager;

    /**
     * @var ShoppingList
     */
    private $shoppingList;

    private $actions = [];

    /**
     * ShoppingListUpdate constructor.
     * @param EventDispatcherInterface $dispatcher
     * @param ShoppingListManager $manager
     * @param ShoppingList $shoppingList
     */
    public function __construct(ShoppingList $shoppingList, EventDispatcherInterface $dispatcher, ShoppingListManager $manager)
    {
        $this->dispatcher = $dispatcher;
        $this->manager = $manager;
        $this->shoppingList = $shoppingList;
    }

    public function addLineItem($productId, $variantId, $quantity = 1)
    {
        $action = ShoppingListAddLineItemAction::ofProductIdVariantIdAndQuantity($productId, (int)$variantId, (int)$quantity);

        $this->updateShoppingList($this->shoppingList, $action);

        return $this;
    }

    public function changeName($newName)
    {
        $this->handle($this->shoppingList->getName(), $newName, ShoppingListChangeNameAction::class, 'ofName');

        return $this;
    }

    public function handle($oldValue, $newValue, $action, $builder)
    {
        if ($oldValue != $newValue) {
            $action = $action::$builder($newValue);
            $this->updateShoppingList($this->shoppingList, $action);
        }
    }

    public function updateShoppingList(ShoppingList $shoppingList, AbstractAction $action, $eventName = null)
    {
        $eventName = is_null($eventName) ? get_class($action) : $eventName;

        $event = new ShoppingListUpdateEvent($shoppingList, $action);
        $event = $this->dispatcher->dispatch($eventName, $event);

        return $this->actions = array_merge($this->actions, $event->getActions());
    }

    /**
     * @return ShoppingList
     */
    public function flush()
    {
        return $this->manager->store($this->shoppingList, $this->actions);
    }
}