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
    private $profile;

    public function __construct(Profile $profile)
    {
        $this->profile = $profile;
    }

    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        $this->data['duration'] = $this->profile->getDuration();
        $this->data['requestInfos'] = $this->profile->getRequestInfos();
    }

    public function getDuration()
    {
        return isset($this->data['duration']) ? $this->data['duration'] : 0;
    }

    public function getRequestCount()
    {
        return count($this->data['requestInfos']);
    }

    public function getRequestInfos()
    {
        return $this->data['requestInfos'];
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
