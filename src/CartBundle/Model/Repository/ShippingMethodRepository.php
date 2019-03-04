<?php
/**
 */

namespace Commercetools\Symfony\CartBundle\Model\Repository;

use Commercetools\Core\Builder\Request\RequestBuilder;
use Commercetools\Core\Model\ShippingMethod\ShippingMethod;
use Commercetools\Core\Model\ShippingMethod\ShippingMethodCollection;
use Commercetools\Core\Model\Zone\Location;
use Commercetools\Symfony\CtpBundle\Model\Repository;

class ShippingMethodRepository extends Repository
{
    /**
     * @param string $locale
     * @param Location $location
     * @param string|null $currency
     * @return ShippingMethodCollection
     */
    public function getShippingMethodsByLocation($locale, Location $location, $currency = null)
    {
        $request = RequestBuilder::of()->shippingMethods()->getByLocation($location, $currency);

        return $this->executeRequest($request, $locale);
    }

    /**
     * @param string $locale
     * @param string $cartId
     * @return ShippingMethod
     */
    public function getShippingMethodByCart($locale, $cartId)
    {
        $request = RequestBuilder::of()->shippingMethods()->getByCartId($cartId);

        return $this->executeRequest($request, $locale);
    }

    /**
     * @param string $locale
     * @param string $id
     * @return ShippingMethod
     */
    public function getShippingMethodById($locale, $id)
    {
        $request = RequestBuilder::of()->shippingMethods()->getById($id);

        return $this->executeRequest($request, $locale);
    }
}
