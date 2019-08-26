<?php
/**
 *
 */

namespace Commercetools\Symfony\CtpBundle\Tests\Model\Repository;

use Cache\Adapter\Common\CacheItem;
use Commercetools\Core\Client\HttpClient;
use Commercetools\Core\Config;
use Commercetools\Core\Model\JsonObjectMapper;
use Commercetools\Core\Model\Product\ProductCollection;
use Commercetools\Core\Request\AbstractApiRequest;
use Commercetools\Core\Request\AbstractQueryRequest;
use Commercetools\Core\Request\QueryAllRequestInterface;
use Commercetools\Core\Response\PagedQueryResponse;
use Commercetools\Core\Response\ResourceResponse;
use Commercetools\Symfony\CtpBundle\Model\Repository;
use Commercetools\Symfony\CtpBundle\Service\ContextFactory;
use Commercetools\Symfony\CtpBundle\Service\MapperFactory;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Cache\CacheItemPoolInterface;

class RepositoryTest extends TestCase
{
    private $cache;
    private $mapperFactory;
    private $response;
    private $client;
    private $contextFactory;

    protected function setUp()
    {
        $this->cache = $this->prophesize(CacheItemPoolInterface::class);
        $this->mapperFactory = $this->prophesize(MapperFactory::class);
        $this->contextFactory = $this->prophesize(ContextFactory::class);

        $this->response = $this->prophesize(ResourceResponse::class);
        $this->response->toArray()->willReturn([]);
        $this->response->getContext()->willReturn(null);
        $this->response->isError()->willReturn(false);
        $this->response->toObject()->willReturn(ProductCollection::of());

        $this->client = $this->prophesize(HttpClient::class);
    }

    private function getRepository()
    {
        return new TestRepository(
            true,
            $this->cache->reveal(),
            $this->client->reveal(),
            $this->mapperFactory->reveal(),
            $this->contextFactory->reveal()
        );
    }

    public function testGetMapper()
    {
        $this->mapperFactory->build('foo')->willReturn('bar')->shouldBeCalledOnce();
        $repository = $this->getRepository();
        $mapper = $repository->getMapper('foo');
        $this->assertSame('bar', $mapper);
    }

    public function testRetrieveAll()
    {
        $request = $this->prophesize(AbstractQueryRequest::class);
        $request->sort('id')->will(function () {
            return $this;
        })->shouldBeCalledOnce();
        $request->limit(Repository::DEFAULT_PAGE_SIZE)->will(function () {
            return $this;
        })->shouldBeCalledOnce();
        $request->withTotal(false)->will(function () {
            return $this;
        })->shouldBeCalledOnce();
        $request->getResultClass()->willReturn(ProductCollection::class)->shouldBeCalledOnce();

        $cachedItem = $this->prophesize(CacheItem::class);
        $cachedItem->set(Argument::type('string'))->will(function () {
            return $this;
        })->shouldBeCalledOnce();
        $cachedItem->expiresAfter(Repository::CACHE_TTL)->will(function () {
            return $this;
        })->shouldBeCalledOnce();

        $this->cache->hasItem('foo')->willReturn(false)->shouldBeCalledOnce();
        $this->cache->getItem('foo')->willReturn($cachedItem->reveal())->shouldBeCalledOnce();
        $this->cache->save(Argument::type(CacheItem::class))->shouldBeCalledOnce();

        $this->response->toArray()->willReturn(['results' => []]);
        $this->mapperFactory->build('en')->willReturn(JsonObjectMapper::of())->shouldBeCalled();

        $this->client->execute(Argument::type(QueryAllRequestInterface::class), null)
            ->willReturn($this->response->reveal())->shouldBeCalledOnce();

        $repository = $this->getRepository();
        $repository->retrieveAll('foo', $request->reveal(), 'en');
    }

    public function testRetrieveAllFromCache()
    {
        $request = $this->prophesize(AbstractQueryRequest::class);

        $serializedResult = serialize(ProductCollection::of());
        $cachedItem = $this->prophesize(CacheItem::class);
        $cachedItem->get()->willReturn($serializedResult)->shouldBeCalledOnce();

        $this->cache->hasItem('foo')->willReturn(true)->shouldBeCalledOnce();
        $this->cache->getItem('foo')->willReturn($cachedItem->reveal())->shouldBeCalledOnce();

        $repository = $this->getRepository();
        $repository->retrieveAll('foo', $request->reveal(), 'en');
    }

    public function testRetrieveFromCache()
    {
        $serializedResult = serialize(ProductCollection::of());

        $cachedItem = $this->prophesize(CacheItem::class);
        $cachedItem->get()->willReturn($serializedResult)->shouldBeCalledOnce();

        $this->cache->hasItem('foo')->willReturn(true)->shouldBeCalledOnce();
        $this->cache->getItem('foo')->willReturn($cachedItem->reveal())->shouldBeCalledOnce();

        $request = $this->prophesize(AbstractApiRequest::class);

        $repository = $this->getRepository();
        $result = $repository->retrieve('foo', $request->reveal(), 'en');

        $this->assertInstanceOf(ProductCollection::class, $result);
    }

    /**
     * @expectedException Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @expectedExceptionMessage resource not found
     */
    public function testRetrieveFromCacheWithError()
    {
        $this->cache->hasItem('foo')->willReturn(true)->shouldBeCalledOnce();
        $this->cache->getItem('foo')->willReturn([])->shouldBeCalledOnce();

        $request = $this->prophesize(AbstractApiRequest::class);

        $repository = $this->getRepository();
        $repository->retrieve('foo', $request->reveal(), 'en');
    }

    public function testStore()
    {
        $cachedItem = $this->prophesize(CacheItem::class);
        $cachedItem->set('bar')->will(function () {
            return $this;
        })->shouldBeCalledOnce();
        $cachedItem->expiresAfter(100)->will(function () {
            return $this;
        })->shouldBeCalledOnce();

        $this->cache->getItem('foo')->willReturn($cachedItem->reveal())->shouldBeCalledOnce();
        $this->cache->save(Argument::type(CacheItem::class))->shouldBeCalledOnce();

        $repository = $this->getRepository();
        $repository->store('foo', 'bar', 100);
    }
}

//phpcs:disable
class TestRepository extends Repository
{
    public function retrieveAll(
        $cacheKey,
        QueryAllRequestInterface $request,
        $locale,
        $force = false,
        $ttl = self::CACHE_TTL
    ) {
        return parent::retrieveAll($cacheKey, $request, $locale, $force, $ttl);
    }

    public function retrieve($cacheKey, AbstractApiRequest $request, $locale, $force = false, $ttl = self::CACHE_TTL)
    {
        return parent::retrieve($cacheKey, $request, $locale, $force, $ttl);
    }

    public function store($cacheKey, $data, $ttl)
    {
        parent::store($cacheKey, $data, $ttl);
    }
}
