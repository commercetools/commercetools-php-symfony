<?php
/**
 * Created by PhpStorm.
 * User: nsotiropoulos
 * Date: 16/04/2018
 * Time: 11:15
 */

namespace Commercetools\Symfony\ShoppingListBundle\Model\Repository;

use Commercetools\Core\Builder\Request\RequestBuilder;
use Commercetools\Core\Model\Common\LocalizedString;
use Commercetools\Core\Model\Customer\CustomerReference;
use Commercetools\Core\Model\ShoppingList\ShoppingList;
use Commercetools\Core\Model\ShoppingList\ShoppingListDraft;
use Commercetools\Symfony\CtpBundle\Model\QueryParams;
use Commercetools\Symfony\CtpBundle\Model\Repository;
use Commercetools\Symfony\CtpBundle\Service\MapperFactory;
use Commercetools\Core\Client;
use Psr\Cache\CacheItemPoolInterface;

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

    public function getShoppingListById($locale, $shoppingListId, QueryParams $params = null)
    {
        $request = RequestBuilder::of()->shoppingLists()->getById($shoppingListId);

        return $this->getShoppingLists($request, $locale, $params);
    }

    public function getAllShoppingListsByCustomer($locale, CustomerReference $customer, QueryParams $params = null)
    {
        $request = RequestBuilder::of()->shoppingLists()->query()->where('customer(id = "' . $customer->getId() . '")')->sort('createdAt desc');

        return $this->getShoppingLists($request, $locale, $params);
    }

    private function getShoppingLists(RequestBuilder $request, $locale, QueryParams $params = null)
    {
        $client = $this->getClient();

        if(!is_null($params)){
            foreach ($params->getParams() as $param) {
                $request->addParamObject($param);
            }
        }

        $response = $request->executeWithClient($client);
        $lists = $request->mapFromResponse(
            $response,
            $this->getMapper($locale)
        );

        return $lists;
    }

    public function create($locale, CustomerReference $customer, $shoppingListName, QueryParams $params = null)
    {
        $client = $this->getClient();
        $key = $this->createUniqueKey($customer->getId());
        $localizedListName = LocalizedString::ofLangAndText($locale, $shoppingListName);
        $shoppingListDraft = ShoppingListDraft::ofNameAndKey($localizedListName, $key)
            ->setCustomer($customer);
        $request = RequestBuilder::of()->shoppingLists()->create($shoppingListDraft);

        if(!is_null($params)){
            foreach ($params->getParams() as $param) {
                $request->addParamObject($param);
            }
        }

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

    public function update(ShoppingList $shoppingList, array $actions, QueryParams $params = null)
    {
        $client = $this->getClient();
        $request = RequestBuilder::of()->shoppingLists()->update($shoppingList)->setActions($actions);

        if(!is_null($params)){
            foreach ($params->getParams() as $param) {
                $request->addParamObject($param);
            }
        }

        $response = $request->executeWithClient($client);
        $list = $request->mapFromResponse(
            $response
        );

        return $list;

    }
}
