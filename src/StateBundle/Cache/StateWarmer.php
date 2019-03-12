<?php
/**
 *
 */

namespace Commercetools\Symfony\StateBundle\Cache;

use Commercetools\Core\Error\ApiException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

class StateWarmer implements CacheWarmerInterface
{
    private $stateKeyResolver;
    private $logger;

    public function __construct(StateKeyResolver $stateKeyResolver, LoggerInterface $logger)
    {
        $this->stateKeyResolver = $stateKeyResolver;
        $this->logger = $logger;
    }

    public function isOptional()
    {
        return true;
    }

    public function warmUp($cacheDir)
    {
        try {
            $this->stateKeyResolver->fillCache();
        } catch (ApiException $exception) {
            $this->logger->info("Could not fetch states from commercetools");
        }
    }
}
