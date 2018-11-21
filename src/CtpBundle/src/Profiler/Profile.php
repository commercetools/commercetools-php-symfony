<?php
/**
 * @author @jayS-de <jens.schulze@commercetools.de>
 */

namespace Commercetools\Symfony\CtpBundle\Profiler;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class Profile implements \IteratorAggregate, \Serializable
{
    private $name;
    private $starts = [];
    private $ends = [];
    private $profiles = [];
    private $request = [];
    private $response = [];

    public function __construct($name = 'main', RequestInterface $request = null)
    {
        $this->name = $name;
        $this->enter($request);
    }

    /**
     * Starts the profiling.
     * @param RequestInterface $request
     */
    public function enter(RequestInterface $request = null)
    {
        $this->starts = [
            'wt' => microtime(true),
            'mu' => memory_get_usage(),
            'pmu' => memory_get_peak_usage(),
        ];

        if ($request) {
            $this->request = [
                'method' => $request->getMethod(),
                'url' => (string)$request->getUri(),
                'headers' => $request->getHeaders(),
                'body' => (string)$request->getBody()
            ];
        }
    }

    /**
     * Stops the profiling.
     * @param ResponseInterface $response
     */
    public function leave(ResponseInterface $response = null)
    {
        $this->ends = [
            'wt' => microtime(true),
            'mu' => memory_get_usage(),
            'pmu' => memory_get_peak_usage(),
        ];
        if ($response) {
            $this->response = [
                'headers' => $response->getHeaders(),
                'statusCode' => $response->getStatusCode(),
                'body' => (string)$response->getBody(),
            ];
        }
    }

    public function getName()
    {
        return $this->name;
    }

    public function getDuration()
    {
        if (isset($this->starts['wt']) && isset($this->ends['wt'])) {
            return 1000 * ($this->ends['wt'] - $this->starts['wt']);
        }

        return 0;
    }

    public function getRequestInfos()
    {
        $info = [];

        if ($entry = $this->getRequestInfo()) {
            $info[] = $entry;
        }
        foreach ($this->profiles as $profile) {
            $info = array_merge($info, $profile->getRequestInfos());
        }

        return $info;
    }

    protected function getRequestInfo()
    {
        $info = [];

        if ($this->request) {
            $info['request'] = $this->request;
        }
        if ($this->response) {
            $info['response'] = $this->response;
            $info['duration'] = $this->getDuration();
        }
        return $info;
    }

    public function addProfile(Profile $profile)
    {
        $this->profiles[] = $profile;
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->profiles);
    }

    public function serialize()
    {
        return serialize([$this->name, $this->starts, $this->ends, $this->profiles, $this->request, $this->response]);
    }

    public function unserialize($data)
    {
        list($this->name, $this->starts, $this->ends, $this->profiles, $this->request, $this->response) = unserialize($data);
    }
}

