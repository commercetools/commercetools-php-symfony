<?php
/**
 * @author jayS-de <jens.schulze@commercetools.de>
 */

namespace Commercetools\Symfony\CtpBundle\Model;

use Commercetools\Core\Client;
use Commercetools\Core\Model\JsonObjectMapper;
use Commercetools\Core\Model\MapperInterface;
use Commercetools\Core\Request\AbstractApiRequest;
use Commercetools\Core\Request\QueryAllRequestInterface;
use Commercetools\Symfony\CtpBundle\Service\MapperFactory;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Repository
{
    const DEFAULT_PAGE_SIZE = 500;

    const CACHE_TTL = 3600;

    /**
     * @var bool
     */
    protected $enableCache;

    /**
     * @var CacheItemPoolInterface
     */
    protected $cache;

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var MapperFactory
     */
    protected $mapperFactory;

    /**
     * Repository constructor.
     * @param $enableCache
     * @param CacheItemPoolInterface $cache
     * @param Client $client
     * @param MapperFactory $mapperFactory
     */
    public function __construct($enableCache, CacheItemPoolInterface $cache, Client $client, MapperFactory $mapperFactory)
    {
        if (is_string($enableCache)) {
            $enableCache = ($enableCache == "true");
        }
        $this->enableCache = $enableCache;
        $this->cache = $cache;
        $this->client = $client;
        $this->mapperFactory = $mapperFactory;
    }

    /**
     * @return Client
     */
    protected function getClient()
    {
        return $this->client;
    }

    /**
     * @param $locale
     * @return MapperInterface
     */
    public function getMapper($locale)
    {
        return $this->mapperFactory->build($locale);
    }

    /**
     * @param Client $client
     * @param $cacheKey
     * @param QueryAllRequestInterface $request
     * @param int $ttl
     * @return mixed
     */
    protected function retrieveAll(
        Client $client,
        $cacheKey,
        QueryAllRequestInterface $request,
        $locale,
        $force = false,
        $ttl = self::CACHE_TTL
    ) {
        $data = [];
        if (!$force && $this->enableCache && $this->cache->hasItem($cacheKey)) {
            $cachedData = $this->cache->getItem($cacheKey);
            if (!empty($cachedData)) {
                $data = $cachedData;
            }
            $result = unserialize($data->get());
            $result->setContext($client->getConfig()->getContext());
        } else {
            $result = $this->getAll($client, $request, $locale);
            $this->store($cacheKey, serialize($result), $ttl);
        }

        return $result;
    }

    protected function getAll(Client $client, QueryAllRequestInterface $request, $locale)
    {
        $lastId = null;
        $data = ['results' => []];
        do {
            $request->sort('id')->limit(static::DEFAULT_PAGE_SIZE)->withTotal(false);
            if ($lastId != null) {
                $request->where('id > "' . $lastId . '"');
            }
            $response = $client->execute($request);
            if ($response->isError() || is_null($response->toObject())) {
                break;
            }
            $results = $response->toArray()['results'];
            $data['results'] = array_merge($data['results'], $results);
            $lastId = end($results)['id'];
        } while (count($results) >= static::DEFAULT_PAGE_SIZE);

        $result = $this->getMapper($locale)->map($data, $request->getResultClass());

        return $result;
    }

    /**
     * @param Client $client
     * @param $cacheKey
     * @param AbstractApiRequest $request
     * @param int $ttl
     * @return \Commercetools\Core\Model\Common\JsonDeserializeInterface|null
     */
    protected function retrieve(
        Client $client, $cacheKey,
        AbstractApiRequest $request,
        $locale,
        $force = false,
        $ttl = self::CACHE_TTL
    ) {
        if (!$force && $this->enableCache && $this->cache->hasItem($cacheKey)) {
            $cachedData = $this->cache->getItem($cacheKey);
            if (empty($cachedData)) {
                throw new NotFoundHttpException("resource not found");
            }
            $result = unserialize($cachedData->get());
            $result->setContext($client->getConfig()->getContext());
        } else {
            $response = $request->executeWithClient($client);
            if ($response->isError() || is_null($response->toObject())) {
                $this->store($cacheKey, '', $ttl);
                throw new NotFoundHttpException("resource not found");
            }

            $result = $this->getMapper($locale)->map($response->toArray(), $request->getResultClass());
            $this->store($cacheKey, serialize($result), $ttl);
        }

        return $result;
    }

    protected function store($cacheKey, $data, $ttl)
    {
        if ($this->enableCache) {
            $item = $this->cache->getItem($cacheKey)->set($data)->expiresAfter($ttl);
            $this->cache->save($item);
        }
    }
}
