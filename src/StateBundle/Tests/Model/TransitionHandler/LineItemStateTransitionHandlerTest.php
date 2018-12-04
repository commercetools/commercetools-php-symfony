<?php
/**
 *
 */

namespace Commercetools\Symfony\StateBundle\Tests\Model\TransitionHandler;


use Commercetools\Core\Model\Order\Order;
use Commercetools\Core\Request\Orders\Command\OrderTransitionLineItemStateAction;
use Commercetools\Symfony\CartBundle\Manager\OrderManager;
use Commercetools\Symfony\StateBundle\Model\ItemStateWrapper;
use Commercetools\Symfony\StateBundle\Model\TransitionHandler\LineItemStateTransitionHandler;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\Workflow\Event\Event;
use Symfony\Component\Workflow\Transition;

class LineItemStateTransitionHandlerTest extends TestCase
{
    public function testHandle()
    {
        $manager = $this->prophesize(OrderManager::class);
        $manager->dispatch(
            Argument::type(Order::class),
            Argument::type(OrderTransitionLineItemStateAction::class),
            null
        )->willReturn([])->shouldBeCalledOnce();
        $manager->apply(
            Argument::type(Order::class),
            Argument::type('array')
        )->shouldBeCalledOnce();

        $itemStateTransitionHandler = new LineItemStateTransitionHandler($manager->reveal());

        $subject = $this->prophesize(ItemStateWrapper::class);
        $subject->getResource()->willReturn(Order::of())->shouldBeCalledOnce();
        $subject->getUpdateAction(Argument::is(false))->willReturn(OrderTransitionLineItemStateAction::of())->shouldBeCalledOnce();

        $transition = $this->prophesize(Transition::class);
        $transition->getTos()->willReturn([])->shouldBeCalledOnce();

        $event = $this->prophesize(Event::class);
        $event->getSubject()->willReturn($subject->reveal())->shouldBeCalledOnce();
        $event->getTransition()->willReturn($transition->reveal())->shouldBeCalledOnce();

        $itemStateTransitionHandler->handle($event->reveal());
    }
}
