<?php
/**
 *
 */

namespace Commercetools\Symfony\ExampleBundle\Entity;

class ProductEntity
{
    private $productId;
    private $variantId;
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
     * @return ProductEntity
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
        return $this->variantId;
    }

    /**
     * @param mixed $variantId
     * @return ProductEntity
     */
    public function setVariantId($variantId)
    {
        $this->variantId = $variantId;
    
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
     * @return ProductEntity
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
     * @return ProductEntity
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
     * @return ProductEntity
     */
    public function setAllVariants($allVariants)
    {
        $this->allVariants = $allVariants;

        return $this;
    }
}
