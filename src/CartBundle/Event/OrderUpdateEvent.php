<?php
/**
 */

namespace Commercetools\Symfony\CartBundle\Event;

use Commercetools\Core\Model\Order\Order;
use Commercetools\Core\Request\AbstractAction;
use Symfony\Component\EventDispatcher\Event;

class OrderUpdateEvent extends Event
{
    /**
     * @var Order
     */
    private $order;

    /**
     * @var AbstractAction[]
     */
    private $actions;

    public function __construct(Order $order, AbstractAction $action)
    {
        $this->order = $order;
        $this->actions = [$action];
    }

    /**
     * @return Order
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @return AbstractAction[]
     */
    public function getActions()
    {
        return $this->actions;
    }

    /**
     * @param array $actions
     */
    public function setActions(array $actions)
    {
        $this->actions = $actions;
    }

    /**
     * @param AbstractAction $action
     */
    public function addAction(AbstractAction $action)
    {
        $this->actions[] = $action;
    }
}
