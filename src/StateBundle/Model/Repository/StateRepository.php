<?php
/**
 *
 */

namespace Commercetools\Symfony\StateBundle\Model\Repository;

use Commercetools\Core\Builder\Request\RequestBuilder;
use Commercetools\Core\Client;
use Commercetools\Core\Model\State\State;
use Commercetools\Core\Model\State\StateCollection;
use Commercetools\Symfony\CtpBundle\Logger\Logger;
use Commercetools\Symfony\CtpBundle\Model\Repository;
use Commercetools\Symfony\CtpBundle\Service\MapperFactory;
use Psr\Cache\CacheItemPoolInterface;

class StateRepository extends Repository
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * StateRepository constructor.
     * @param $enableCache
     * @param CacheItemPoolInterface $cache
     * @param Client $client
     * @param MapperFactory $mapperFactory
     * @param Logger $logger
     */
    public function __construct($enableCache, CacheItemPoolInterface $cache, Client $client, MapperFactory $mapperFactory, Logger $logger)
    {
        parent::__construct($enableCache, $cache, $client, $mapperFactory);
        $this->logger = $logger;
    }

    /**
     * @return StateCollection
     */
    public function getStates()
    {
        $request = RequestBuilder::of()->states()->query();

        return $this->executeRequest($request);
    }

    /**
     * @param $id
     * @return State
     */
    public function getById($id)
    {
        $request = RequestBuilder::of()->states()->getById($id);

        return $this->executeRequest($request);
    }

    /**
     * @param $type
     * @param $key
     * @return State
     */
    public function getByTypeAndKey($type, $key)
    {
        $request = RequestBuilder::of()->states()->query()->where('type = "'.$type.'" and key = "'.$key.'"');

        $stateCollection = $this->executeRequest($request);

        return $stateCollection->current();
    }

    /**
     * @param $key
     * @return State
     */
    public function getByKey($key)
    {
        $request = RequestBuilder::of()->states()->query()->where('key = "'.$key.'"');

        $stateCollection = $this->executeRequest($request);

        return $stateCollection->current();
    }
}
