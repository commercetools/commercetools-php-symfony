<?php
/**
 * @author jayS-de <jens.schulze@commercetools.de>
 */

namespace Commercetools\Symfony\CtpBundle\Model;

use Commercetools\Core\Client\HttpClient;
use Commercetools\Core\Model\MapperInterface;
use Commercetools\Core\Request\AbstractApiRequest;
use Commercetools\Core\Request\ClientRequestInterface;
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
     * @var HttpClient
     */
    protected $client;

    /**
     * @var MapperFactory
     */
    protected $mapperFactory;

    /**
     * Repository constructor.
     * @param string|bool $enableCache
     * @param CacheItemPoolInterface $cache
     * @param HttpClient $client
     * @param MapperFactory $mapperFactory
     */
    public function __construct($enableCache, CacheItemPoolInterface $cache, HttpClient $client, MapperFactory $mapperFactory)
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
     * @return HttpClient
     */
    protected function getClient()
    {
        return $this->client;
    }

    /**
     * @param string $locale
     * @return MapperInterface
     */
    public function getMapper($locale)
    {
        return $this->mapperFactory->build($locale);
    }

    /**
     * @param string $cacheKey
     * @param QueryAllRequestInterface $request
     * @param string $locale
     * @param bool $force
     * @param int $ttl
     * @return mixed
     * @throws \Commercetools\Core\Error\ApiException
     * @throws \Commercetools\Core\Error\InvalidTokenException
     * @throws \Psr\Cache\InvalidArgumentException
     */
    protected function retrieveAll(
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
            $result->setContext($this->client->getConfig()->getContext());
        } else {
            $result = $this->getAll($request, $locale);
            $this->store($cacheKey, serialize($result), $ttl);
        }

        return $result;
    }

    /**
     * @param QueryAllRequestInterface $request
     * @param string $locale
     * @return mixed
     * @throws \Commercetools\Core\Error\ApiException
     * @throws \Commercetools\Core\Error\InvalidTokenException
     */
    protected function getAll(QueryAllRequestInterface $request, $locale)
    {
        $lastId = null;
        $data = ['results' => []];
        do {
            $request->sort('id')->limit(static::DEFAULT_PAGE_SIZE)->withTotal(false);
            if ($lastId != null) {
                $request->where('id > "' . $lastId . '"');
            }
            $response = $this->client->execute($request);
            if ($response->isError() || is_null($response->toObject())) {
                break;
            }
            $results = $response->toArray()['results'];
            $data['results'] = array_merge($data['results'], $results);
            $lastId = end($results)['id'];
        } while (count($results) >= static::DEFAULT_PAGE_SIZE);

        $result = $this->getMapper($locale)->map($data['results'], $request->getResultClass());

        return $result;
    }

    /**
     * @param string $cacheKey
     * @param AbstractApiRequest $request
     * @param string $locale
     * @param bool $force
     * @param int $ttl
     * @return \Commercetools\Core\Model\Common\JsonDeserializeInterface
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws NotFoundHttpException
     */
    protected function retrieve(
        $cacheKey,
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
            $result->setContext($this->client->getConfig()->getContext());
        } else {
            $response = $this->client->execute($request);
            if ($response->isError() || is_null($response->toObject())) {
                $this->store($cacheKey, '', $ttl);
                throw new NotFoundHttpException("resource not found");
            }

            $result = $request->mapFromResponse(
                $response,
                $this->getMapper($locale)
            );

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

    /**
     * @param ClientRequestInterface $request
     * @param string $locale
     * @param QueryParams|null $params
     * @return mixed
     */
    protected function executeRequest(ClientRequestInterface $request, $locale = 'en', QueryParams $params = null)
    {
        $client = $this->getClient();

        if (!is_null($params)) {
            foreach ($params->getParams() as $param) {
                $request->addParamObject($param);
            }
        }

        $response = $this->client->execute($request);

        $mappedResponse = $request->mapFromResponse(
            $response,
            $this->getMapper($locale)
        );

        return $mappedResponse;
    }
}
