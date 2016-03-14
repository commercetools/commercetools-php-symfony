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

    public function getCustomer($customerId)
    {
        $request = CustomerByIdGetRequest::ofId($customerId);
        $response = $request->executeWithClient($this->client);
        $customer = $request->mapResponse($response);

        return $customer;
    }

    public function setAddresses(Customer $customer, Address $address, $addressId)
    {
        $request = CustomerUpdateRequest::ofIdAndVersion($customer->getId(), $customer->getVersion());

        $request->addAction(CustomerChangeAddressAction::ofAddressIdAndAddress($addressId, $address));
        $response = $request->executeWithClient($this->client);
        $customer = $request->mapResponse($response);

        return $customer;
    }

    public function setCustomerDetails(Customer $customer, $firstName, $lastName, $email)
    {
        $request = CustomerUpdateRequest::ofIdAndVersion($customer->getId(), $customer->getVersion());
        if ($customer->getFirstName() != $firstName || $customer->getLastName() != $lastName) {
            $request->addAction(CustomerChangeNameAction::ofFirstNameAndLastName($firstName, $lastName));
        }
        if ($customer->getEmail() != $email) {
            $request->addAction(CustomerChangeEmailAction::ofEmail($email));
        }
        $response = $request->executeWithClient($this->client);

        if ($response->isError()) {
            return null;
        }
        $customer = $request->mapResponse($response);

        return $customer;
    }

    public function setNewPassword(Customer $customer, $currentPassword, $newPassword)
    {
        if ($currentPassword == $newPassword) {
            throw new \InvalidArgumentException('same_password');
        }
        if (!empty($currentPassword) && !empty($newPassword)) {
            $request = CustomerPasswordChangeRequest::ofIdVersionAndPasswords(
                $customer->getId(),
                $customer->getVersion(),
                $currentPassword,
                $newPassword
            );

            $response = $request->executeWithClient($this->client);

            if ($response->isError()) {
                throw new \InvalidArgumentException('wrong_password');
            }
            $customer = $request->mapResponse($response);

            return $customer;
        }

        return null;
    }
}