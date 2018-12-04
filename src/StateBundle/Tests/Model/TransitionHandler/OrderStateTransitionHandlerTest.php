<?php
/**
 *
 */

namespace Commercetools\Symfony\StateBundle\Tests\Model\TransitionHandler;


use Commercetools\Core\Model\Order\Order;
use Commercetools\Core\Request\Orders\Command\OrderTransitionStateAction;
use Commercetools\Symfony\CartBundle\Manager\OrderManager;
use Commercetools\Symfony\StateBundle\Model\TransitionHandler\OrderStateTransitionHandler;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\Workflow\Event\Event;
use Symfony\Component\Workflow\Transition;

class OrderStateTransitionHandlerTest extends TestCase
{
    public function testHandle()
    {
        $manager = $this->prophesize(OrderManager::class);
        $manager->dispatch(
            Argument::type(Order::class),
            Argument::type(OrderTransitionStateAction::class),
            null
        )->willReturn([])->shouldBeCalledOnce();
        $manager->apply(
            Argument::type(Order::class),
            Argument::type('array')
        )->shouldBeCalledOnce();

        $itemStateTransitionHandler = new OrderStateTransitionHandler($manager->reveal());

        $transition = $this->prophesize(Transition::class);
        $transition->getTos()->willReturn(['foo'])->shouldBeCalledOnce();

        $event = $this->prophesize(Event::class);
        $event->getSubject()->willReturn(Order::of())->shouldBeCalledOnce();
        $event->getTransition()->willReturn($transition->reveal())->shouldBeCalledOnce();

        $itemStateTransitionHandler->handle($event->reveal());
    }
}
