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
    private $stateKeyResolver;

    public function __construct(StateKeyResolver $stateKeyResolver)
    {
        $this->stateKeyResolver = $stateKeyResolver;
    }

    public function isOptional()
    {
        return true;
    }

    public function warmUp($cacheDir)
    {
        $this->stateKeyResolver->fillCache();
    }
}
