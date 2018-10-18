<?php
/**
 *
 */

namespace Commercetools\Symfony\ExampleBundle\Entity;

class ProductToCart
{
    private $productId;
    private $variantId;
    private $variantIdText;
    private $slug;
    private $quantity;
    private $allVariants;

    /**
     * @return mixed
     */
    public function getProductId()
    {
        return $this->productId;
    }

    /**
     * @param mixed $productId
     */
    public function setProductId($productId)
    {
        $this->productId = $productId;
    
        return $this;
    }

    /**
     * @return mixed
     */
    public function getVariantId()
    {
        return $this->variantId ?? 1;
    }

    /**
     * @param mixed $variantId
     */
    public function setVariantId($variantId)
    {
        $this->variantId = $variantId;
    
        return $this;
    }

    /**
     * @return mixed
     */
    public function getVariantIdText()
    {
        return $this->variantIdText;
    }

    /**
     * @param mixed $variantIdText
     */
    public function setVariantIdText($variantIdText)
    {
        $this->variantIdText = $variantIdText;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * @param mixed $slug
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;
    
        return $this;
    }

    /**
     * @return mixed
     */
    public function getQuantity()
    {
        return $this->quantity ?? 1;
    }

    /**
     * @param mixed $quantity
     */
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;
    
        return $this;
    }

    /**
     * @return mixed
     */
    public function getAllVariants()
    {
        return $this->allVariants ?? [];
    }

    /**
     * @param mixed $allVariants
     */
    public function setAllVariants($allVariants)
    {
        $this->allVariants = $allVariants;

        return $this;
    }

    public function toArray()
    {
        return get_object_vars($this);
    }
}
