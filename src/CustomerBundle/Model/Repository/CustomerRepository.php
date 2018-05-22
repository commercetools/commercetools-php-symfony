<?php
/**
 */

namespace Commercetools\Symfony\CustomerBundle\Model\Repository;

use Commercetools\Core\Builder\Request\RequestBuilder;
use Commercetools\Core\Model\Customer\Customer;
use Commercetools\Symfony\CtpBundle\Model\QueryParams;
use Commercetools\Symfony\CtpBundle\Service\MapperFactory;
use Commercetools\Symfony\CtpBundle\Model\Repository;
use Commercetools\Core\Client;
use Psr\Cache\CacheItemPoolInterface;
use Commercetools\Core\Request\ClientRequestInterface;

class CustomerRepository extends Repository
{
    const CUSTOMER_ID = 'customer.id';

    public function __construct(
        $enableCache,
        CacheItemPoolInterface $cache,
        Client $client,
        MapperFactory $mapperFactory
    ) {
        parent::__construct($enableCache, $cache, $client, $mapperFactory);
    }

    public function executeRequest(ClientRequestInterface $request, $locale, QueryParams $params = null)
    {
        $client = $this->getClient();

        if(!is_null($params)){
            foreach ($params->getParams() as $param) {
                $request->addParamObject($param);
            }
        }

        $response = $request->executeWithClient($client);
        $customers = $request->mapFromResponse(
            $response,
            $this->getMapper($locale)
        );

        return $customers;
    }

    public function getCustomerById($locale, $customerId, QueryParams $params = null)
    {
        $request = RequestBuilder::of()->customers()->getById($customerId);

        return $this->executeRequest($request, $locale, $params);
    }

    public function update(Customer $customer, array $actions, QueryParams $params = null)
    {
        $client = $this->getClient();
        $request = RequestBuilder::of()->customers()->update($customer)->setActions($actions);

        if(!is_null($params)){
            foreach ($params->getParams() as $param) {
                $request->addParamObject($param);
            }
        }

        $response = $request->executeWithClient($client);
        $customer = $request->mapFromResponse($response);

        return $customer;

    }
}
