<?php
/**
 */

namespace Commercetools\Symfony\ShoppingListBundle\Model\Repository;

use Commercetools\Core\Builder\Request\RequestBuilder;
use Commercetools\Core\Model\Common\LocalizedString;
use Commercetools\Core\Model\ShoppingList\MyShoppingListDraft;
use Commercetools\Core\Model\ShoppingList\ShoppingList;
use Commercetools\Symfony\CtpBundle\Model\MeRepository;
use Commercetools\Symfony\CtpBundle\Model\QueryParams;

class MeShoppingListRepository extends MeRepository
{
    /**
     * @param $locale
     * @param $shoppingListId
     * @param QueryParams|null $params
     * @return mixed
     */
    public function getShoppingListById($locale, $shoppingListId, QueryParams $params = null)
    {
        $request = RequestBuilder::of()->me()->shoppingLists()->getById($shoppingListId);

        return $this->executeRequest($request, $locale, $params);
    }

    /**
     * @param $locale
     * @param QueryParams|null $params
     * @return mixed
     */
    public function getAllMyShoppingLists($locale, QueryParams $params = null)
    {
        $request = RequestBuilder::of()->me()->shoppingLists()->query();

        return $this->executeRequest($request, $locale, $params);
    }

    /**
     * @param string $locale
     * @param string $shoppingListName
     * @param QueryParams|null $params
     * @return mixed
     */
    public function create($locale, $shoppingListName, QueryParams $params = null)
    {
        $shoppingListDraft = MyShoppingListDraft::ofName(
            LocalizedString::ofLangAndText($locale, $shoppingListName)
        );

        $request = RequestBuilder::of()->me()->shoppingLists()->create($shoppingListDraft);

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
        $request = RequestBuilder::of()->me()->shoppingLists()->update($shoppingList)->setActions($actions);

        return $this->executeRequest($request, 'en', $params);
    }

    /**
     * @param string $locale
     * @param ShoppingList $shoppingList
     * @return mixed
     */
    public function delete($locale, ShoppingList $shoppingList)
    {
        $request = RequestBuilder::of()->me()->shoppingLists()->delete($shoppingList);

        return $this->executeRequest($request, $locale);
    }
}
