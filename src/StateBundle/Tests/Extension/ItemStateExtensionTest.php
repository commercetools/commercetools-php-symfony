<?php
/**
 *
 */

namespace Commercetools\Symfony\StateBundle\Tests\Extension;


use Commercetools\Core\Model\Cart\LineItem;
use Commercetools\Core\Model\Order\Order;
use Commercetools\Core\Model\State\StateReference;
use Commercetools\Symfony\StateBundle\Cache\StateKeyResolver;
use Commercetools\Symfony\StateBundle\Extension\ItemStateExtension;
use Commercetools\Symfony\StateBundle\Model\ItemStateWrapper;
use PHPUnit\Framework\TestCase;
use Twig\TwigFunction;

class ItemStateExtensionTest extends TestCase
{
    private $itemStateExtension;

    public function setUp()
    {
        $stateKeyResolver = $this->prophesize(StateKeyResolver::class);
        $this->itemStateExtension = new ItemStateExtension($stateKeyResolver->reveal());
    }

    public function testGetFunctions()
    {
        $functions = $this->itemStateExtension->getFunctions();
        $this->assertNotEmpty($functions);

        foreach ($functions as $function) {
            $this->assertInstanceOf(TwigFunction::class, $function);
        }
    }

    public function testWrapItemState()
    {
        $wrapped = $this->itemStateExtension->wrapItemState(Order::of(), StateReference::of(), LineItem::of());
        $this->assertInstanceOf(ItemStateWrapper::class, $wrapped);
    }
}
