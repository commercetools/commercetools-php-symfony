<?php
/**
 *
 */

namespace Commercetools\Symfony\ExampleBundle\Entity;

class ProductToShoppingList extends ProductEntity
{
    private $shoppingListId;
    private $availableShoppingLists;

    /**
     * @return mixed
     */
    public function getShoppingListId()
    {
        return $this->shoppingListId;
    }

    /**
     * @param mixed $shoppingListId
     * @return ProductToShoppingList
     */
    public function setShoppingListId($shoppingListId)
    {
        $this->shoppingListId = $shoppingListId;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getAvailableShoppingLists()
    {
        return $this->availableShoppingLists;
    }

    /**
     * @param mixed $availableShoppingLists
     * @return ProductToShoppingList
     */
    public function setAvailableShoppingLists($availableShoppingLists)
    {
        $this->availableShoppingLists = $availableShoppingLists;

        return $this;
    }
}
