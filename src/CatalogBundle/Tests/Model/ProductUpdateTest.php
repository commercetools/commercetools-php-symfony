<?php
/**
 *
 */

namespace Commercetools\Symfony\CatalogBundle\Tests\Model;


use Commercetools\Core\Model\Product\Product;
use Commercetools\Core\Request\Products\Command\ProductAddPriceAction;
use Commercetools\Core\Request\Products\Command\ProductSetKeyAction;
use Commercetools\Symfony\CatalogBundle\Model\ProductUpdateBuilder;
use Commercetools\Symfony\CatalogBundle\Manager\CatalogManager;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

class ProductUpdateTest extends TestCase
{
    public function getActionProvider()
    {
        return [
            ['setKey', ProductSetKeyAction::class],
            ['addPrice', ProductAddPriceAction::class],

        ];
    }

    /**
     * @dataProvider getActionProvider
     */
    public function testUpdateMethods($updateMethod, $actionClass)
    {
        $cart = $this->prophesize(Product::class);

        $manager = $this->prophesize(CatalogManager::class);
        $manager->apply(
            $cart,
            Argument::allOf(
                Argument::containing(Argument::type($actionClass))
            )
        )->shouldBeCalledTimes(1);

        $manager->dispatch(
            $cart,
            Argument::type($actionClass),
            Argument::is(null)
        )->will(function ($args) { return [$args[1]]; })->shouldBeCalledTimes(1);

        $update = new ProductUpdateBuilder($cart->reveal(), $manager->reveal());

        $action = $actionClass::of();
        $update->$updateMethod($action);

        $update->flush();
    }

    /**
     * @dataProvider getActionProvider
     */
    public function testUpdateMethodsCallback($updateMethod, $actionClass)
    {
        $cart = $this->prophesize(Product::class);

        $manager = $this->prophesize(CatalogManager::class);
        $manager->apply(
            $cart,
            Argument::allOf(
                Argument::containing(Argument::type($actionClass))
            )
        )->shouldBeCalledTimes(1);

        $manager->dispatch(
            $cart,
            Argument::type($actionClass),
            Argument::is(null)
        )->will(function ($args) { return [$args[1]]; })->shouldBeCalledTimes(1);

        $update = new ProductUpdateBuilder($cart->reveal(), $manager->reveal());

        $callback = function ($action) use ($actionClass) {
            static::assertInstanceOf($actionClass, $action);
            return $action;
        };
        $update->$updateMethod($callback);

        $update->flush();
    }
}
