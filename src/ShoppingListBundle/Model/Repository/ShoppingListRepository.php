<?php
/**
 */

namespace Commercetools\Symfony\ShoppingListBundle\Model\Repository;

use Commercetools\Core\Builder\Request\RequestBuilder;
use Commercetools\Core\Error\InvalidArgumentException;
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

        return $this->executeRequest($request, $locale, $params);
    }

    public function getShoppingListForUser($locale, $shoppingListId, CustomerReference $customer = null, $anonymousId = null, QueryParams $params = null)
    {
        $request = RequestBuilder::of()->shoppingLists()->query();

        if (!is_null($customer)) {
            $request->where('id = "' . $shoppingListId . '" and customer(id = "' . $customer->getId() . '")');
        } else if (!is_null($anonymousId)) {
            $request->where('id = "' . $shoppingListId . '" and anonymousId = "' . $anonymousId . '"');
        } else {
            throw new InvalidArgumentException('At least one of CustomerReference or AnonymousId should be present');
        }

        return $this->executeRequest($request, $locale, $params);
    }

    public function getAllShoppingListsByCustomer($locale, CustomerReference $customer, QueryParams $params = null)
    {
        $request = RequestBuilder::of()->shoppingLists()->query()->where('customer(id = "' . $customer->getId() . '")')->sort('createdAt desc');

        return $this->executeRequest($request, $locale, $params);
    }

    public function getAllShoppingListsByAnonymousId($locale, $anonymousId, QueryParams $params = null)
    {
        $request = RequestBuilder::of()->shoppingLists()->query()->where('anonymousId = "' . $anonymousId . '"')->sort('createdAt desc');

        return $this->executeRequest($request, $locale, $params);
    }

    public function createByCustomer($locale, CustomerReference $customer, $shoppingListName, QueryParams $params = null)
    {
        $key = $this->createUniqueKey($customer->getId());
        $localizedListName = LocalizedString::ofLangAndText($locale, $shoppingListName);
        $shoppingListDraft = ShoppingListDraft::ofNameAndKey($localizedListName, $key)
            ->setCustomer($customer);
        $request = RequestBuilder::of()->shoppingLists()->create($shoppingListDraft);

        return $this->executeRequest($request, $locale, $params);
    }

    public function createByAnonymous($locale, $anonymousId, $shoppingListName, QueryParams $params = null)
    {
        $key = $this->createUniqueKey($anonymousId);
        $localizedListName = LocalizedString::ofLangAndText($locale, $shoppingListName);
        $shoppingListDraft = ShoppingListDraft::ofNameAndKey($localizedListName, $key)
            ->setAnonymousId($anonymousId);
        $request = RequestBuilder::of()->shoppingLists()->create($shoppingListDraft);

        return $this->executeRequest($request, $locale, $params);
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
        $list = $request->mapFromResponse($response);

        return $list;
    }

    public function deleteByCustomer($locale, CustomerReference $customer, $shoppingListId)
    {
        $shoppingList = $this->getShoppingListForUser($locale, $shoppingListId, $customer);
        $request = RequestBuilder::of()->shoppingLists()->delete($shoppingList->current());

        return $this->executeRequest($request, $locale);
    }

    public function deleteByAnonymous($locale, $anonymousId, $shoppingListId)
    {
        $shoppingList = $this->getShoppingListForUser($locale, $shoppingListId, null, $anonymousId);
        $request = RequestBuilder::of()->shoppingLists()->delete($shoppingList);

        return $this->executeRequest($request, $locale);
    }
}
