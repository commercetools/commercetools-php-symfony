<?php
/**
 */

namespace Commercetools\Symfony\CartBundle\Event;

use Commercetools\Core\Model\Cart\Cart;
use Commercetools\Core\Request\AbstractAction;
use Symfony\Component\EventDispatcher\Event;

class CartUpdateEvent extends Event
{
    /**
     * @var Cart
     */
    private $cart;

    /**
     * @var AbstractAction[]
     */
    private $actions;

    public function __construct(Cart $cart, AbstractAction $action)
    {
        $this->shoppingList = $cart;
        $this->actions = [$action];
    }

    /**
     * @return Cart
     */
    public function getCart()
    {
        return $this->cart;
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
