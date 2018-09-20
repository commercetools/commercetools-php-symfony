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
    private $profiler;

    public function __construct(
        Config $config,
        ContextFactory $contextFactory,
        $cache,
        LocaleConverter $converter,
        LoggerInterface $logger,
        CommercetoolsProfilerExtension $profiler = null
    ) {
        $this->config = $config;
        $this->contextFactory = $contextFactory;
        $this->cache = $cache;
        $this->converter = $converter;
        $this->logger = $logger;
        $this->profiler = $profiler;
    }

    /**
     * @param string $locale
     * @param Context $context
     * @param Config|array $config
     * @return Client
     */
    public function build(
        $locale = null,
        Context $context = null,
        $config = null
    ) {
        if (is_array($config)) {
            $config = Config::fromArray($config);
        }
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

        if ($this->profiler instanceof CommercetoolsProfilerExtension) {
            $client->getHttpClient()->addHandler($this->profiler->getProfileMiddleWare());
        }

        return $client;
    }
}
