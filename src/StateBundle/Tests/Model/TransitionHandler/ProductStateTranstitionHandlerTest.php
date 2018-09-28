<?php
/**
 *
 */

namespace Commercetools\Symfony\StateBundle\Tests\Model\TransitionHandler;


use Commercetools\Core\Model\Product\Product;
use Commercetools\Core\Request\Products\Command\ProductTransitionStateAction;
use Commercetools\Symfony\CatalogBundle\Manager\CatalogManager;
use Commercetools\Symfony\StateBundle\Model\TransitionHandler\ProductStateTransitionHandler;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\Workflow\Event\Event;
use Symfony\Component\Workflow\Transition;

class ProductStateTranstitionHandlerTest extends TestCase
{
    public function testHandle()
    {
        $manager = $this->prophesize(CatalogManager::class);
        $manager->dispatch(
            Argument::type(Product::class),
            Argument::type(ProductTransitionStateAction::class),
            null
        )->willReturn([])->shouldBeCalledOnce();
        $manager->apply(
            Argument::type(Product::class),
            Argument::type('array')
        )->shouldBeCalledOnce();

        $itemStateTransitionHandler = new ProductStateTransitionHandler($manager->reveal());

        $transition = $this->prophesize(Transition::class);
        $transition->getTos()->willReturn(['foo'])->shouldBeCalledOnce();

        $event = $this->prophesize(Event::class);
        $event->getSubject()->willReturn(Product::of())->shouldBeCalledOnce();
        $event->getTransition()->willReturn($transition->reveal())->shouldBeCalledOnce();

        $itemStateTransitionHandler->handle($event->reveal());
    }
}
