<?php
/**
 * @author @jayS-de <jens.schulze@commercetools.de>
 */

namespace Commercetools\Symfony\CtpBundle\DataCollector;

use Commercetools\Symfony\CtpBundle\Logger\Logger;
use Commercetools\Symfony\CtpBundle\Profiler\Profile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

class CommercetoolsDataCollector extends DataCollector
{
    private $logger;
    private $profile;

    public function __construct(Logger $logger, Profile $profile)
    {
        $this->logger = $logger;
        $this->profile = $profile;
    }

    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        $this->data['logger'] = $this->logger->getMessages();
        $this->data['profile']['duration'] = $this->profile->getDuration();
    }

    public function getLogs()
    {
        return $this->data['logger'];
    }

    public function getRequestCount()
    {
        return count($this->data['logger']);
    }

    public function getDuration()
    {
        return isset($this->data['profile']['duration']) ? $this->data['profile']['duration'] : 0;
    }

    public function getName()
    {
        return 'commercetools';
    }
}
