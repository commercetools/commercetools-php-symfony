<?php
namespace Commercetools\Symfony\CtpBundle\Controller;

use Commercetools\Symfony\CtpBundle\DataCollector\CommercetoolsDataCollector;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use Twig\Environment;

class ProfilerController
{
    /**
     * @var Profiler
     */
    private $profiler;
    /**
     * @var Environment
     */
    private $templating;

    public function __construct(Profiler $profiler, Environment $templating)
    {
        $this->profiler = $profiler;
        $this->templating = $templating;
    }
    
    public function details($token, $requestIndex)
    {
        $this->profiler->disable();
        $profile = $this->profiler->loadProfile($token);
        /**
         * @var CommercetoolsDataCollector $collector
         */
        $collector =  $profile->getCollector('commercetools');
        $requests = $collector->getRequestInfos();

        $entry = $requests[$requestIndex];
        if (isset($entry['request']['body']) && strpos($entry['request']['body'], '{') === 0) {
            $entry['request']['body'] = json_encode(json_decode($entry['request']['body'], true), JSON_PRETTY_PRINT);
        }
        if (isset($entry['response']['body']) && strpos($entry['response']['body'], '{') === 0) {
            $entry['response']['body'] = json_encode(json_decode($entry['response']['body'], true), JSON_PRETTY_PRINT);
        }
        return new Response($this->templating->render('@Ctp/Collector/details.html.twig', [
            'requestIndex' => $requestIndex,
            'entry' => $entry
        ]));
    }
}
