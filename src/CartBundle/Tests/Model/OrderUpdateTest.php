<?php
declare(strict_types=1);

namespace Commercetools\Symfony\CartBundle\Tests\Model;

use Commercetools\Core\Model\Order\Order;
use Commercetools\Core\Request\Orders\Command\OrderAddDeliveryAction;
use Commercetools\Core\Request\Orders\Command\OrderAddParcelToDeliveryAction;
use Commercetools\Core\Request\Orders\Command\OrderAddPaymentAction;
use Commercetools\Core\Request\Orders\Command\OrderAddReturnInfoAction;
use Commercetools\Core\Request\Orders\Command\OrderChangeOrderStateAction;
use Commercetools\Core\Request\Orders\Command\OrderChangePaymentStateAction;
use Commercetools\Core\Request\Orders\Command\OrderChangeShipmentStateAction;
use Commercetools\Core\Request\Orders\Command\OrderImportCustomLineItemStateAction;
use Commercetools\Core\Request\Orders\Command\OrderImportLineItemStateAction;
use Commercetools\Core\Request\Orders\Command\OrderRemoveDeliveryAction;
use Commercetools\Core\Request\Orders\Command\OrderRemoveParcelFromDeliveryAction;
use Commercetools\Core\Request\Orders\Command\OrderRemovePaymentAction;
use Commercetools\Core\Request\Orders\Command\OrderSetBillingAddress;
use Commercetools\Core\Request\Orders\Command\OrderSetCustomerEmail;
use Commercetools\Core\Request\Orders\Command\OrderSetCustomFieldAction;
use Commercetools\Core\Request\Orders\Command\OrderSetCustomTypeAction;
use Commercetools\Core\Request\Orders\Command\OrderSetDeliveryAddressAction;
use Commercetools\Core\Request\Orders\Command\OrderSetDeliveryItemsAction;
use Commercetools\Core\Request\Orders\Command\OrderSetLocaleAction;
use Commercetools\Core\Request\Orders\Command\OrderSetOrderNumberAction;
use Commercetools\Core\Request\Orders\Command\OrderSetParcelItemsAction;
use Commercetools\Core\Request\Orders\Command\OrderSetParcelMeasurementsAction;
use Commercetools\Core\Request\Orders\Command\OrderSetParcelTrackingDataAction;
use Commercetools\Core\Request\Orders\Command\OrderSetReturnPaymentStateAction;
use Commercetools\Core\Request\Orders\Command\OrderSetReturnShipmentStateAction;
use Commercetools\Core\Request\Orders\Command\OrderSetShippingAddress;
use Commercetools\Core\Request\Orders\Command\OrderTransitionCustomLineItemStateAction;
use Commercetools\Core\Request\Orders\Command\OrderTransitionLineItemStateAction;
use Commercetools\Core\Request\Orders\Command\OrderTransitionStateAction;
use Commercetools\Core\Request\Orders\Command\OrderUpdateSyncInfoAction;
use Commercetools\Symfony\CartBundle\Manager\OrderManager;
use Commercetools\Symfony\CartBundle\Model\OrderUpdateBuilder;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

class OrderUpdateTest extends TestCase
{
    public function getActionProvider()
    {
        return [
            ['addDelivery', OrderAddDeliveryAction::class],
            ['addParcelToDelivery', OrderAddParcelToDeliveryAction::class],
            ['addPayment', OrderAddPaymentAction::class],
            ['addReturnInfo', OrderAddReturnInfoAction::class],
            ['changeOrderState', OrderChangeOrderStateAction::class],
            ['changePaymentState', OrderChangePaymentStateAction::class],
            ['changeShipmentState', OrderChangeShipmentStateAction::class],
            ['importCustomLineItemState', OrderImportCustomLineItemStateAction::class],
            ['importLineItemState', OrderImportLineItemStateAction::class],
            ['removeDelivery', OrderRemoveDeliveryAction::class],
            ['removeParcelFromDelivery', OrderRemoveParcelFromDeliveryAction::class],
            ['removePayment', OrderRemovePaymentAction::class],
            ['setBillingAddress', OrderSetBillingAddress::class],
            ['setCustomField', OrderSetCustomFieldAction::class],
//            ['setCustomLineItemCustomField', OrderSetCustomLineItemCustomFieldAction::class],
//            ['setCustomLineItemCustomType', OrderSetCustomLineItemCustomTypeAction::class],
            ['setCustomType', OrderSetCustomTypeAction::class],
            ['setCustomerEmail', OrderSetCustomerEmail::class],
            ['setDeliveryAddress', OrderSetDeliveryAddressAction::class],
            ['setDeliveryItems', OrderSetDeliveryItemsAction::class],
//            ['setLineItemCustomField', OrderSetLineItemCustomFieldAction::class],
//            ['setLineItemCustomType', OrderSetLineItemCustomTypeAction::class],
            ['setLocale', OrderSetLocaleAction::class],
            ['setOrderNumber', OrderSetOrderNumberAction::class],
            ['setParcelItems', OrderSetParcelItemsAction::class],
            ['setParcelMeasurements', OrderSetParcelMeasurementsAction::class],
            ['setParcelTrackingData', OrderSetParcelTrackingDataAction::class],
            ['setReturnPaymentState', OrderSetReturnPaymentStateAction::class],
            ['setReturnShipmentState', OrderSetReturnShipmentStateAction::class],
            ['setShippingAddress', OrderSetShippingAddress::class], // 'Action' missing in class name
            ['transitionCustomLineItemState', OrderTransitionCustomLineItemStateAction::class],
            ['transitionLineItemState', OrderTransitionLineItemStateAction::class],
            ['transitionState', OrderTransitionStateAction::class],
            ['updateSyncInfo', OrderUpdateSyncInfoAction::class],
        ];
    }

    /**
     * @dataProvider getActionProvider
     */
    public function testUpdateMethods($updateMethod, $actionClass)
    {
        $order = $this->prophesize(Order::class);

        $manager = $this->prophesize(OrderManager::class);
        $manager->apply(
            $order,
            Argument::allOf(
                Argument::containing(Argument::type($actionClass))
            )
        )->shouldBeCalledTimes(1);

        $manager->dispatch(
            $order,
            Argument::type($actionClass),
            Argument::is(null)
        )->will(function ($args) { return [$args[1]]; })->shouldBeCalledTimes(1);

        $update = new OrderUpdateBuilder($order->reveal(), $manager->reveal());

        $action = $actionClass::of();
        $update->$updateMethod($action);

        $update->flush();
    }

    /**
     * @dataProvider getActionProvider
     */
    public function testUpdateMethodsCallback($updateMethod, $actionClass)
    {
        $order = $this->prophesize(Order::class);

        $manager = $this->prophesize(OrderManager::class);
        $manager->apply(
            $order,
            Argument::allOf(
                Argument::containing(Argument::type($actionClass))
            )
        )->shouldBeCalledTimes(1);

        $manager->dispatch(
            $order,
            Argument::type($actionClass),
            Argument::is(null)
        )->will(function ($args) { return [$args[1]]; })->shouldBeCalledTimes(1);

        $update = new OrderUpdateBuilder($order->reveal(), $manager->reveal());

        $callback = function ($action) use ($actionClass) {
            static::assertInstanceOf($actionClass, $action);
            return $action;
        };
        $update->$updateMethod($callback);

        $update->flush();
    }
}
