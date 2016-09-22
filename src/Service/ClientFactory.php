<?php
/**
 * @author: Ylambers <yaron.lambers@commercetools.de>
 */

namespace Commercetools\Symfony\CtpBundle\Service;


use Commercetools\Core\Cache\CacheAdapterInterface;
use Commercetools\Core\Client;
use Commercetools\Core\Config;
use Commercetools\Core\Model\Common\Context;
use Psr\Log\LoggerInterface;

class ClientFactory
{
    private $contextFactory;
    private $cache;
    private $logger;
    private $converter;
    private $config;

    public function __construct(
        Config $config,
        ContextFactory $contextFactory,
        $cache,
        LocaleConverter $converter,
        LoggerInterface $logger
    ) {
        $this->config = $config;
        $this->contextFactory = $contextFactory;
        $this->cache = $cache;
        $this->converter = $converter;
        $this->logger = $logger;
        $this->config = $config;
    }

    /**
     * @param string $locale
     * @param Context $context
     * @param Config $config
     * @return Client
     */
    public function build(
        $locale = null,
        Context $context = null,
        Config $config = null
    ) {
        if (is_null($config)) {
            $config = $this->config;
        }
        if (is_null($context)) {
            $context = $this->contextFactory->build($locale);
        }
        $config->setContext($context);

        if (is_null($this->logger)) {
            return Client::ofConfigAndCache($config, $this->cache);
        }
        return Client::ofConfigCacheAndLogger($config, $this->cache, $this->logger);
    }
}
