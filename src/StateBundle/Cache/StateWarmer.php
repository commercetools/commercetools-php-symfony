<?php
/**
 *
 */

namespace Commercetools\Symfony\StateBundle\Cache;


use Commercetools\Symfony\StateBundle\Model\Repository\StateRepository;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

class StateWarmer implements CacheWarmerInterface
{
    private $stateRepository;
    private $cache;

    public function __construct(StateRepository $stateRepository,  CacheItemPoolInterface $cache)
    {
        $this->stateRepository = $stateRepository;
        $this->cache = $cache;
    }

    public function isOptional()
    {
        return true;
    }

    public function warmUp($cacheDir)
    {
        $states = $this->stateRepository->getStates();

        foreach ($states as $state) {
            $item = $this->cache->getItem($state->getId());
            $item->set($state->getKey());
            $this->cache->save($item);
        }
    }
}
