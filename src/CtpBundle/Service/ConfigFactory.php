<?php
/**
 *
 */

namespace Commercetools\Symfony\CtpBundle\Service;

use Commercetools\Core\Config;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;
use Psr\Log\LoggerInterface;

class ConfigFactory
{
    public static function create(array $config, HandlerStack $handlerStack, LoggerInterface $logger)
    {
        $config = Config::fromArray($config);

        $config->setClientOptions(['handler' => $handlerStack]);

        // in debug only
        $handler = HandlerStack::create();
        $handler->push(Middleware::log($logger, new MessageFormatter()));
        $config->setOAuthClientOptions(['handler' => $handler]);

        return $config;
    }
}
