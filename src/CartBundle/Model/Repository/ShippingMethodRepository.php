<?php
/**
 */

namespace Commercetools\Symfony\CartBundle\Model\Repository;


use Commercetools\Core\Builder\Request\RequestBuilder;
use Commercetools\Core\Model\ShippingMethod\ShippingMethodCollection;
use Commercetools\Core\Model\Zone\Location;
use Commercetools\Symfony\CtpBundle\Model\Repository;

class ShippingMethodRepository extends Repository
{
    /**
     * @return ShippingMethodCollection
     */
    public function getShippingMethodsByLocation($locale, Location $location, $currency = null)
    {
        $client = $this->getClient();

        $request = RequestBuilder::of()->shippingMethods()->getByLocation($location, $currency);

        $response = $request->executeWithClient($client);

        $shippingMethods = $request->mapFromResponse(
            $response,
            $this->getMapper($locale)
        );

        return $shippingMethods;
    }

    public function getShippingMethodByCart($locale, $cartId)
    {
        $client = $this->getClient();

        $request = RequestBuilder::of()->shippingMethods()->getByCartId($cartId);

        $response = $request->executeWithClient($client);

        $shippingMethod = $request->mapFromResponse(
            $response,
            $this->getMapper($locale)
        );

        return $shippingMethod;
    }

    public function getShippingMethodById($locale, $id)
    {
        $client = $this->getClient();

        $request = RequestBuilder::of()->shippingMethods()->getById($id);

        $response = $request->executeWithClient($client);

        $shippingMethod = $request->mapFromResponse(
            $response,
            $this->getMapper($locale)
        );

        return $shippingMethod;
    }
}
