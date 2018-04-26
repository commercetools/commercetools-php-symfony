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
use Commercetools\Symfony\CtpBundle\Model\QueryParams;
use Commercetools\Symfony\ShoppingListBundle\Event\ShoppingListUpdateEvent;
use Commercetools\Symfony\ShoppingListBundle\Model\Repository\ShoppingListRepository;
use Commercetools\Symfony\ShoppingListBundle\Model\ShoppingListUpdateBuilder;
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

    public function getById($locale, $shoppingListId, QueryParams $params = null)
    {
        return $this->repository->getShoppingListById($locale, $shoppingListId, $params);
    }

    public function getAllOfCustomer($locale, CustomerReference $customer, QueryParams $params = null)
    {
        return $this->repository->getAllShoppingListsByCustomer($locale, $customer, $params);
    }

    public function createShoppingList($locale, CustomerReference $customer, $name)
    {

        return $this->repository->create($locale, $customer, $name);
    }

    /**
     * @param ShoppingList $list
     * @return ShoppingListUpdateBuilder
     */
    public function update(ShoppingList $list)
    {
        return new ShoppingListUpdateBuilder($list, $this);
    }

    public function dispatch(ShoppingList $shoppingList, AbstractAction $action, $eventName = null)
    {
        $eventName = is_null($eventName) ? get_class($action) : $eventName;

        $event = new ShoppingListUpdateEvent($shoppingList, $action);
        $event = $this->dispatcher->dispatch($eventName, $event);

        return $event->getActions();
    }

    /**
     * @param $shoppingList
     * @param $actions
     * @return ShoppingList
     */
    public function apply(ShoppingList $shoppingList, array $actions)
    {
        return $this->repository->update($shoppingList, $actions);
    }
}
