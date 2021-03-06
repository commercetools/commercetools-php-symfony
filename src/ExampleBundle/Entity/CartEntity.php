<?php
/**
 */

namespace Commercetools\Symfony\ExampleBundle\Entity;

use Commercetools\Core\Model\Cart\Cart;

class CartEntity
{
    private $billingAddress;
    private $shippingAddress;
    private $name;
    private $differentAddresses;

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     * @return CartEntity
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getBillingAddress()
    {
        return $this->billingAddress;
    }

    /**
     * @param array $billingAddress
     * @return CartEntity
     */
    public function setBillingAddress(array $billingAddress)
    {
        $this->billingAddress = $billingAddress;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getShippingAddress()
    {
        return $this->shippingAddress;
    }

    /**
     * @param array $shippingAddress
     * @return CartEntity
     */
    public function setShippingAddress(array $shippingAddress)
    {
        $this->shippingAddress = $shippingAddress;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getDifferentAddresses()
    {
        return $this->differentAddresses;
    }

    /**
     * @param mixed $differentAddresses
     * @return CartEntity
     */
    public function setDifferentAddresses($differentAddresses)
    {
        $this->differentAddresses = $differentAddresses;

        return $this;
    }

    /**
     * @param Cart $cart
     * @return CartEntity
     */
    public static function ofCart(Cart $cart)
    {
        $entity = new static();
        $entity->shippingAddress = !is_null($cart->getShippingAddress()) ? $cart->getShippingAddress()->toArray() : [];
        $entity->billingAddress = !is_null($cart->getBillingAddress()) ? $cart->getBillingAddress()->toArray() : [];

        return $entity;
    }
}
