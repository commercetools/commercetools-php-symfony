<?php
/**
 * Created by PhpStorm.
 * User: nsotiropoulos
 * Date: 17/04/2018
 * Time: 16:39
 */

namespace Commercetools\Symfony\ShoppingListBundle\Manager;


use Commercetools\Core\Model\ShoppingList\ShoppingList;
use Commercetools\Core\Request\AbstractAction;
use Commercetools\Core\Request\ShoppingLists\Command\ShoppingListAddLineItemAction;
use Commercetools\Core\Request\ShoppingLists\Command\ShoppingListRemoveLineItemAction;
use Commercetools\Core\Request\ShoppingLists\Command\ShoppingListChangeLineItemQuantityAction;
use Commercetools\Symfony\ShoppingListBundle\Event\ShoppingListUpdateEvent;
use Commercetools\Symfony\ShoppingListBundle\Model\Repository\ShoppingListRepository;
use Commercetools\Symfony\ShoppingListBundle\Model\ShoppingListUpdate;
use Commercetools\Symfony\ShoppingListBundle\ShoppingListEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Commercetools\Core\Model\Customer\CustomerReference;

class ShoppingListManager
{
    /**
     * @var ShoppingListRepository
     */
    private $repository;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * ShoppingListManager constructor.
     * @param ShoppingListRepository $repository
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(ShoppingListRepository $repository, EventDispatcherInterface $dispatcher)
    {
        $this->repository = $repository;
        $this->dispatcher = $dispatcher;
    }


    public function getById($locale, $shoppingListId)
    {
        return $this->repository->getShoppingList($locale, $shoppingListId);
    }

    public function getAllOfCustomer($locale, CustomerReference $customer)
    {
        return $this->repository->getAllShoppingListsByCustomer($locale, $customer);
    }

    public function addLineItem(ShoppingList $shoppingList, $productId, $variantId, $quantity = 1)
    {
        $action = ShoppingListAddLineItemAction::ofProductIdVariantIdAndQuantity($productId, (int)$variantId, (int)$quantity);

        $shoppingList = $this->updateShoppingList($shoppingList, $action);

        return $shoppingList;
    }

    public function removeLineItem(ShoppingList $shoppingList, $lineItemId)
    {
        $action = ShoppingListRemoveLineItemAction::ofLineItemId($lineItemId);

        $shoppingList = $this->updateShoppingList($shoppingList, $action);

        return $shoppingList;
    }

    public function changeLineItemQuantity(ShoppingList $shoppingList, $lineItemId, $quantity)
    {
        $action = ShoppingListChangeLineItemQuantityAction::ofLineItemIdAndQuantity($lineItemId, (int)$quantity);

        $shoppingList = $this->updateShoppingList($shoppingList, $action);

        return $shoppingList;
    }

    /**
     * @param ShoppingList $list
     * @return ShoppingListUpdate
     */
    public function update(ShoppingList $list)
    {
        return new ShoppingListUpdate($list, $this->dispatcher, $this->manager);
    }

    public function store($shoppingList, $actions)
    {

    }

    public function updateShoppingList(ShoppingList $shoppingList, AbstractAction $action, $eventName = null)
    {
        $eventName = is_null($eventName) ? get_class($action) : $eventName;

        $event = new ShoppingListUpdateEvent($shoppingList, $action);
        $event = $this->dispatcher->dispatch($eventName, $event);

        return $this->repository->update($event->getShoppingList(), $event->getActions());
    }
}