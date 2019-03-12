<?php
/**
 *
 */

namespace Commercetools\Symfony\CtpBundle\Tests\Profiler;

use Commercetools\Symfony\CtpBundle\Profiler\CommercetoolsProfilerExtension;
use Commercetools\Symfony\CtpBundle\Profiler\Profile;
use Commercetools\Symfony\CtpBundle\Profiler\ProfileMiddleware;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\Stopwatch\Stopwatch;

class CommercetoolsProfilerExtensionTest extends TestCase
{
    public function testEnter()
    {
        $profile = $this->prophesize(Profile::class);
        $profile->addProfile(Argument::type(Profile::class))->willReturn('foo')->shouldBeCalledOnce();

        $stopwatch = $this->prophesize(Stopwatch::class);
        $profilerExtension = new CommercetoolsProfilerExtension($profile->reveal(), $stopwatch->reveal());

        $profile2 = $this->prophesize(Profile::class);
        $profile2->getName()->willReturn('foo')->shouldBeCalledOnce();
        $profilerExtension->enter($profile2->reveal());
    }

    public function testLeave()
    {
        $profile = $this->prophesize(Profile::class);
        $profile->addProfile(Argument::type(Profile::class))->willReturn('foo')->shouldBeCalledOnce();
        $profile->leave()->shouldBeCalledOnce();

        $stopwatch = $this->prophesize(Stopwatch::class);
        $stopwatch->start('foo', 'commercetools')->will(function () {
            return $this;
        })->shouldBeCalledOnce();
        $stopwatch->stop('foo')->shouldBeCalled();

        $profilerExtension = new CommercetoolsProfilerExtension($profile->reveal(), $stopwatch->reveal());

        $profile2 = $this->prophesize(Profile::class);
        $profile2->getName()->willReturn('foo')->shouldBeCalledTimes(2);
        $profile2->leave(null)->willReturn('foo')->shouldBeCalledOnce();

        $profilerExtension->enter($profile2->reveal());
        $profilerExtension->leave($profile2->reveal());
    }

    public function testGetName()
    {
        $profile = $this->prophesize(Profile::class);
        $stopwatch = $this->prophesize(Stopwatch::class);
        $profilerExtension = new CommercetoolsProfilerExtension($profile->reveal(), $stopwatch->reveal());

        $this->assertSame('commercetools-profiler', $profilerExtension->getName());
    }

    public function testGetProfileMiddleWare()
    {
        $profile = $this->prophesize(Profile::class);
        $stopwatch = $this->prophesize(Stopwatch::class);
        $profilerExtension = new CommercetoolsProfilerExtension($profile->reveal(), $stopwatch->reveal());

        $middleWare = $profilerExtension->getProfileMiddleWare();
        $this->assertInstanceOf(ProfileMiddleware::class, $middleWare(function () {
            return true;
        }));
    }
}
