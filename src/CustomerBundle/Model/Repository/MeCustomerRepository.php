<?php
/**
 */

namespace Commercetools\Symfony\CustomerBundle\Model\Repository;

use Commercetools\Core\Builder\Request\RequestBuilder;
use Commercetools\Core\Model\Customer\Customer;
use Commercetools\Core\Model\Customer\CustomerSigninResult;
use Commercetools\Core\Model\Customer\MyCustomerDraft;
use Commercetools\Symfony\CtpBundle\Model\MeRepository;
use Commercetools\Symfony\CtpBundle\Model\QueryParams;

class MeCustomerRepository extends MeRepository
{
    const CUSTOMER_ID = 'customer.id';
    const CUSTOMER_ACCESS_TOKEN = 'customer.access_token';
    const CUSTOMER_REFRESH_TOKEN = 'customer.refresh_token';

    /**
     * @param string $locale
     * @param QueryParams|null $params
     * @return Customer
     */
    public function getMeInfo($locale, QueryParams $params = null)
    {
        $request = RequestBuilder::of()->me()->get();

        return $this->executeRequest($request, $locale, $params);
    }

    /**
     * @param Customer $customer
     * @param array $actions
     * @param QueryParams|null $params
     * @return Customer
     */
    public function update(Customer $customer, array $actions, QueryParams $params = null)
    {
        $request = RequestBuilder::of()->me()->update($customer)->setActions($actions);

        if (!is_null($params)) {
            foreach ($params->getParams() as $param) {
                $request->addParamObject($param);
            }
        }

        return $this->executeRequest($request);
    }

    /**
     * @param Customer $customer
     * @param string $currentPassword
     * @param string $newPassword
     * @return Customer
     */
    public function changePassword(Customer $customer, $currentPassword, $newPassword)
    {
        $request = RequestBuilder::of()->me()->changePassword($customer, $currentPassword, $newPassword);

        return $this->executeRequest($request);
    }

    /**
     * @param string $locale
     * @param string $email
     * @param string $password
     * @return CustomerSigninResult
     */
    public function createCustomer($locale, $email, $password)
    {
        $customerDraft = MyCustomerDraft::of()
            ->setEmail($email)
            ->setPassword($password);

        $request = RequestBuilder::of()->me()->signUp($customerDraft);

        return $this->executeRequest($request, $locale);
    }

    /**
     * @param Customer $customer
     * @return Customer
     */
    public function delete(Customer $customer)
    {
        $request = RequestBuilder::of()->me()->delete($customer);

        return $this->executeRequest($request);
    }
}
