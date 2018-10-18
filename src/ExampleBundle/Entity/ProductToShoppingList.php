<?php
/**
 *
 */

namespace Commercetools\Symfony\ExampleBundle\Entity;

class ProductToShoppingList extends ProductToCart
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
     */
    public function setAvailableShoppingLists($availableShoppingLists)
    {
        $this->availableShoppingLists = $availableShoppingLists;

        return $this;
    }


}
