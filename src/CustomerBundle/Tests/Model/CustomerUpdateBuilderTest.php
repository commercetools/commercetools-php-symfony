<?php
declare(strict_types=1);

namespace Commercetools\Symfony\CustomerBundle\Tests\Model;

use Commercetools\Core\Model\Customer\Customer;

use Commercetools\Core\Request\Customers\Command\CustomerAddAddressAction;
use Commercetools\Core\Request\Customers\Command\CustomerAddBillingAddressAction;
use Commercetools\Core\Request\Customers\Command\CustomerChangeAddressAction;
use Commercetools\Core\Request\Customers\Command\CustomerChangeEmailAction;
use Commercetools\Core\Request\Customers\Command\CustomerRemoveAddressAction;
use Commercetools\Core\Request\Customers\Command\CustomerSetCompanyNameAction;
use Commercetools\Core\Request\Customers\Command\CustomerSetCustomerGroupAction;
use Commercetools\Core\Request\Customers\Command\CustomerSetCustomerNumberAction;
use Commercetools\Core\Request\Customers\Command\CustomerSetDateOfBirthAction;
use Commercetools\Core\Request\Customers\Command\CustomerSetDefaultBillingAddressAction;
use Commercetools\Core\Request\Customers\Command\CustomerSetDefaultShippingAddressAction;
use Commercetools\Core\Request\Customers\Command\CustomerSetExternalIdAction;
use Commercetools\Core\Request\Customers\Command\CustomerSetFirstNameAction;
use Commercetools\Core\Request\Customers\Command\CustomerSetKeyAction;
use Commercetools\Core\Request\Customers\Command\CustomerSetLastNameAction;
use Commercetools\Core\Request\Customers\Command\CustomerSetLocaleAction;
use Commercetools\Core\Request\Customers\Command\CustomerSetMiddleNameAction;
use Commercetools\Core\Request\Customers\Command\CustomerSetSalutationAction;
use Commercetools\Core\Request\Customers\Command\CustomerSetTitleAction;
use Commercetools\Core\Request\Customers\Command\CustomerSetVatIdAction;
use Commercetools\Core\Request\Customers\Command\CustomerAddShippingAddressAction;
use Commercetools\Core\Request\Customers\Command\CustomerRemoveBillingAddressAction;
use Commercetools\Core\Request\Customers\Command\CustomerRemoveShippingAddressAction;
//use Commercetools\Core\Request\Customers\Command\CustomerSetCustomFieldAction;
//use Commercetools\Core\Request\Customers\Command\CustomerSetCustomTypeAction;
use Commercetools\Symfony\CustomerBundle\Manager\CustomerManager;
use Commercetools\Symfony\CustomerBundle\Model\CustomerUpdateBuilder;
use Prophecy\Argument;

class CustomerUpdateBuilderTest extends \PHPUnit_Framework_TestCase
{
    public function getActionProvider()
    {
        return [
           ['addAddress', CustomerAddAddressAction::class],
           ['addBillingAddressId', CustomerAddBillingAddressAction::class],
           ['addShippingAddressId', CustomerAddShippingAddressAction::class],
           ['changeAddress', CustomerChangeAddressAction::class],
           ['changeEmail', CustomerChangeEmailAction::class],
           ['removeAddress', CustomerRemoveAddressAction::class],
           ['removeBillingAddressId', CustomerRemoveBillingAddressAction::class],
           ['removeShippingAddressId', CustomerRemoveShippingAddressAction::class],
           ['setCompanyName', CustomerSetCompanyNameAction::class],
//           ['setCustomField', CustomerSetCustomFieldAction::class],
//           ['setCustomType', CustomerSetCustomTypeAction::class],
           ['setCustomerGroup', CustomerSetCustomerGroupAction::class],
           ['setCustomerNumber', CustomerSetCustomerNumberAction::class],
           ['setDateOfBirth', CustomerSetDateOfBirthAction::class],
           ['setDefaultBillingAddress', CustomerSetDefaultBillingAddressAction::class],
           ['setDefaultShippingAddress', CustomerSetDefaultShippingAddressAction::class],
           ['setExternalId', CustomerSetExternalIdAction::class],
           ['setFirstName', CustomerSetFirstNameAction::class],
           ['setKey', CustomerSetKeyAction::class],
           ['setLastName', CustomerSetLastNameAction::class],
           ['setLocale', CustomerSetLocaleAction::class],
           ['setMiddleName', CustomerSetMiddleNameAction::class],
           ['setSalutation', CustomerSetSalutationAction::class],
           ['setTitle', CustomerSetTitleAction::class],
           ['setVatId', CustomerSetVatIdAction::class]
        ];
    }

    /**
     * @dataProvider getActionProvider
     */
    public function testUpdateMethods($updateMethod, $actionClass)
    {
        $customer = $this->prophesize(Customer::class);

        $manager = $this->prophesize(CustomerManager::class);
        $manager->apply(
            $customer,
            Argument::allOf(
                Argument::containing(Argument::type($actionClass))
            )
        )->shouldBeCalledTimes(1);

        $manager->dispatch(
            $customer,
            Argument::type($actionClass),
            Argument::is(null)
        )->will(function ($args) { return [$args[1]]; })->shouldBeCalledTimes(1);

        $update = new CustomerUpdateBuilder($customer->reveal(), $manager->reveal());

        $action = $actionClass::of();
        $update->$updateMethod($action);

        $update->flush();
    }

    /**
     * @dataProvider getActionProvider
     */
    public function testUpdateMethodsCallback($updateMethod, $actionClass)
    {
        $customer = $this->prophesize(Customer::class);

        $manager = $this->prophesize(CustomerManager::class);
        $manager->apply(
            $customer,
            Argument::allOf(
                Argument::containing(Argument::type($actionClass))
            )
        )->shouldBeCalledTimes(1);

        $manager->dispatch(
            $customer,
            Argument::type($actionClass),
            Argument::is(null)
        )->will(function ($args) { return [$args[1]]; })->shouldBeCalledTimes(1);

        $update = new CustomerUpdateBuilder($customer->reveal(), $manager->reveal());

        $callback = function ($action) use ($actionClass) {
            static::assertInstanceOf($actionClass, $action);
            return $action;
        };
        $update->$updateMethod($callback);

        $update->flush();
    }
}
