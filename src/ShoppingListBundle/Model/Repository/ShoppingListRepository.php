<?php
/**
 * Created by PhpStorm.
 * User: nsotiropoulos
 * Date: 16/04/2018
 * Time: 11:15
 */

namespace Commercetools\Symfony\ShoppingListBundle\Model\Repository;

use Commercetools\Core\Model\Customer\CustomerReference;
use Commercetools\Core\Model\ShoppingList\ShoppingListDraft;
use Commercetools\Core\Request\ShoppingLists\ShoppingListCreateRequest;
use Commercetools\Core\Request\ShoppingLists\ShoppingListQueryRequest;
use Commercetools\Symfony\CtpBundle\Model\Repository;
use Commercetools\Symfony\CtpBundle\Service\MapperFactory;
use Commercetools\Core\Client;
use Commercetools\Core\Request\ShoppingLists\ShoppingListByIdGetRequest;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpFoundation\Session\Session;

class ShoppingListRepository extends Repository
{
    public function __construct(
        $enableCache,
        CacheItemPoolInterface $cache,
        Client $client,
        MapperFactory $mapperFactory
    ) {
        parent::__construct($enableCache, $cache, $client, $mapperFactory);
    }

    public function getShoppingList($locale, $shoppingListId)
    {
        $client = $this->getClient();
        $request = ShoppingListByIdGetRequest::ofId($shoppingListId);
        $response = $request->executeWithClient($client);
        $shoppingList = $request->mapFromResponse(
            $response,
            $this->mapperFactory->build($locale, $request->getResultClass())
        );


        return $shoppingList;
    }

    public function getAllShoppingListsByCustomer($locale, CustomerReference $customer)
    {
        $client = $this->getClient();
        $request = ShoppingListQueryRequest::of()->where('customer(id = "' . $customer->getId() . '")')->sort('createdAt desc');
        $response = $request->executeWithClient($client);
        $lists = $request->mapFromResponse(
            $response,
            $this->getMapper($locale)
        );

        return $lists;
    }

    public function createShoppingList($locale, CustomerReference $customer)
    {
        $client = $this->getClient();
        $key = $this->createUniqueKey($customer->getId());
        $shoppingListDraft = ShoppingListDraft::ofNameAndKey($key, $key);
        $request = ShoppingListCreateRequest::ofDraft($shoppingListDraft);
        $response = $request->executeWithClient($client);
        $list = $request->mapFromResponse(
            $response,
            $this->getMapper($locale)
        );

        return $list;
    }

    private function createUniqueKey($customerId)
    {
        return $customerId . '-' . uniqid();
    }
}