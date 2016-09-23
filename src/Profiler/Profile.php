<?php
/**
 * @author @jayS-de <jens.schulze@commercetools.de>
 */

namespace Commercetools\Symfony\CtpBundle\Profiler;

class Profile implements \IteratorAggregate, \Serializable
{
    private $name;
    private $starts = [];
    private $ends = [];
    private $profiles = [];

    public function __construct($name = 'main')
    {
        $this->name = $name;
        $this->enter();
    }

    /**
     * Starts the profiling.
     */
    public function enter()
    {
        $this->starts = [
            'wt' => microtime(true),
            'mu' => memory_get_usage(),
            'pmu' => memory_get_peak_usage(),
        ];
    }

    /**
     * Stops the profiling.
     */
    public function leave()
    {
        $this->ends = [
            'wt' => microtime(true),
            'mu' => memory_get_usage(),
            'pmu' => memory_get_peak_usage(),
        ];
    }

    public function getName()
    {
        return $this->name;
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
        return serialize([$this->name, $this->starts, $this->ends, $this->profiles]);
    }

    public function unserialize($data)
    {
        list($this->name, $this->starts, $this->ends, $this->profiles) = unserialize($data);
    }
}

