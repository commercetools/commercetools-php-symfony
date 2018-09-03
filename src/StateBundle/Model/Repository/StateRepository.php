<?php
/**
 *
 */

namespace Commercetools\Symfony\StateBundle\Model\Repository;


use Commercetools\Core\Builder\Request\RequestBuilder;
use Commercetools\Core\Client;
use Commercetools\Symfony\CtpBundle\Logger\Logger;
use Commercetools\Symfony\CtpBundle\Model\Repository;
use Commercetools\Symfony\CtpBundle\Service\MapperFactory;
use Psr\Cache\CacheItemPoolInterface;

class StateRepository extends Repository
{
    private $logger;

    public function __construct($enableCache, CacheItemPoolInterface $cache, Client $client, MapperFactory $mapperFactory, Logger $logger)
    {
        parent::__construct($enableCache, $cache, $client, $mapperFactory);
        $this->logger = $logger;
    }

    public function getStates()
    {
        $request = RequestBuilder::of()->states()->query();

        return $this->executeRequest($request);
    }

    public function getById($id)
    {
        $request = RequestBuilder::of()->states()->getById($id);

        return $this->executeRequest($request);
    }
}
