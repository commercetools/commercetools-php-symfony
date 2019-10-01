<?php
/**
 */

namespace Commercetools\Symfony\CatalogBundle\Event;

use Commercetools\Core\Model\Product\Product;
use Commercetools\Core\Request\AbstractAction;
use Symfony\Contracts\EventDispatcher\Event;

class ProductUpdateEvent extends Event
{
    /**
     * @var Product
     */
    private $product;

    /**
     * @var AbstractAction[]
     */
    private $actions;

    public function __construct(Product $product, AbstractAction $action)
    {
        $this->product = $product;
        $this->actions = [$action];
    }

    /**
     * @return Product
     */
    public function getProduct()
    {
        return $this->product;
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
