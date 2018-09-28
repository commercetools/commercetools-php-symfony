<?php
/**
 * @author @jayS-de <jens.schulze@commercetools.de>
 */

namespace Commercetools\Symfony\CtpBundle\Profiler;

use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Stopwatch\Stopwatch;

class CommercetoolsProfilerExtension
{
    private $actives = array();
    private $stopwatch;
    private $events;

    public function __construct(Profile $profile, Stopwatch $stopwatch = null)
    {
        $this->actives[] = $profile;

        $this->stopwatch = $stopwatch;
        $this->events = new \SplObjectStorage();
    }

    public function enter(Profile $profile)
    {
        if ($this->stopwatch) {
            $this->events[$profile] = $this->stopwatch->start($profile->getName(), 'commercetools');
        }

        $this->actives[0]->addProfile($profile);
        array_unshift($this->actives, $profile);
    }

    public function leave(Profile $profile, ResponseInterface $response = null)
    {
        $profile->leave($response);
        array_shift($this->actives);

        if (1 === count($this->actives)) {
            $this->actives[0]->leave();
        }

        if ($this->stopwatch) {
            $this->events[$profile]->stop($profile->getName());
            unset($this->events[$profile]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'commercetools-profiler';
    }

    public function getProfileMiddleWare()
    {
        return ProfileMiddleware::create($this);
    }
}
