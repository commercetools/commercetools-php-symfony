<?php
/**
 * Created by PhpStorm.
 * User: nsotiropoulos
 * Date: 18/04/2018
 * Time: 14:51
 */

namespace Commercetools\Symfony\CartBundle\Model;


use Commercetools\Core\Builder\Update\CartsActionBuilder;
use Commercetools\Core\Model\Cart\Cart;
use Commercetools\Core\Request\AbstractAction;
use Commercetools\Symfony\CartBundle\Manager\CartManager;

class CartUpdateBuilder extends CartsActionBuilder
{
    /**
     * @var CartManager
     */
    private $manager;

    /**
     * @var Cart
     */
    private $cart;

    /**
     * CartUpdate constructor.
     * @param CartManager $manager
     * @param Cart $cart
     */
    public function __construct(Cart $cart, CartManager $manager)
    {
        $this->manager = $manager;
        $this->cart = $cart;
    }


    public function addAction(AbstractAction $action, $eventName = null)
    {
        $actions = $this->manager->dispatch($this->cart, $action, $eventName);

        $this->setActions(array_merge($this->getActions(), $actions));

        return $this;
    }

    /**
     * @return Cart
     */
    public function flush()
    {
        return $this->manager->apply($this->cart, $this->getActions());
    }
}
