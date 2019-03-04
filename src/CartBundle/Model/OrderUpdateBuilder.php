<?php
/**
 */

namespace Commercetools\Symfony\CartBundle\Model;


use Commercetools\Core\Builder\Update\OrdersActionBuilder;
use Commercetools\Core\Model\Order\Order;
use Commercetools\Core\Request\AbstractAction;
use Commercetools\Symfony\CartBundle\Manager\OrderManager;

class OrderUpdateBuilder extends OrdersActionBuilder
{
    /**
     * @var OrderManager
     */
    private $manager;

    /**
     * @var Order
     */
    private $order;

    /**
     * OrderUpdate constructor.
     * @param OrderManager $manager
     * @param Order $order
     */
    public function __construct(Order $order, OrderManager $manager)
    {
        $this->manager = $manager;
        $this->order = $order;
    }

    /**
     * @param AbstractAction $action
     * @param string|null $eventName
     * @return $this
     */
    public function addAction(AbstractAction $action, $eventName = null)
    {
        $actions = $this->manager->dispatch($this->order, $action, $eventName);

        $this->setActions(array_merge($this->getActions(), $actions));

        return $this;
    }

    /**
     * @return Order
     */
    public function flush()
    {
        return $this->manager->apply($this->order, $this->getActions());
    }
}
