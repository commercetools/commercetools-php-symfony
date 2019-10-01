<?php
/**
 *
 */

namespace Commercetools\Symfony\StateBundle\Cache;

use Commercetools\Core\Model\State\State;
use Commercetools\Core\Model\State\StateCollection;
use Commercetools\Core\Model\State\StateReference;
use Commercetools\Symfony\StateBundle\Model\Repository\StateRepository;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

class StateKeyResolver
{
    private $stateRepository;
    private $cache;

    /**
     * StateKeyResolver constructor.
     * @param StateRepository $stateRepository
     * @param CacheItemPoolInterface $cache
     */
    public function __construct(StateRepository $stateRepository, CacheItemPoolInterface $cache)
    {
        $this->stateRepository = $stateRepository;
        $this->cache = $cache;
    }

    /**
     * @param StateReference $state
     * @return mixed|null|string
     */
    public function resolve(StateReference $state)
    {
        if ($state->getObj() instanceof State) {
            return $state->getObj()->getKey();
        }

        $item = $this->cache->getItem($state->getId());

        if ($item->isHit()) {
            return $item->get();
        }

        $state = $this->stateRepository->getById($state->getId());
        $this->storeValue($item, $state->getKey());

        $item = $this->cache->getItem($state->getId());

        if ($item->isHit()) {
            return $item->get();
        }

        return null;
    }

    public function resolveKey($key)
    {
        $item = $this->cache->getItem($key);

        if ($item->isHit()) {
            return $item->get();
        }

        $state = $this->stateRepository->getByKey($key);
        $this->storeValue($item, $state->getId());

        $item = $this->cache->getItem($key);

        if ($item->isHit()) {
            return $item->get();
        }

        return null;
    }

    private function storeValue(CacheItemInterface $item, $value)
    {
        $item->set($value);
        $item->expiresAfter(new \DateInterval('PT1H'));
        $this->cache->save($item);
    }

    public function fillCache()
    {
        $states = $this->stateRepository->getStates();

        if ($states instanceof StateCollection) {
            foreach ($states as $state) {
                $item = $this->cache->getItem($state->getId());
                $this->storeValue($item, $state->getKey());
                $item = $this->cache->getItem($state->getKey());
                $this->storeValue($item, $state->getId());
            }
        }
    }
}
