<?php
/**
 *
 */

namespace Commercetools\Symfony\StateBundle\Cache;

use GuzzleHttp\Exception\ClientException;
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
        } catch (ClientException $exception) {
            $this->logger->info("Could not fetch states from commercetools");
        }
    }
}
