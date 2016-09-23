<?php
/**
 * @author @jayS-de <jens.schulze@commercetools.de>
 */

namespace Commercetools\Symfony\CtpBundle\Profiler;

use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class ProfileMiddleware
{
    private $handler;
    private $profiler;
    
    public function __construct(CommercetoolsProfilerExtension $profiler, callable $handler)
    {
        $this->profiler = $profiler;
        $this->handler = $handler;
    }
    public static function create(CommercetoolsProfilerExtension $profiler)
    {
        return function (callable $handler) use ($profiler) {
            return new self($profiler, $handler);
        };
    }

    /**
     * @param RequestInterface $request
     * @param array $options
     * @return PromiseInterface
     */
    public function __invoke(RequestInterface $request, array $options)
    {
        $fn = $this->handler;

        $this->profiler->enter($profile = new Profile((string)$request->getUri()->getPath()));

        return $fn($request, $options)
            ->then(function (ResponseInterface $response) use ($profile) {
                $this->profiler->leave($profile);
                return $response;
            });
    }
}
