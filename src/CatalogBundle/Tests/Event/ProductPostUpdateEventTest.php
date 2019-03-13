<?php
/**
 *
 */

namespace Commercetools\Symfony\CatalogBundle\Tests\Event;

use Commercetools\Core\Model\Product\Product;
use Commercetools\Core\Request\Products\Command\ProductSetKeyAction;
use Commercetools\Symfony\CatalogBundle\Event\ProductPostUpdateEvent;
use PHPUnit\Framework\TestCase;

class ProductPostUpdateEventTest extends TestCase
{
    public function testProductPostUpdateEvent()
    {
        $event = new ProductPostUpdateEvent(Product::of(), [ProductSetKeyAction::of()]);
        $this->assertInstanceOf(Product::class, $event->getProduct());
        $this->assertInstanceOf(ProductSetKeyAction::class, current($event->getActions()));
    }
}
