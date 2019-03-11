<?php
/**
 */

namespace Commercetools\Symfony\CustomerBundle\Model\Repository;

use Commercetools\Core\Builder\Request\RequestBuilder;
use Commercetools\Core\Model\Customer\Customer;
use Commercetools\Core\Model\Customer\CustomerDraft;
use Commercetools\Symfony\CtpBundle\Model\QueryParams;
use Commercetools\Symfony\CtpBundle\Model\Repository;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class CustomerRepository extends Repository
{
    const CUSTOMER_ID = 'customer.id';

    /**
     * @param string $locale
     * @param string $customerId
     * @param QueryParams|null $params
     * @return Customer
     */
    public function getCustomerById($locale, $customerId, QueryParams $params = null)
    {
        $request = RequestBuilder::of()->customers()->getById($customerId);

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
        $request = RequestBuilder::of()->customers()->update($customer)->setActions($actions);

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
        $request = RequestBuilder::of()->customers()->changePassword($customer, $currentPassword, $newPassword);

        return $this->executeRequest($request);
    }

    /**
     * @param string $locale
     * @param string $email
     * @param string $password
     * @param SessionInterface|null $session
     * @return Customer
     */
    public function createCustomer($locale, $email, $password, SessionInterface $session = null)
    {
        $customerDraft = CustomerDraft::of()
            ->setEmail($email)
            ->setPassword($password);

        if (!is_null($session)) {
            $customerDraft->setAnonymousId($session->getId());
        }

        $request = RequestBuilder::of()->customers()->create($customerDraft);

        return $this->executeRequest($request, $locale);
    }

    /**
     * @param Customer $customer
     * @return Customer
     */
    public function delete(Customer $customer)
    {
        $request = RequestBuilder::of()->customers()->delete($customer);

        return $this->executeRequest($request);
    }
}
