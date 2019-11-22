<?php
/**
 *
 */

namespace Commercetools\Symfony\CtpBundle\Service;

use Commercetools\Core\Config;
use Commercetools\Symfony\CtpBundle\CtpBundle;
use Commercetools\Symfony\CtpBundle\Profiler\CommercetoolsProfilerExtension;
use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;
use Psr\Http\Message\RequestInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Kernel;

class ConfigFactory
{
    public static function create(array $config, LoggerInterface $logger, CommercetoolsProfilerExtension $profiler = null, $debug = false)
    {
        $config = Config::fromArray($config);

        $clientOptions = $config->getClientOptions();
        $middleWares = $clientOptions['middlewares'] ?? [];

        $middleWares['useragent'] = Middleware::mapRequest(
            function (RequestInterface $request) {
                return $request->withHeader('User-Agent', $request->getHeaderLine('User-Agent') . " ctp-bundle/" . CtpBundle::VERSION . " (symfony/" . Kernel::VERSION . ")");
            });

        if ($profiler instanceof CommercetoolsProfilerExtension) {
            $middleWares['profiler'] = $profiler->getProfileMiddleWare();
        }

        $clientOptions['middlewares'] = $middleWares;
        $config->setClientOptions($clientOptions);

        if ($debug) {
            $oauthOptions = $config->getOAuthClientOptions();
            $oauthMiddleWares = $oauthOptions['middlewares'] ?? [];
            $oauthMiddleWares['ctp_log'] = Middleware::log($logger, new MessageFormatter());
            if ($profiler instanceof CommercetoolsProfilerExtension) {
                $oauthMiddleWares['profiler'] = $profiler->getProfileMiddleWare();
            }
            $oauthOptions['middlewares'] = $oauthMiddleWares;
            $config->setOAuthClientOptions($oauthOptions);
        }

        return $config;
    }
}
