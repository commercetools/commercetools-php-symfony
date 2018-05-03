<?php
/**
 * Created by PhpStorm.
 * User: nsotiropoulos
 * Date: 16/04/2018
 * Time: 11:15
 */

namespace Commercetools\Symfony\CustomerBundle\Model\Repository;

use Commercetools\Core\Builder\Request\RequestBuilder;
use Commercetools\Core\Request\Customers\Command\CustomerChangeAddressAction;
use Commercetools\Core\Builder\Update\ActionBuilder;
use Commercetools\Core\Builder\Update\CustomersActionBuilder;
use Commercetools\Core\Model\Common\LocalizedString;
use Commercetools\Core\Model\Common\Address;
use Commercetools\Core\Model\Customer\CustomerReference;
use Commercetools\Core\Model\Customer\Customer;
use Commercetools\Core\Model\Customer\CustomerDraft;
use Commercetools\Core\Request\Customers\Command\CustomerChangeEmailAction;
use Commercetools\Core\Request\Customers\Command\CustomerSetFirstNameAction;
use Commercetools\Core\Request\Customers\Command\CustomerSetLastNameAction;
use Commercetools\Core\Request\Customers\CustomerPasswordChangeRequest;
use Commercetools\Symfony\CtpBundle\Model\QueryParams;
use Commercetools\Symfony\CtpBundle\Service\MapperFactory;
use Commercetools\Symfony\CtpBundle\Model\Repository;
use Commercetools\Core\Client;
use Commercetools\Symfony\CustomerBundle\Model\CustomerUpdateBuilder;
use Psr\Cache\CacheItemPoolInterface;
use Commercetools\Core\Request\ClientRequestInterface;

class CustomerRepository extends Repository
{
    public function __construct(
        $enableCache,
        CacheItemPoolInterface $cache,
        Client $client,
        MapperFactory $mapperFactory
    ) {
        parent::__construct($enableCache, $cache, $client, $mapperFactory);
    }

    private function executeRequest(ClientRequestInterface $request, $locale, QueryParams $params = null)
    {
        $client = $this->getClient();

        if(!is_null($params)){
            foreach ($params->getParams() as $param) {
                $request->addParamObject($param);
            }
        }

        $response = $request->executeWithClient($client);
        $customers = $request->mapFromResponse(
            $response,
            $this->getMapper($locale)
        );

        return $customers;
    }

    public function getCustomerById($locale, $customerId, QueryParams $params = null)
    {
        $request = RequestBuilder::of()->customers()->getById($customerId);

        return $this->executeRequest($request, $locale, $params);
    }

    public function setAddress($locale, Customer $customer, Address $address, $addressId)
    {
        $request = RequestBuilder::of()->customers()->update($customer)
            ->setActions([CustomerChangeAddressAction::ofAddressIdAndAddress($addressId, $address)]);

        return $this->executeRequest($request, $locale);
    }

    public function setCustomerDetails($locale, Customer $customer, $firstName, $lastName, $email)
    {
        $request = RequestBuilder::of()->customers()->update($customer);

        if ($customer->getFirstName() != $firstName){
            $request->addAction(CustomerSetFirstNameAction::of()->setFirstName($firstName));
        }
        if ($customer->getLastName() != $lastName){
            $request->addAction(CustomerSetLastNameAction::of()->setLastName($lastName));
        }
        if ($customer->getEmail() != $email) {
            $request->addAction(CustomerChangeEmailAction::ofEmail($email));
        }

        return $this->executeRequest($request, $locale);
    }

    public function setNewPassword($locale, Customer $customer, $currentPassword, $newPassword)
    {
        if ($currentPassword == $newPassword) {
            throw new \InvalidArgumentException();
        }
        if (!empty($currentPassword) && !empty($newPassword)) {
            $request = RequestBuilder::of()->customers()->update($customer)
                ->setActions([CustomerPasswordChangeRequest::ofIdVersionAndPasswords(
                    $customer->getId(),
                    $customer->getVersion(),
                    $currentPassword,
                    $newPassword
                )]);

            return $this->executeRequest($request, $locale);

        }

        return null;
    }

    public function update(Customer $customer, array $actions, QueryParams $params = null)
    {
        $client = $this->getClient();
        $request = RequestBuilder::of()->customers()->update($customer)->setActions($actions);

        if(!is_null($params)){
            foreach ($params->getParams() as $param) {
                $request->addParamObject($param);
            }
        }

        $response = $request->executeWithClient($client);
        $customer = $request->mapFromResponse($response);

        return $customer;

    }
}
