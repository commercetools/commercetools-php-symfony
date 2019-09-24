<?php
/**
 *
 */

namespace Commercetools\Symfony\CustomerBundle\Manager;

use Commercetools\Core\Model\Customer\Customer;
use Commercetools\Core\Request\AbstractAction;
use Commercetools\Symfony\CustomerBundle\Model\CustomerUpdateBuilder;

interface CustomerManagerInterface
{
    /**
     * @param Customer $customer
     * @return CustomerUpdateBuilder
     */
    public function update(Customer $customer);

    /**
     * @param Customer $customer
     * @param AbstractAction $action
     * @param string|null $eventName
     * @return AbstractAction[]
     */
    public function dispatch(Customer $customer, AbstractAction $action, $eventName = null);

    /**
     * @param Customer $customer
     * @param array $actions
     * @return Customer
     */
    public function apply(Customer $customer, array $actions);

    /**
     * @param Customer $customer
     * @param array $actions
     * @return Customer
     */
    public function dispatchPostUpdate(Customer $customer, array $actions);
}
