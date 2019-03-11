<?php
/**
 *
 */

namespace Commercetools\Symfony\CtpBundle\Tests\Controller;

use Commercetools\Symfony\CtpBundle\Controller\ProfilerController;
use Commercetools\Symfony\CtpBundle\DataCollector\CommercetoolsDataCollector;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\HttpKernel\Profiler\Profile;
use Symfony\Component\HttpKernel\Profiler\Profiler;

class ProfilerControllerTest extends TestCase
{
    public function testDetails()
    {
        $requestInfos = [
            'bar' => 'baz'
        ];

        $commercetoolsDataCollector = $this->prophesize(CommercetoolsDataCollector::class);
        $commercetoolsDataCollector->getRequestInfos()->willReturn($requestInfos)->shouldBeCalledOnce();

        $profile = $this->prophesize(Profile::class);
        $profile->getCollector('commercetools')->willReturn($commercetoolsDataCollector)->shouldBeCalledOnce();

        $profiler = $this->prophesize(Profiler::class);
        $profiler->disable()->shouldBeCalledOnce();
        $profiler->loadProfile('foo')->willReturn($profile)->shouldBeCalledOnce();

        $templating = $this->prophesize(EngineInterface::class);
        $templating->renderResponse(Argument::containingString('details.html.twig'), Argument::type('array'))
            ->will(function ($args) {
                return $args;
            })->shouldBeCalledOnce();

        $profilerController = new ProfilerController($profiler->reveal(), $templating->reveal());
        $response = $profilerController->details('foo', 'bar');

        $expected = [
            '@Ctp/Collector/details.html.twig', [
                'requestIndex' => 'bar',
                'entry' => 'baz'
            ]
        ];

        $this->assertEquals($expected, $response);
    }

    public function testDetailsWithJson()
    {
        $requestInfos = [
            'baz' => [
                'request' => ['body' => '{"foobar":"true"}'],
                'response' => ['body' => '{"barfoo":"false"}']
            ]
        ];

        $commercetoolsDataCollector = $this->prophesize(CommercetoolsDataCollector::class);
        $commercetoolsDataCollector->getRequestInfos()->willReturn($requestInfos)->shouldBeCalledOnce();

        $profile = $this->prophesize(Profile::class);
        $profile->getCollector('commercetools')->willReturn($commercetoolsDataCollector)->shouldBeCalledOnce();

        $profiler = $this->prophesize(Profiler::class);
        $profiler->disable()->shouldBeCalledOnce();
        $profiler->loadProfile('foo')->willReturn($profile)->shouldBeCalledOnce();

        $templating = $this->prophesize(EngineInterface::class);
        $templating->renderResponse(Argument::containingString('details.html.twig'), Argument::type('array'))
            ->will(function ($args) {
                return $args;
            })->shouldBeCalledOnce();

        $profilerController = new ProfilerController($profiler->reveal(), $templating->reveal());
        $response = $profilerController->details('foo', 'baz');

        $expected = [
            '@Ctp/Collector/details.html.twig', [
                'requestIndex' => 'baz',
                'entry' => [
                    'request' => ['body' => json_encode(json_decode('{"foobar":"true"}', true), JSON_PRETTY_PRINT)],
                    'response' => ['body' => json_encode(json_decode('{"barfoo":"false"}', true), JSON_PRETTY_PRINT)]
                ]
            ]
        ];

        $this->assertEquals($expected, $response);
    }
}
