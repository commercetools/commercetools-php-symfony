<?php
/**
 *
 */

namespace Commercetools\Symfony\StateBundle\Tests\EventListener;


use Commercetools\Symfony\StateBundle\EventListener\TransitionSubscriber;
use Commercetools\Symfony\StateBundle\Model\TransitionHandler\SubjectHandler;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\Workflow\Event\Event;

class TransitionSubscriberTest extends TestCase
{
    public function testTransitionSubject()
    {
        $subjectHandler = $this->prophesize(SubjectHandler::class);
        $subjectHandler->handle(Argument::type(Event::class))->shouldBeCalledOnce();
        $event = $this->prophesize(Event::class);

        $transitionSubscriber = new TransitionSubscriber($subjectHandler->reveal());
        $this->assertInstanceOf(TransitionSubscriber::class, $transitionSubscriber);
        $transitionSubscriber->transitionSubject($event->reveal());
    }

}
