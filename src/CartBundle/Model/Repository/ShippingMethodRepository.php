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
    public function executeRequest($locale, $request)
    {
        $client = $this->getClient();

        $response = $request->executeWithClient($client);

        $shippingMethods = $request->mapFromResponse(
            $response,
            $this->getMapper($locale)
        );

        return $shippingMethods;
    }

    /**
     * @return ShippingMethodCollection
     */
    public function getShippingMethodsByLocation($locale, Location $location, $currency = null)
    {
        $request = RequestBuilder::of()->shippingMethods()->getByLocation($location, $currency);

        return $this->executeRequest($locale, $request);
    }

    public function getShippingMethodByCart($locale, $cartId)
    {
        $request = RequestBuilder::of()->shippingMethods()->getByCartId($cartId);

        return $this->executeRequest($locale, $request);
    }

    public function getShippingMethodById($locale, $id)
    {
        $request = RequestBuilder::of()->shippingMethods()->getById($id);

        return $this->executeRequest($locale, $request);
    }
}
