<?php
/**
 * @author: Ylambers <yaron.lambers@commercetools.de>
 */

namespace Commercetools\Symfony\CtpBundle\Model\Repository;

use Commercetools\Symfony\CtpBundle\Model\Repository;
use Commercetools\Core\Model\Common\Address;
use Commercetools\Core\Model\Customer\Customer;
use Commercetools\Core\Request\Customers\Command\CustomerChangeAddressAction;
use Commercetools\Core\Request\Customers\Command\CustomerChangeEmailAction;
use Commercetools\Core\Request\Customers\Command\CustomerChangeNameAction;
use Commercetools\Core\Request\Customers\CustomerByIdGetRequest;
use Commercetools\Core\Request\Customers\CustomerPasswordChangeRequest;
use Commercetools\Core\Request\Customers\CustomerUpdateRequest;

class CustomerRepository extends Repository
{
    const NAME = 'customer';

    /**
     * @param $customerId
     * @return Customer
     */

    public function getCustomer($locale, $customerId)
    {
        $client = $this->getClient($locale);
        $request = CustomerByIdGetRequest::ofId($customerId);
        $response = $request->executeWithClient($client);
        $customer = $request->mapResponse($response);

        return $customer;
    }

    public function setAddresses($locale, Customer $customer, Address $address, $addressId)
    {
        $client = $this->getClient($locale);

        $request = CustomerUpdateRequest::ofIdAndVersion($customer->getId(), $customer->getVersion());

        $request->addAction(CustomerChangeAddressAction::ofAddressIdAndAddress($addressId, $address));
        $response = $request->executeWithClient($client);
        $customer = $request->mapResponse($response);

        return $customer;
    }

    public function setCustomerDetails($locale, Customer $customer, $firstName, $lastName, $email)
    {
        $client = $this->getClient($locale);
        $request = CustomerUpdateRequest::ofIdAndVersion($customer->getId(), $customer->getVersion());
        if ($customer->getFirstName() != $firstName || $customer->getLastName() != $lastName) {
            $request->addAction(CustomerChangeNameAction::ofFirstNameAndLastName($firstName, $lastName));
        }
        if ($customer->getEmail() != $email) {
            $request->addAction(CustomerChangeEmailAction::ofEmail($email));
        }
        $response = $request->executeWithClient($client);

        if ($response->isError()) {
            return null;
        }
        $customer = $request->mapResponse($response);

        return $customer;
    }

    public function setNewPassword($locale,Customer $customer, $currentPassword, $newPassword)
    {
        $client = $this->getClient($locale);

        if ($currentPassword == $newPassword) {
            throw new \InvalidArgumentException('form.type.password');
        }
        if (!empty($currentPassword) && !empty($newPassword)) {
            $request = CustomerPasswordChangeRequest::ofIdVersionAndPasswords(
                $customer->getId(),
                $customer->getVersion(),
                $currentPassword,
                $newPassword
            );

            $response = $request->executeWithClient($client);

            if ($response->isError()) {
                throw new \InvalidArgumentException('wrong_password');
            }
            $customer = $request->mapResponse($response);

            return $customer;
        }

        return null;
    }
}