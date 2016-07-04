<?php
/**
 * @author: Ylambers <yaron.lambers@commercetools.de>
 */

namespace Commercetools\Symfony\CtpBundle\Model\Repository;


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
        $client = $this->getClient($locale);
        $cacheKey = static::NAME;
        $shippingMethodRequest = ShippingMethodQueryRequest::of();
        return $this->retrieveAll($client, $cacheKey, $shippingMethodRequest, $force);
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
        $client = $this->getClient($locale);
        $request = ShippingMethodByLocationGetRequest::ofCountry($country)->withCurrency($currency);
        return $client->executeAsync($request);
    }

    public function getShippingMethodByCart($locale, $cartId)
    {
        $client = $this->getClient($locale);
        $request = ShippingMethodByCartIdGetRequest::ofCartId($cartId);
        $response = $request->executeWithClient($client);
        $shippingMethods = $request->mapResponse($response);

        return $shippingMethods;
    }

}