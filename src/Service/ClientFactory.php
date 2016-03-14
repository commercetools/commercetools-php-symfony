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
    private $clientCredentials;
    private $fallbackLanguages;
    private $cache;
    private $logger;
    private $converter;

    public function __construct(
        $client_id,
        $client_secret,
        $project,
        $fallbackLanguages,
        CacheAdapterInterface $cache,
        LocaleConverter $converter,
        LoggerInterface $logger
    ) {
        $this->clientCredentials = [
            'client_id' => $client_id,
            'client_secret' => $client_secret,
            'project' => $project
        ];
        $this->fallbackLanguages = $fallbackLanguages;
        $this->cache = $cache;
        $this->converter = $converter;
        $this->logger = $logger;
    }

    public function build(
        $locale,
        $fallbackLanguages = null,
        array $clientCredentials = null
    ) {
        $locale = $this->converter->convert($locale);
        if (is_null($clientCredentials)) {
            $clientCredentials = $this->clientCredentials;
        }
        if (is_null($fallbackLanguages)) {
            $fallbackLanguages = $this->fallbackLanguages;
        }
        $language = \Locale::getPrimaryLanguage($locale);
        $languages = array_merge([$language], $fallbackLanguages[$language]);
        $context = Context::of()->setLanguages($languages)->setGraceful(true)->setLocale($locale);
        $config = $clientCredentials;
        $config = Config::fromArray($config)->setContext($context);

        if (is_null($this->logger)) {
            return Client::ofConfigAndCache($config, $this->cache);
        }
        return Client::ofConfigCacheAndLogger($config, $this->cache, $this->logger);
    }
}