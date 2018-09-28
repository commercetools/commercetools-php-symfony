<?php
/**
 *
 */

namespace Commercetools\Symfony\StateBundle\Tests\Cache;


use Cache\Adapter\Common\CacheItem;
use Commercetools\Core\Model\State\State;
use Commercetools\Core\Model\State\StateCollection;
use Commercetools\Core\Model\State\StateReference;
use Commercetools\Symfony\StateBundle\Cache\StateKeyResolver;
use Commercetools\Symfony\StateBundle\Model\Repository\StateRepository;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Cache\CacheItemPoolInterface;

class StateKeyResolverTest extends TestCase
{
    public function testResolve()
    {
        $stateRepository = $this->prophesize(StateRepository::class);
        $stateRepository->getById('foo')->willReturn(StateReference::ofId('foo')->setKey('bar'))->shouldBeCalledOnce();

        $cacheItem = $this->prophesize(CacheItem::class);
        $cacheItem->isHit()->willReturn(false, true)->shouldBeCalledTimes(2);
        $cacheItem->set('bar')->shouldBeCalledOnce();
        $cacheItem->expiresAfter(Argument::type(\DateInterval::class))->shouldBeCalledOnce();
        $cacheItem->get()->willReturn('cache-item-value')->shouldBeCalledOnce();

        $cache = $this->prophesize(CacheItemPoolInterface::class);
        $cache->getItem('foo')->willReturn($cacheItem->reveal())->shouldBeCalledTimes(2);
        $cache->save(Argument::type(CacheItem::class))->shouldBeCalledOnce();

        $stateKeyResolver = new StateKeyResolver($stateRepository->reveal(), $cache->reveal());
        $resolved = $stateKeyResolver->resolve(StateReference::ofId('foo'));
        $this->assertSame('cache-item-value', $resolved);
    }

    public function testResolveFromCache()
    {
        $stateRepository = $this->prophesize(StateRepository::class);

        $cacheItem = $this->prophesize(CacheItem::class);
        $cacheItem->isHit()->willReturn(true)->shouldBeCalledOnce();
        $cacheItem->get()->willReturn('cache-item-value')->shouldBeCalledOnce();

        $cache = $this->prophesize(CacheItemPoolInterface::class);
        $cache->getItem('foo')->willReturn($cacheItem->reveal())->shouldBeCalledTimes(1);

        $stateKeyResolver = new StateKeyResolver($stateRepository->reveal(), $cache->reveal());
        $resolved = $stateKeyResolver->resolve(StateReference::ofId('foo'));
        $this->assertSame('cache-item-value', $resolved);
    }

    public function testResolveFromState()
    {
        $stateRepository = $this->prophesize(StateRepository::class);

        $stateObj = $this->prophesize(State::class);
        $stateObj->getKey()->willReturn('bar')->shouldBeCalledOnce();

        $stateReference = $this->prophesize(StateReference::class);
        $stateReference->getObj()->willReturn($stateObj->reveal())->shouldBeCalledTimes(2);

        $cache = $this->prophesize(CacheItemPoolInterface::class);
        $stateKeyResolver = new StateKeyResolver($stateRepository->reveal(), $cache->reveal());

        $resolved = $stateKeyResolver->resolve($stateReference->reveal());
        $this->assertSame('bar', $resolved);
    }

    public function testResolveKey()
    {
        $stateRepository = $this->prophesize(StateRepository::class);
        $stateRepository->getByKey('bar')->willReturn(StateReference::ofKey('bar')->setId('foo'))->shouldBeCalledOnce();

        $cacheItem = $this->prophesize(CacheItem::class);
        $cacheItem->isHit()->willReturn(false, true)->shouldBeCalledTimes(2);
        $cacheItem->set('foo')->shouldBeCalledOnce();
        $cacheItem->expiresAfter(Argument::type(\DateInterval::class))->shouldBeCalledOnce();
        $cacheItem->get()->willReturn('cache-item-value')->shouldBeCalledOnce();

        $cache = $this->prophesize(CacheItemPoolInterface::class);
        $cache->getItem('bar')->willReturn($cacheItem->reveal())->shouldBeCalledTimes(2);
        $cache->save(Argument::type(CacheItem::class))->shouldBeCalledOnce();

        $stateKeyResolver = new StateKeyResolver($stateRepository->reveal(), $cache->reveal());
        $resolved = $stateKeyResolver->resolveKey('bar');
        $this->assertSame('cache-item-value', $resolved);
    }

    public function testResolveKeyFromCache()
    {
        $stateRepository = $this->prophesize(StateRepository::class);

        $cacheItem = $this->prophesize(CacheItem::class);
        $cacheItem->isHit()->willReturn(true)->shouldBeCalledOnce();
        $cacheItem->get()->willReturn('cache-item-value')->shouldBeCalledOnce();

        $cache = $this->prophesize(CacheItemPoolInterface::class);
        $cache->getItem('bar')->willReturn($cacheItem->reveal())->shouldBeCalledOnce();

        $stateKeyResolver = new StateKeyResolver($stateRepository->reveal(), $cache->reveal());
        $resolved = $stateKeyResolver->resolveKey('bar');
        $this->assertSame('cache-item-value', $resolved);
    }

    public function testFillCache()
    {
        $state = $this->prophesize(State::class);
        $state->getId()->willReturn('foo')->shouldBeCalledTimes(3);
        $state->getKey()->willReturn('bar')->shouldBeCalledTimes(3);
        $state->setContext(Argument::any())->shouldBeCalledOnce();
        $state->parentSet(Argument::any())->shouldBeCalledOnce();
        $state->rootSet(Argument::any())->shouldBeCalledOnce();

        $stateCollection = StateCollection::of()->add($state->reveal());

        $stateRepository = $this->prophesize(StateRepository::class);
        $stateRepository->getStates()->willReturn($stateCollection)->shouldBeCalled();

        $cacheItem1 = $this->prophesize(CacheItem::class);
        $cacheItem2 = $this->prophesize(CacheItem::class);

        $cache = $this->prophesize(CacheItemPoolInterface::class);
        $cache->getItem('foo')->willReturn($cacheItem1->reveal())->shouldBeCalledOnce();
        $cache->getItem('bar')->willReturn($cacheItem2->reveal())->shouldBeCalledOnce();
        $cache->save(Argument::type(CacheItem::class))->shouldBeCalledTimes(2);

        $stateKeyResolver = new StateKeyResolver($stateRepository->reveal(), $cache->reveal());
        $stateKeyResolver->fillCache();
    }

}
