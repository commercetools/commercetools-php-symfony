<?php
/**
 */

namespace Commercetools\Symfony\CartBundle\Model\Repository;


use Commercetools\Core\Model\ShippingMethod\ShippingMethodCollection;
use Commercetools\Core\Request\ShippingMethods\ShippingMethodByCartIdGetRequest;
use Commercetools\Core\Request\ShippingMethods\ShippingMethodByLocationGetRequest;
use Commercetools\Core\Request\ShippingMethods\ShippingMethodQueryRequest;
use Commercetools\Symfony\CtpBundle\Model\Repository;

class ShippingMethodRepository extends Repository
{
    const NAME = 'shippingMethods';

    /**
     * @return ShippingMethodCollection
     */
    public function getShippingMethods($locale, $force = false)
    {
        $client = $this->getClient();
        $cacheKey = static::NAME;
        $shippingMethodRequest = ShippingMethodQueryRequest::of();
        return $this->retrieveAll($client, $cacheKey, $shippingMethodRequest, $locale, $force);
    }

    /**
     * @param $name
     * @return \Commercetools\Core\Model\ShippingMethod\ShippingMethod
     */
    public function getByName($locale, $name)
    {
        $shippingMethod = $this->getShippingMethods($locale)->getByName($name);
        if (is_null($shippingMethod)) {
            $shippingMethod = $this->getShippingMethods($locale, true)->getByName($name);
        }
        return $shippingMethod;
    }

    /**
     * @param $country
     * @param $currency
     * @return \Commercetools\Core\Response\ApiResponseInterface
     */
    public function getByCountryAndCurrency($locale, $country, $currency)
    {
        $client = $this->getClient();
        
        $request = ShippingMethodByLocationGetRequest::ofCountry($country)->withCurrency($currency);
        return $client->executeAsync($request);
    }

    public function getShippingMethodByCart($locale, $cartId)
    {
        $client = $this->getClient();
        $request = ShippingMethodByCartIdGetRequest::ofCartId($cartId);
        $response = $request->executeWithClient($client);
        $shippingMethods = $request->mapFromResponse(
            $response,
            $this->getMapper($locale)
        );

        return $shippingMethods;
    }

}
