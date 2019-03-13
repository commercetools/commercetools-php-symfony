<?php
declare(strict_types=1);

namespace Commercetools\Symfony\CartBundle\Tests\Model;

use Commercetools\Core\Model\Cart\Cart;
use Commercetools\Core\Request\Carts\Command\CartAddCustomLineItemAction;
use Commercetools\Core\Request\Carts\Command\CartAddDiscountCodeAction;
use Commercetools\Core\Request\Carts\Command\CartAddLineItemAction;
use Commercetools\Core\Request\Carts\Command\CartAddPaymentAction;
use Commercetools\Core\Request\Carts\Command\CartAddShoppingListAction;
use Commercetools\Core\Request\Carts\Command\CartChangeCustomLineItemMoneyAction;
use Commercetools\Core\Request\Carts\Command\CartChangeCustomLineItemQuantityAction;
use Commercetools\Core\Request\Carts\Command\CartChangeLineItemQuantityAction;
use Commercetools\Core\Request\Carts\Command\CartChangeTaxCalculationModeAction;
use Commercetools\Core\Request\Carts\Command\CartChangeTaxModeAction;
use Commercetools\Core\Request\Carts\Command\CartChangeTaxRoundingModeAction;
use Commercetools\Core\Request\Carts\Command\CartRecalculateAction;
use Commercetools\Core\Request\Carts\Command\CartRemoveCustomLineItemAction;
use Commercetools\Core\Request\Carts\Command\CartRemoveDiscountCodeAction;
use Commercetools\Core\Request\Carts\Command\CartRemoveLineItemAction;
use Commercetools\Core\Request\Carts\Command\CartRemovePaymentAction;
use Commercetools\Core\Request\Carts\Command\CartSetAnonymousIdAction;
use Commercetools\Core\Request\Carts\Command\CartSetBillingAddressAction;
use Commercetools\Core\Request\Carts\Command\CartSetCartTotalTaxAction;
use Commercetools\Core\Request\Carts\Command\CartSetCountryAction;
use Commercetools\Core\Request\Carts\Command\CartSetCustomerEmailAction;
use Commercetools\Core\Request\Carts\Command\CartSetCustomerGroupAction;
use Commercetools\Core\Request\Carts\Command\CartSetCustomerIdAction;
use Commercetools\Core\Request\Carts\Command\CartSetCustomFieldAction;
use Commercetools\Core\Request\Carts\Command\CartSetCustomLineItemCustomFieldAction;
use Commercetools\Core\Request\Carts\Command\CartSetCustomLineItemCustomTypeAction;
use Commercetools\Core\Request\Carts\Command\CartSetCustomLineItemTaxAmountAction;
use Commercetools\Core\Request\Carts\Command\CartSetCustomLineItemTaxRateAction;
use Commercetools\Core\Request\Carts\Command\CartSetCustomShippingMethodAction;
use Commercetools\Core\Request\Carts\Command\CartSetCustomTypeAction;
use Commercetools\Core\Request\Carts\Command\CartSetDeleteDaysAfterLastModificationAction;
use Commercetools\Core\Request\Carts\Command\CartSetLineItemCustomFieldAction;
use Commercetools\Core\Request\Carts\Command\CartSetLineItemCustomTypeAction;
use Commercetools\Core\Request\Carts\Command\CartSetLineItemPriceAction;
use Commercetools\Core\Request\Carts\Command\CartSetLineItemTaxAmountAction;
use Commercetools\Core\Request\Carts\Command\CartSetLineItemTaxRateAction;
use Commercetools\Core\Request\Carts\Command\CartSetLineItemTotalPriceAction;
use Commercetools\Core\Request\Carts\Command\CartSetLocaleAction;
use Commercetools\Core\Request\Carts\Command\CartSetShippingAddressAction;
use Commercetools\Core\Request\Carts\Command\CartSetShippingMethodAction;
use Commercetools\Core\Request\Carts\Command\CartSetShippingMethodTaxAmountAction;
use Commercetools\Core\Request\Carts\Command\CartSetShippingMethodTaxRateAction;
use Commercetools\Core\Request\Carts\Command\CartSetShippingRateInputAction;
use Commercetools\Symfony\CartBundle\Manager\CartManager;
use Commercetools\Symfony\CartBundle\Model\CartUpdateBuilder;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

