<?php
declare(strict_types=1);

namespace Commercetools\Symfony\CartBundle\Tests\Model;

use Commercetools\Core\Model\Payment\Payment;
use Commercetools\Core\Request\Payments\Command\PaymentAddInterfaceInteractionAction;
use Commercetools\Core\Request\Payments\Command\PaymentAddTransactionAction;
use Commercetools\Core\Request\Payments\Command\PaymentChangeAmountPlannedAction;
use Commercetools\Core\Request\Payments\Command\PaymentChangeTransactionInteractionIdAction;
use Commercetools\Core\Request\Payments\Command\PaymentChangeTransactionStateAction;
use Commercetools\Core\Request\Payments\Command\PaymentChangeTransactionTimestampAction;
use Commercetools\Core\Request\Payments\Command\PaymentSetAmountPaidAction;
use Commercetools\Core\Request\Payments\Command\PaymentSetAmountRefundedAction;
use Commercetools\Core\Request\Payments\Command\PaymentSetAnonymousIdAction;
use Commercetools\Core\Request\Payments\Command\PaymentSetAuthorizationAction;
use Commercetools\Core\Request\Payments\Command\PaymentSetCustomFieldAction;
use Commercetools\Core\Request\Payments\Command\PaymentSetCustomTypeAction;
use Commercetools\Core\Request\Payments\Command\PaymentSetCustomerAction;
use Commercetools\Core\Request\Payments\Command\PaymentSetExternalIdAction;
use Commercetools\Core\Request\Payments\Command\PaymentSetInterfaceIdAction;
use Commercetools\Core\Request\Payments\Command\PaymentSetKeyAction;
use Commercetools\Core\Request\Payments\Command\PaymentSetMethodInfoInterfaceAction;
use Commercetools\Core\Request\Payments\Command\PaymentSetMethodInfoMethodAction;
use Commercetools\Core\Request\Payments\Command\PaymentSetMethodInfoNameAction;
use Commercetools\Core\Request\Payments\Command\PaymentSetStatusInterfaceCodeAction;
use Commercetools\Core\Request\Payments\Command\PaymentSetStatusInterfaceTextAction;
use Commercetools\Core\Request\Payments\Command\PaymentTransitionStateAction;
use Commercetools\Symfony\CartBundle\Manager\PaymentManager;
use Commercetools\Symfony\CartBundle\Model\PaymentUpdateBuilder;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

class PaymentUpdateTest extends TestCase
{
    public function getActionProvider()
    {
        return [
            ['addInterfaceInteraction', PaymentAddInterfaceInteractionAction::class],
            ['addTransaction', PaymentAddTransactionAction::class]
        ];
    }

    /**
     * @dataProvider getActionProvider
     */
    public function testUpdateMethods($updateMethod, $actionClass)
    {
        $payment = $this->prophesize(Payment::class);

        $manager = $this->prophesize(PaymentManager::class);
        $manager->apply(
            $payment,
            Argument::allOf(
                Argument::containing(Argument::type($actionClass))
            )
        )->shouldBeCalledTimes(1);

        $manager->dispatch(
            $payment,
            Argument::type($actionClass),
            Argument::is(null)
        )->will(function ($args) {
            return [$args[1]];
        })->shouldBeCalledTimes(1);

        $update = new PaymentUpdateBuilder($payment->reveal(), $manager->reveal());

        $action = $actionClass::of();
        $update->$updateMethod($action);

        $update->flush();
    }

    /**
     * @dataProvider getActionProvider
     */
    public function testUpdateMethodsCallback($updateMethod, $actionClass)
    {
        $payment = $this->prophesize(Payment::class);

        $manager = $this->prophesize(PaymentManager::class);
        $manager->apply(
            $payment,
            Argument::allOf(
                Argument::containing(Argument::type($actionClass))
            )
        )->shouldBeCalledTimes(1);

        $manager->dispatch(
            $payment,
            Argument::type($actionClass),
            Argument::is(null)
        )->will(function ($args) {
            return [$args[1]];
        })->shouldBeCalledTimes(1);

        $update = new PaymentUpdateBuilder($payment->reveal(), $manager->reveal());

        $callback = function ($action) use ($actionClass) {
            static::assertInstanceOf($actionClass, $action);
            return $action;
        };
        $update->$updateMethod($callback);

        $update->flush();
    }
}
