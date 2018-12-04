<?php
/**
 *
 */

namespace Commercetools\Symfony\CatalogBundle\Tests\Event;



use Commercetools\Core\Model\Product\Product;
use Commercetools\Core\Request\Products\Command\ProductAddPriceAction;
use Commercetools\Core\Request\Products\Command\ProductSetKeyAction;
use Commercetools\Core\Request\Products\Command\ProductSetSkuAction;
use Commercetools\Symfony\CatalogBundle\Event\ProductUpdateEvent;
use PHPUnit\Framework\TestCase;

class ProductUpdateEventTest extends TestCase
{
    public function testPaymentUpdateEvent()
    {
        $event = new ProductUpdateEvent(Product::of(), ProductSetKeyAction::of());
        $this->assertInstanceOf(Product::class, $event->getProduct());
        $this->assertSame(1, count($event->getActions()));
        $this->assertInstanceOf(ProductSetKeyAction::class, current($event->getActions()));

        $event->addAction(ProductSetSkuAction::of());
        $this->assertSame(2, count($event->getActions()));

        $event->setActions([ProductAddPriceAction::of()]);
        $this->assertSame(1, count($event->getActions()));
        $this->assertInstanceOf(ProductAddPriceAction::class, current($event->getActions()));
    }
}
