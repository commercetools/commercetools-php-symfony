<?php
/**
 *
 */

namespace Commercetools\Symfony\StateBundle\Cache;


use Commercetools\Core\Model\State\State;
use Commercetools\Core\Model\State\StateReference;
use Commercetools\Symfony\StateBundle\Model\Repository\StateRepository;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

class StateKeyResolver
{
    private $stateRepository;
    private $cache;

    public function __construct(StateRepository $stateRepository, CacheItemPoolInterface $cache)
    {
        $this->stateRepository = $stateRepository;
        $this->cache = $cache;
    }

    public function resolve(StateReference $state)
    {
        if ($state->getObj() instanceof State) {
            return $state->getObj()->getKey();
        }

        $item = $this->cache->getItem($state->getId());
        if ($item->isHit()) {
            return $item->get();
        }

        $this->store($item, $this->stateRepository->getById($state->getId()));

        $item = $this->cache->getItem($state->getId());
        if ($item->isHit()) {
            return $item->get();
        }

        return null;
    }

    private function store(CacheItemInterface $item, State $state)
    {
        $item->set($state->getKey());
        $item->expiresAfter(new \DateInterval('PT1H'));
        $this->cache->save($item);
    }

    public function fillCache()
    {
        $states = $this->stateRepository->getStates();

        foreach ($states as $state) {
            $item = $this->cache->getItem($state->getId());
            $this->store($item, $state);
        }
    }
}
