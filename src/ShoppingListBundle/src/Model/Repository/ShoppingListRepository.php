<?php
/**
 */

namespace Commercetools\Symfony\ShoppingListBundle\Model\Repository;

use Commercetools\Core\Builder\Request\RequestBuilder;
use Commercetools\Core\Model\Common\LocalizedString;
use Commercetools\Core\Model\Customer\CustomerReference;
use Commercetools\Core\Model\ShoppingList\ShoppingList;
use Commercetools\Core\Model\ShoppingList\ShoppingListDraft;
use Commercetools\Symfony\CtpBundle\Model\QueryParams;
use Commercetools\Symfony\CtpBundle\Model\Repository;

class ShoppingListRepository extends Repository
{
    /**
     * @param $locale
     * @param $shoppingListId
     * @param QueryParams|null $params
     * @return mixed
     */
    public function getShoppingListById($locale, $shoppingListId, QueryParams $params = null)
    {
        $request = RequestBuilder::of()->shoppingLists()->getById($shoppingListId);

        return $this->executeRequest($request, $locale, $params);
    }

    /**
     * @param $locale
     * @param $shoppingListId
     * @param CustomerReference|null $customer
     * @param string|null $anonymousId
     * @param QueryParams|null $params
     * @return mixed
     */
    public function getShoppingList($locale, $shoppingListId, CustomerReference $customer = null, $anonymousId = null, QueryParams $params = null)
    {
        $request = RequestBuilder::of()->shoppingLists()->query();

        $predicate = 'id = "' . $shoppingListId . '"';

        if (!is_null($customer)) {
            $predicate .= ' and customer(id = "' . $customer->getId() . '")';
        } else if (!is_null($anonymousId)) {
            $predicate .= ' and anonymousId = "' . $anonymousId . '"';
        }

        $request->where($predicate);

        $shoppingLists = $this->executeRequest($request, $locale, $params);

        return $shoppingLists->current();
    }

    /**
     * @param $locale
     * @param CustomerReference $customer
     * @param QueryParams|null $params
     * @return mixed
     */
    public function getAllShoppingListsByCustomer($locale, CustomerReference $customer, QueryParams $params = null)
    {
        $request = RequestBuilder::of()->shoppingLists()->query()->where('customer(id = "' . $customer->getId() . '")');

        return $this->executeRequest($request, $locale, $params);
    }

    public function getAllShoppingListsByAnonymousId($locale, $anonymousId, QueryParams $params = null)
    {
        $request = RequestBuilder::of()->shoppingLists()->query()->where('anonymousId = "' . $anonymousId . '"');

        return $this->executeRequest($request, $locale, $params);
    }

    /**
     * @param $locale
     * @param $shoppingListName
     * @param CustomerReference|null $customer
     * @param string|null $anonymousId
     * @param QueryParams|null $params
     * @return mixed
     */
    public function create($locale, $shoppingListName, CustomerReference $customer = null, $anonymousId = null, QueryParams $params = null)
    {
        $localizedListName = LocalizedString::ofLangAndText($locale, $shoppingListName);
        $shoppingListDraft = ShoppingListDraft::ofName($localizedListName);

        if (!is_null($customer)) {
            $shoppingListDraft->setCustomer($customer);
            $shoppingListDraft->setKey($this->createUniqueKey($customer->getId()));
        } elseif (!is_null($anonymousId)) {
            $shoppingListDraft->setAnonymousId($anonymousId);
            $shoppingListDraft->setKey($this->createUniqueKey($anonymousId));
        }

        $request = RequestBuilder::of()->shoppingLists()->create($shoppingListDraft);

        return $this->executeRequest($request, $locale, $params);
    }

    /**
     * @param ShoppingList $shoppingList
     * @param array $actions
     * @param QueryParams|null $params
     * @return ShoppingList
     */
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

    /**
     * @param $locale
     * @param ShoppingList $shoppingList
     * @return mixed
     */
    public function delete($locale, ShoppingList $shoppingList)
    {
        $request = RequestBuilder::of()->shoppingLists()->delete($shoppingList);

        return $this->executeRequest($request, $locale);
    }

    private function createUniqueKey($customerId)
    {
        return $customerId . '-' . uniqid();
    }
}
