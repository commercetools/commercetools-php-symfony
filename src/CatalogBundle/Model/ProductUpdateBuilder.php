<?php
/**
 */

namespace Commercetools\Symfony\CatalogBundle\Model;


use Commercetools\Core\Builder\Update\ProductsActionBuilder;
use Commercetools\Core\Model\Product\Product;
use Commercetools\Core\Request\AbstractAction;
use Commercetools\Symfony\CatalogBundle\Manager\CatalogManager;

class ProductUpdateBuilder extends ProductsActionBuilder
{
    /**
     * @var CatalogManager
     */
    private $manager;

    /**
     * @var Product
     */
    private $product;

    /**
     * ProductUpdate constructor.
     * @param Product $product
     * @param CatalogManager $manager
     */
    public function __construct(Product $product, CatalogManager $manager)
    {
        $this->manager = $manager;
        $this->product = $product;
    }


    public function addAction(AbstractAction $action, $eventName = null)
    {
        $actions = $this->manager->dispatch($this->product, $action, $eventName);

        $this->setActions(array_merge($this->getActions(), $actions));

        return $this;
    }

    /**
     * @return Product
     */
    public function flush()
    {
        return $this->manager->apply($this->product, $this->getActions());
    }
}
