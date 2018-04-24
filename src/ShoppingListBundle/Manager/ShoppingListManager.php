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

    public function createShoppingList($locale, CustomerReference $customer, $name)
    {
        return $this->repository->create($locale, $customer, $name);
    }

    /**
     * @param ShoppingList $list
     * @return ShoppingListUpdate
     */
    public function update(ShoppingList $list)
    {
        return new ShoppingListUpdate($list, $this->dispatcher, $this);
    }

    /**
     * @param $shoppingList
     * @param $actions
     * @return ShoppingList
     */
    public function apply($shoppingList, $actions)
    {
        return $this->repository->update($shoppingList, $actions);

    }
}