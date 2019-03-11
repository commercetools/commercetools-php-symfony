<?php
/**
 *
 */

namespace Commercetools\Symfony\CtpBundle\Tests\DataCollector;

use Commercetools\Symfony\CtpBundle\DataCollector\CommercetoolsDataCollector;
use Commercetools\Symfony\CtpBundle\Profiler\Profile;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CommercetoolsDataCollectorTest extends TestCase
{
    public function testCollect()
    {
        $profile = $this->prophesize(Profile::class);
        $profile->getDuration()->willReturn(5)->shouldBeCalledOnce();
        $profile->getRequestInfos()->willReturn(['foo' => 'bar'])->shouldBeCalledOnce();

        $request = $this->prophesize(Request::class);
        $response = $this->prophesize(Response::class);

        $dataCollector = new CommercetoolsDataCollector($profile->reveal());
        $dataCollector->collect($request->reveal(), $response->reveal());

        $this->assertSame(5, $dataCollector->getDuration());
        $this->assertArrayHasKey('foo', $dataCollector->getRequestInfos());
        $this->assertCount(1, $dataCollector->getRequestInfos());
        $this->assertSame(1, $dataCollector->getRequestCount());
        $this->assertSame('commercetools', $dataCollector->getName());
    }

    public function testReset()
    {
        $profile = $this->prophesize(Profile::class);
        $profile->getDuration()->willReturn(5)->shouldBeCalledOnce();
        $profile->getRequestInfos()->willReturn(['foo' => 'bar'])->shouldBeCalledOnce();

        $request = $this->prophesize(Request::class);
        $response = $this->prophesize(Response::class);

        $dataCollector = new CommercetoolsDataCollector($profile->reveal());
        $dataCollector->collect($request->reveal(), $response->reveal());

        $dataCollector->reset();

        $this->assertNull($dataCollector->getRequestInfos());
        $this->assertSame(0, $dataCollector->getRequestCount());
        $this->assertSame(0, $dataCollector->getDuration());
    }
}
