<?php
/**
 *
 */

namespace Commercetools\Symfony\StateBundle\Cache;


use Commercetools\Symfony\StateBundle\Model\Repository\StateRepository;
use Psr\Cache\CacheItemPoolInterface;

class StateCacheHelper
{
    private $stateRepository;
    private $cache;

    public function __construct(StateRepository $stateRepository, CacheItemPoolInterface $cache)
    {
        $this->stateRepository = $stateRepository;
        $this->cache = $cache;
    }

    public function resolveFromId($id)
    {
        $item = $this->cache->getItem($id);
        if ($item->isHit()) {
            return $item->get();
        }

        $this->fillCache();

        $item = $this->cache->getItem($id);
        if ($item->isHit()) {
            return $item->get();
        }

        return null;
    }

    private function fillCache()
    {
        $states = $this->stateRepository->getStates();

        foreach ($states as $state) {
            $item = $this->cache->getItem($state->getId());
            $item->set($state->getKey());
            $this->cache->save($item);
        }
    }

}
