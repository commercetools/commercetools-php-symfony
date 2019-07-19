<?php
/**
 *
 */

namespace Commercetools\Symfony\CtpBundle\Service;

use Commercetools\Symfony\CtpBundle\CtpBundle;
use Commercetools\Symfony\CtpBundle\Profiler\CommercetoolsProfilerExtension;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Psr\Http\Message\RequestInterface;
use Symfony\Component\HttpKernel\Kernel;

class HandlerStackFactory
{
    /**
     * @param HandlerStack|null $handler
     * @param CommercetoolsProfilerExtension|null $profiler
     * @return HandlerStack
     */
    public static function create(HandlerStack $handler = null, CommercetoolsProfilerExtension $profiler = null)
    {
        if (is_null($handler)) {
            $handler = HandlerStack::create();
        }

        $handler->push(
            Middleware::mapRequest(function (RequestInterface $request) {
                return $request->withHeader('User-Agent', $request->getHeaderLine('User-Agent') . " ctp-bundle/" . CtpBundle::VERSION . " (symfony/" . Kernel::VERSION . ")");
            }),
            'useragent'
        );

        if ($profiler instanceof CommercetoolsProfilerExtension) {
            $handler->push($profiler->getProfileMiddleWare(), 'profiler');
        }

        return $handler;
    }
}
