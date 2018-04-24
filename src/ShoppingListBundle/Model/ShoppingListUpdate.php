<?php
/**
 * Created by PhpStorm.
 * User: nsotiropoulos
 * Date: 18/04/2018
 * Time: 14:51
 */

namespace Commercetools\Symfony\ShoppingListBundle\Model;


use Commercetools\Core\Builder\Request\RequestBuilder;
use Commercetools\Core\Builder\Update\ActionBuilder;
use Commercetools\Core\Builder\Update\ShoppingListsActionBuilder;
use Commercetools\Core\Model\ShoppingList\ShoppingList;
use Commercetools\Core\Request\AbstractAction;
use Commercetools\Core\Request\ShoppingLists\Command\ShoppingListAddLineItemAction;
use Commercetools\Core\Request\ShoppingLists\Command\ShoppingListChangeNameAction;
use Commercetools\Symfony\ShoppingListBundle\Event\ShoppingListUpdateEvent;
use Commercetools\Symfony\ShoppingListBundle\Manager\ShoppingListManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use InvalidArgumentException;

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

    /**
     * @param $class
     * @param $action
     * @return AbstractAction
     * @throws InvalidArgumentException
     */
    private function resolveAction($class, $action = null)
    {
        if (is_null($action) || is_callable($action)) {
            $callback = $action;
            $emptyAction = $class::of();
            $action = $this->callback($emptyAction, $callback);
        }
        if ($action instanceof $class) {
            return $action;
        }
        throw new InvalidArgumentException(
            sprintf('Expected method to be called with or callable to return %s', $class)
        );
    }

    /**
     * @param $action
     * @param callable $callback
     * @return AbstractAction
     */
    private function callback($action, callable $callback = null)
    {
        if (!is_null($callback)) {
            $action = $callback($action);
        }
        return $action;
    }

    /**
     * @param ShoppingListAddLineItemAction|callable $action
     * @return $this
     */
    public function addLineItem($action)
    {
        $this->addAction($this->resolveAction(ShoppingListAddLineItemAction::class, $action));

        return $this;
    }

    public function changeName($action)
    {
        $this->addAction($this->resolveAction(ShoppingListChangeNameAction::class, $action));

        return $this;
    }

    public function addAction(AbstractAction $action, $eventName = null)
    {
        $eventName = is_null($eventName) ? get_class($action) : $eventName;

        $event = new ShoppingListUpdateEvent($this->shoppingList, $action);
        $event = $this->dispatcher->dispatch($eventName, $event);

        return $this->actions = array_merge($this->actions, $event->getActions());
    }

    /**
     * @return ShoppingList
     */
    public function flush()
    {
        return $this->manager->apply($this->shoppingList, $this->actions);
    }
}