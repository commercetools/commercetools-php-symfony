<?php
/**
 *
 */

namespace Commercetools\Symfony\StateBundle\Tests\Model\TransitionHandler;

use Commercetools\Core\Model\Payment\Payment;
use Commercetools\Core\Request\Payments\Command\PaymentTransitionStateAction;
use Commercetools\Symfony\CartBundle\Manager\PaymentManager;
use Commercetools\Symfony\StateBundle\Model\TransitionHandler\PaymentStateTransitionHandler;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\Workflow\Event\Event;
use Symfony\Component\Workflow\Transition;

class PaymentStateTransitionHandlerTest extends TestCase
{
    public function testHandle()
    {
        $manager = $this->prophesize(PaymentManager::class);
        $manager->dispatch(
            Argument::type(Payment::class),
            Argument::type(PaymentTransitionStateAction::class),
            null
        )->willReturn([])->shouldBeCalledOnce();
        $manager->apply(
            Argument::type(Payment::class),
            Argument::type('array')
        )->shouldBeCalledOnce();

        $itemStateTransitionHandler = new PaymentStateTransitionHandler($manager->reveal());

        $transition = $this->prophesize(Transition::class);
        $transition->getTos()->willReturn(['foo'])->shouldBeCalledOnce();

        $event = $this->prophesize(Event::class);
        $event->getSubject()->willReturn(Payment::of())->shouldBeCalledOnce();
        $event->getTransition()->willReturn($transition->reveal())->shouldBeCalledOnce();

        $itemStateTransitionHandler->handle($event->reveal());
    }
}
