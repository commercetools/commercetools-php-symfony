<?php
/**
 */

namespace Commercetools\Symfony\CartBundle\Model;

use Commercetools\Core\Builder\Update\CartsActionBuilder;
use Commercetools\Core\Model\Cart\Cart;
use Commercetools\Core\Request\AbstractAction;
use Commercetools\Symfony\CartBundle\Manager\CartManagerInterface;

class CartUpdateBuilder extends CartsActionBuilder
{
    /**
     * @var CartManagerInterface
     */
    private $manager;

    /**
     * @var Cart
     */
    private $cart;

    /**
     * CartUpdate constructor.
     * @param CartManagerInterface $manager
     * @param Cart $cart
     */
    public function __construct(Cart $cart, CartManagerInterface $manager)
    {
        $this->manager = $manager;
        $this->cart = $cart;
    }

    /**
     * @param AbstractAction $action
     * @param string|null $eventName
     * @return $this
     */
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
