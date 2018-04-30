<?php
/**
 * Created by PhpStorm.
 * User: nsotiropoulos
 * Date: 16/04/2018
 * Time: 11:15
 */

namespace Commercetools\Symfony\CustomerBundle\Model\Repository;

use Commercetools\Core\Builder\Request\RequestBuilder;
use Commercetools\Core\Builder\Update\ActionBuilder;
use Commercetools\Core\Builder\Update\CustomersActionBuilder;
use Commercetools\Core\Model\Common\LocalizedString;
use Commercetools\Core\Model\Common\Address;
use Commercetools\Core\Model\Customer\CustomerReference;
use Commercetools\Core\Model\Customer\Customer;
use Commercetools\Core\Model\Customer\CustomerDraft;
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
//        $action = ActionBuilder::of()->customers()->
        $request = RequestBuilder::of()->customers()->update($customer)->setActions($action);

        $request->addAction(CustomerChangeAddressAction::ofAddressIdAndAddress($addressId, $address));

        return $this->executeRequest($request, $locale);
    }

    public function setCustomerDetails($locale, Customer $customer, $firstName, $lastName, $email)
    {
//        $request = CustomerUpdateRequest::ofIdAndVersion($customer->getId(), $customer->getVersion());
        $request = RequestBuilder::of()->customers()->update($customer)->setActions($actions);

        if ($customer->getFirstName() != $firstName || $customer->getLastName() != $lastName) {
            $request->addAction(CustomerChangeNameAction::ofFirstNameAndLastName($firstName, $lastName));
        }
        if ($customer->getEmail() != $email) {
            $request->addAction(CustomerChangeEmailAction::ofEmail($email));
        }

        return $this->executeRequest($request, $locale);
    }

    public function setNewPassword($locale, Customer $customer, $currentPassword, $newPassword)
    {
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
            $customer = $request->mapFromResponse(
                $response,
                $this->getMapper($locale)
            );

            return $customer;
        }

        return null;
    }

//    public function create($locale, CustomerReference $customer, $shoppingListName, QueryParams $params = null)
//    {
//        $client = $this->getClient();
//        $key = $this->createUniqueKey($customer->getId());
//        $localizedListName = LocalizedString::ofLangAndText($locale, $shoppingListName);
//        $shoppingListDraft = ShoppingListDraft::ofNameAndKey($localizedListName, $key)
//            ->setCustomer($customer);
//        $request = RequestBuilder::of()->shoppingLists()->create($shoppingListDraft);
//
//        if(!is_null($params)){
//            foreach ($params->getParams() as $param) {
//                $request->addParamObject($param);
//            }
//        }
//
//        $response = $request->executeWithClient($client);
//        $list = $request->mapFromResponse(
//            $response,
//            $this->getMapper($locale)
//        );
//
//        return $list;
//    }
//
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
        $customer = $request->mapFromResponse(
            $response
        );

        return $customer;

    }
}
