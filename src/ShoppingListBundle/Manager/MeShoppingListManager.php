<?php
/**
 */

namespace Commercetools\Symfony\ShoppingListBundle\Manager;

use Commercetools\Core\Model\ShoppingList\ShoppingList;
use Commercetools\Core\Request\AbstractAction;
use Commercetools\Symfony\CtpBundle\Model\QueryParams;
use Commercetools\Symfony\ShoppingListBundle\Event\ShoppingListUpdateEvent;
use Commercetools\Symfony\ShoppingListBundle\Event\ShoppingListPostUpdateEvent;
use Commercetools\Symfony\ShoppingListBundle\Model\Repository\MeShoppingListRepository;
use Commercetools\Symfony\ShoppingListBundle\Model\ShoppingListUpdateBuilder;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class MeShoppingListManager
{
    /**
     * @var MeShoppingListRepository
     */
    private $repository;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * ShoppingListManager constructor.
     * @param MeShoppingListRepository $repository
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(MeShoppingListRepository $repository, EventDispatcherInterface $dispatcher)
    {
        $this->repository = $repository;
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param string $locale
     * @param string $shoppingListId
     * @param QueryParams|null $params
     * @return mixed
     */
    public function getById($locale, $shoppingListId, QueryParams $params = null)
    {
        return $this->repository->getShoppingListById($locale, $shoppingListId, $params);
    }

    /**
     * @param string $locale
     * @param QueryParams|null $params
     * @return mixed
     */
    public function getAllMyShoppingLists($locale, QueryParams $params = null)
    {
        return $this->repository->getAllMyShoppingLists($locale, $params);
    }

    /**
     * @param string $locale
     * @param string $name
     * @return mixed
     */
    public function createShoppingList($locale, $name)
    {
        return $this->repository->create($locale, $name);
    }

    /**
     * @param string $locale
     * @param ShoppingList $shoppingList
     * @return mixed
     */
    public function deleteShoppingList($locale, ShoppingList $shoppingList)
    {
        return $this->repository->delete($locale, $shoppingList);
    }

    /**
     * @param ShoppingList $list
     * @return ShoppingListUpdateBuilder
     */
    public function update(ShoppingList $list)
    {
        return new ShoppingListUpdateBuilder($list, $this);
    }

    /**
     * @param ShoppingList $shoppingList
     * @param AbstractAction $action
     * @param null $eventName
     * @return AbstractAction[]
     */
    public function dispatch(ShoppingList $shoppingList, AbstractAction $action, $eventName = null)
    {
        $eventName = is_null($eventName) ? get_class($action) : $eventName;

        $event = new ShoppingListUpdateEvent($shoppingList, $action);
        $event = $this->dispatcher->dispatch($eventName, $event);

        return $event->getActions();
    }

    /**
     * @param ShoppingList $shoppingList
     * @param array $actions
     * @return ShoppingList
     */
    public function apply(ShoppingList $shoppingList, array $actions)
    {
        $shoppingList = $this->repository->update($shoppingList, $actions);

        return $this->dispatchPostUpdate($shoppingList, $actions);
    }

    /**
     * @param ShoppingList $shoppingList
     * @param array $actions
     * @return ShoppingList
     */
    public function dispatchPostUpdate(ShoppingList $shoppingList, array $actions)
    {
        $event = new ShoppingListPostUpdateEvent($shoppingList, $actions);
        $event = $this->dispatcher->dispatch(ShoppingListPostUpdateEvent::class, $event);

        return $event->getShoppingList();
    }
}
