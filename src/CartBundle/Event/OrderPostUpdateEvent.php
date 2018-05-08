<?php
/**
 */

namespace Commercetools\Symfony\CartBundle\Event;

use Commercetools\Core\Model\Order\Order;
use Commercetools\Core\Request\AbstractAction;
use Symfony\Component\EventDispatcher\Event;

class OrderPostUpdateEvent extends Event
{
    /**
     * @var Order
     */
    private $order;

    /**
     * @var AbstractAction[]
     */
    private $actions;

    public function __construct(Order $order, array $actions)
    {
        $this->order = $order;
        $this->actions = $actions;
    }

    /**
     * @return AbstractAction[]
     */
    public function getActions()
    {
        return $this->actions;
    }

    /**
     * @return Order
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @param Order $order
     */
    public function setOrder(Order $order)
    {
        $this->order = $order;
    }
}
