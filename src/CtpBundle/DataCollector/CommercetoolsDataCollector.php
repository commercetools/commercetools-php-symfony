<?php
/**
 * @author @jayS-de <jens.schulze@commercetools.de>
 */

namespace Commercetools\Symfony\CtpBundle\DataCollector;

use Commercetools\Symfony\CtpBundle\Profiler\Profile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

class CommercetoolsDataCollector extends DataCollector
{
    /**
     * @var Profile
     */
    private $profile;

    /**
     * CommercetoolsDataCollector constructor.
     * @param Profile $profile
     */
    public function __construct(Profile $profile)
    {
        $this->profile = $profile;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param \Exception|null $exception
     */
    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        $this->data['duration'] = $this->profile->getDuration();
        $this->data['requestInfos'] = $this->profile->getRequestInfos();
    }

    public function getDuration()
    {
        return $this->data['duration'] ?? 0;
    }

    public function getRequestCount()
    {
        return isset($this->data['requestInfos']) ? count($this->data['requestInfos']) : 0;
    }

    public function getRequestInfos()
    {
        return $this->data['requestInfos'] ?? null;
    }

    public function getName()
    {
        return 'commercetools';
    }

    public function reset()
    {
        $this->data = [];
    }
}
