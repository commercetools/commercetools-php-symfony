<?php
/**
 */

namespace Commercetools\Symfony\CatalogBundle\Event;

use Commercetools\Core\Model\Product\Product;
use Commercetools\Core\Request\AbstractAction;
use Symfony\Component\EventDispatcher\Event;

class ProductPostUpdateEvent extends Event
{
    /**
     * @var Product
     */
    private $product;

    /**
     * @var AbstractAction[]
     */
    private $actions;

    public function __construct(Product $product, array $actions)
    {
        $this->product = $product;
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
     * @return Product
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * @param Product $product
     */
    public function setProduct(Product $product)
    {
        $this->product = $product;
    }
}
