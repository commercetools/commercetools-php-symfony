<?php
/**
 *
 */

namespace Commercetools\Symfony\CartBundle\Manager;

use Commercetools\Core\Model\Cart\Cart;
use Commercetools\Core\Request\AbstractAction;
use Commercetools\Symfony\CartBundle\Model\CartUpdateBuilder;

interface CartManagerInterface
{
    /**
     * @param Cart $cart
     * @return CartUpdateBuilder
     */
    public function update(Cart $cart);


    /**
     * @param Cart $cart
     * @param AbstractAction $action
     * @param string|null $eventName
     * @return AbstractAction[]
     */
    public function dispatch(Cart $cart, AbstractAction $action, $eventName = null);


    /**
     * @param Cart $cart
     * @param array $actions
     * @return Cart
     */
    public function apply(Cart $cart, array $actions);

    /**
     * @param Cart $cart
     * @param array $actions
     * @return AbstractAction[]
     */
    public function dispatchPostUpdate(Cart $cart, array $actions);


    /**
     * @param Cart|null $cart
     */
    public function dispatchPostGet(Cart $cart = null);
}
