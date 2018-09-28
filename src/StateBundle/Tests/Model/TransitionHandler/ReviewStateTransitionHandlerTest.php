<?php
/**
 *
 */

namespace Commercetools\Symfony\StateBundle\Tests\Model\TransitionHandler;


use Commercetools\Core\Model\Review\Review;
use Commercetools\Core\Request\Reviews\Command\ReviewTransitionStateAction;
use Commercetools\Symfony\ReviewBundle\Manager\ReviewManager;
use Commercetools\Symfony\StateBundle\Model\TransitionHandler\ReviewStateTransitionHandler;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\Workflow\Event\Event;
use Symfony\Component\Workflow\Transition;

class ReviewStateTransitionHandlerTest extends TestCase
{
    public function testHandle()
    {
        $manager = $this->prophesize(ReviewManager::class);
        $manager->dispatch(
            Argument::type(Review::class),
            Argument::type(ReviewTransitionStateAction::class),
            null
        )->willReturn([])->shouldBeCalledOnce();
        $manager->apply(
            Argument::type(Review::class),
            Argument::type('array')
        )->shouldBeCalledOnce();

        $itemStateTransitionHandler = new ReviewStateTransitionHandler($manager->reveal());

        $transition = $this->prophesize(Transition::class);
        $transition->getTos()->willReturn(['foo'])->shouldBeCalledOnce();

        $event = $this->prophesize(Event::class);
        $event->getSubject()->willReturn(Review::of())->shouldBeCalledOnce();
        $event->getTransition()->willReturn($transition->reveal())->shouldBeCalledOnce();

        $itemStateTransitionHandler->handle($event->reveal());
    }
}
