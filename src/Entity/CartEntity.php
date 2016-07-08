<?php
/**
 * @author: Ylambers <yaron.lambers@commercetools.de>
 */

namespace Commercetools\Symfony\CtpBundle\Entity;


use Commercetools\Core\Model\Cart\Cart;
use Commercetools\Core\Model\Customer\Customer;

class CartEntity
{
    public $billingAddress;

    public $shippingAddress;

    public $check;

    public $name;

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    public static function ofCart(Cart $cart)
    {
        $entity = new static();
        $entity->shippingAddress = !is_null($cart->getShippingAddress()) ? $cart->getShippingAddress()->toArray() : [];
        $entity->billingAddress = !is_null($cart->getBillingAddress()) ? $cart->getBillingAddress()->toArray() : [];

        return $entity;
    }
}
