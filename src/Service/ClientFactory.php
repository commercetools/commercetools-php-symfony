<?php
/**
 * @author: Ylambers <yaron.lambers@commercetools.de>
 */

namespace Commercetools\Symfony\CtpBundle\Service;

use Commercetools\Core\Client;
use Commercetools\Core\Config;
use Commercetools\Core\Model\Common\Context;
use Commercetools\Symfony\CtpBundle\Profiler\CommercetoolsProfilerExtension;
use Commercetools\Symfony\CtpBundle\Profiler\ProfileMiddleware;
use Psr\Log\LoggerInterface;

class ClientFactory
{
    private $contextFactory;
    private $cache;
    private $logger;
    private $converter;
    private $config;
    private $environment;

    public function __construct(
        Config $config,
        ContextFactory $contextFactory,
        $cache,
        LocaleConverter $converter,
        LoggerInterface $logger,
        CommercetoolsProfilerExtension $profiler,
        $environment
    ) {
        $this->config = $config;
        $this->contextFactory = $contextFactory;
        $this->cache = $cache;
        $this->converter = $converter;
        $this->logger = $logger;
        $this->config = $config;
        $this->profiler = $profiler;
        $this->environment = $environment;
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
            $client = Client::ofConfigAndCache($config, $this->cache);
        } else {
            $client = Client::ofConfigCacheAndLogger($config, $this->cache, $this->logger);
        }

        if (in_array($this->environment, ['dev', 'test'], true)) {
            $client->getHttpClient()->addHandler(ProfileMiddleware::create($this->profiler));
        }

        return $client;
    }
}
