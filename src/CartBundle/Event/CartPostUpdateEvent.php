<?php
/**
 * Created by PhpStorm.
 * User: nsotiropoulos
 * Date: 17/04/2018
 * Time: 15:13
 */

namespace Commercetools\Symfony\CartBundle\Event;

use Commercetools\Core\Model\Cart\Cart;
use Commercetools\Core\Request\AbstractAction;
use Symfony\Component\EventDispatcher\Event;

class CartPostUpdateEvent extends Event
{
    /**
     * @var Cart
     */
    private $cart;

    /**
     * @var AbstractAction[]
     */
    private $actions;

    public function __construct(Cart $cart, array $actions)
    {
        $this->cart = $cart;
        $this->actions = $actions;
    }

    /**
     * @return AbstractAction[]
     */
    public function getActions()
    {
        return $this->actions;
    }
}