class CartUpdateTest extends TestCase
{
    public function getActionProvider()
    {
        return [
            ['addCustomLineItem', CartAddCustomLineItemAction::class],
            ['addDiscountCode', CartAddDiscountCodeAction::class],
            ['addLineItem', CartAddLineItemAction::class],
            ['addPayment', CartAddPaymentAction::class],
            ['addShoppingList', CartAddShoppingListAction::class],
            ['changeCustomLineItemMoney', CartChangeCustomLineItemMoneyAction::class],
            ['changeCustomLineItemQuantity', CartChangeCustomLineItemQuantityAction::class],
            ['changeLineItemQuantity', CartChangeLineItemQuantityAction::class],
            ['changeTaxCalculationMode', CartChangeTaxCalculationModeAction::class],
            ['changeTaxMode', CartChangeTaxModeAction::class],
            ['changeTaxRoundingMode', CartChangeTaxRoundingModeAction::class],
            ['recalculate', CartRecalculateAction::class],
            ['removeCustomLineItem', CartRemoveCustomLineItemAction::class],
            ['removeDiscountCode', CartRemoveDiscountCodeAction::class],
            ['removeLineItem', CartRemoveLineItemAction::class],
            ['removePayment', CartRemovePaymentAction::class],
            ['setAnonymousId', CartSetAnonymousIdAction::class],
            ['setBillingAddress', CartSetBillingAddressAction::class],
            ['setCartTotalTax', CartSetCartTotalTaxAction::class],
            ['setCountry', CartSetCountryAction::class],
            ['setCustomField', CartSetCustomFieldAction::class],
            ['setCustomLineItemCustomField', CartSetCustomLineItemCustomFieldAction::class],
            ['setCustomLineItemCustomType', CartSetCustomLineItemCustomTypeAction::class],
            ['setCustomLineItemTaxAmount', CartSetCustomLineItemTaxAmountAction::class],
            ['setCustomLineItemTaxRate', CartSetCustomLineItemTaxRateAction::class],
            ['setCustomShippingMethod', CartSetCustomShippingMethodAction::class],
            ['setCustomType', CartSetCustomTypeAction::class],
            ['setCustomerEmail', CartSetCustomerEmailAction::class],
            ['setCustomerGroup', CartSetCustomerGroupAction::class],
            ['setCustomerId', CartSetCustomerIdAction::class],
            ['setDeleteDaysAfterLastModification', CartSetDeleteDaysAfterLastModificationAction::class],
            ['setLineItemCustomField', CartSetLineItemCustomFieldAction::class],
            ['setLineItemCustomType', CartSetLineItemCustomTypeAction::class],
            ['setLineItemPrice', CartSetLineItemPriceAction::class],
            ['setLineItemTaxAmount', CartSetLineItemTaxAmountAction::class],
            ['setLineItemTaxRate', CartSetLineItemTaxRateAction::class],
            ['setLineItemTotalPrice', CartSetLineItemTotalPriceAction::class],
            ['setLocale', CartSetLocaleAction::class],
            ['setShippingAddress', CartSetShippingAddressAction::class],
            ['setShippingMethod', CartSetShippingMethodAction::class],
            ['setShippingMethodTaxAmount', CartSetShippingMethodTaxAmountAction::class],
            ['setShippingMethodTaxRate', CartSetShippingMethodTaxRateAction::class],
            ['setShippingRateInput', CartSetShippingRateInputAction::class],
        ];
    }

    /**
     * @dataProvider getActionProvider
     */
    public function testUpdateMethods($updateMethod, $actionClass)
    {
        $cart = $this->prophesize(Cart::class);

        $manager = $this->prophesize(CartManager::class);
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
        )->will(function ($args) {
            return [$args[1]];
        })->shouldBeCalledTimes(1);

        $update = new CartUpdateBuilder($cart->reveal(), $manager->reveal());

        $action = $actionClass::of();
        $update->$updateMethod($action);

        $update->flush();
    }

    /**
     * @dataProvider getActionProvider
     */
    public function testUpdateMethodsCallback($updateMethod, $actionClass)
    {
        $cart = $this->prophesize(Cart::class);

        $manager = $this->prophesize(CartManager::class);
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
        )->will(function ($args) {
            return [$args[1]];
        })->shouldBeCalledTimes(1);

        $update = new CartUpdateBuilder($cart->reveal(), $manager->reveal());

        $callback = function ($action) use ($actionClass) {
            static::assertInstanceOf($actionClass, $action);
            return $action;
        };
        $update->$updateMethod($callback);

        $update->flush();
    }
}
