<?php
/**
 * @author jayS-de <jens.schulze@commercetools.de>
 */

namespace Commercetools\Symfony\CtpBundle\Model;

use Cache\Adapter\Common\CacheItem;
use Commercetools\Commons\Helper\QueryHelper;
use Commercetools\Core\Client;
use Commercetools\Core\Request\AbstractApiRequest;
use Commercetools\Core\Request\QueryAllRequestInterface;
use Commercetools\Symfony\CtpBundle\Service\ClientFactory;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Repository
{
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
    protected $client = [];

    /**
     * @var ClientFactory
     */
    protected $clientFactory;

    /**
     * Repository constructor.
     * @param $enableCache
     * @param CacheItemPoolInterface $cache
     * @param ClientFactory $clientFactory
     */
    public function __construct($enableCache, CacheItemPoolInterface $cache, ClientFactory $clientFactory)
    {
        if (is_string($enableCache)) {
            $enableCache = ($enableCache == "true");
        }
        $this->enableCache = $enableCache;
        $this->cache = $cache;
        $this->clientFactory = $clientFactory;
    }

    /**
     * @param $locale
     * @return Client
     */
    protected function getClient($locale)
    {
        if (!isset($this->client[$locale])) {
            $this->client[$locale] = $this->clientFactory->build($locale);
        }

        return $this->client[$locale];
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
        $force = false,
        $ttl = self::CACHE_TTL
    ) {
        $data = [];
        if (!$force && $this->enableCache && $this->cache->hasItem($cacheKey)) {
            $cachedData = $this->cache->getItem($cacheKey);
            if (!empty($cachedData)) {
                $data = $cachedData;
            }
            $result = unserialize($data);
            $result->setContext($client->getConfig()->getContext());
        } else {
            $helper = new QueryHelper();
            $result = $helper->getAll($client, $request);
            $this->store($cacheKey, serialize($result), $ttl);
        }

        return $result;
    }

    /**
     * @param Client $client
     * @param $cacheKey
     * @param AbstractApiRequest $request
     * @param int $ttl
     * @return \Commercetools\Core\Model\Common\JsonDeserializeInterface|null
     */
    protected function retrieve(Client $client, $cacheKey, AbstractApiRequest $request, $force = false, $ttl = self::CACHE_TTL)
    {
        if (!$force && $this->enableCache && $this->cache->hasItem($cacheKey)) {
            $cachedData = $this->cache->getItem($cacheKey);
            if (empty($cachedData)) {
                throw new NotFoundHttpException("resource not found");
            }
            $result = unserialize($cachedData);
            $result->setContext($client->getConfig()->getContext());
        } else {
            $response = $request->executeWithClient($client);
            if ($response->isError() || is_null($response->toObject())) {
                $this->store($cacheKey, '', $ttl);
                throw new NotFoundHttpException("resource not found");
            }
            $result = $request->mapResponse($response);
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
