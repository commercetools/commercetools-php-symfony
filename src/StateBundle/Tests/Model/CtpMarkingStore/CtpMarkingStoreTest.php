<?php
/**
 *
 */

namespace Commercetools\Symfony\StateBundle\Tests\Model;


use Commercetools\Core\Model\Order\Order;
use Commercetools\Core\Model\State\StateReference;
use Commercetools\Symfony\StateBundle\Cache\StateKeyResolver;
use Commercetools\Symfony\StateBundle\Model\CtpMarkingStore\CtpMarkingStore;
use Commercetools\Symfony\StateBundle\Model\StateWrapper;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\Workflow\Marking;

class CtpMarkingStoreTest extends TestCase
{
    public function testGetMarkingForInitial()
    {
        $stateKeyResolver = $this->prophesize(StateKeyResolver::class);
        $ctpMarkingStore = new CtpMarkingStore($stateKeyResolver->reveal(), 'initial-state');

        $subject = $this->prophesize(Order::class);
        $subject->getState()->shouldBeCalledOnce();

        $marking = $ctpMarkingStore->getMarking($subject->reveal());

        $this->assertInstanceOf(Marking::class, $marking);
        $this->assertEquals(['initial-state' => 1], $marking->getPlaces());
    }

    public function testGetMarkingForResource()
    {
        $stateKeyResolver = $this->prophesize(StateKeyResolver::class);
        $stateKeyResolver->resolve(Argument::type(StateReference::class))->will(function($args){
            $state = $args[0];
            return $state->getKey();
        })->shouldBeCalledOnce();
        $ctpMarkingStore = new CtpMarkingStore($stateKeyResolver->reveal(), null);

        $subject = $this->prophesize(Order::class);
        $stateReference = StateReference::ofKey('foo');
        $subject->getState()->willReturn($stateReference)->shouldBeCalledOnce();

        $marking = $ctpMarkingStore->getMarking($subject->reveal());

        $this->assertInstanceOf(Marking::class, $marking);
        $this->assertEquals(['foo' => 1], $marking->getPlaces());
    }

    public function testGetMarkingForStateWrapper()
    {
        $stateKeyResolver = $this->prophesize(StateKeyResolver::class);
        $stateKeyResolver->resolve(Argument::type(StateReference::class))->will(function($args){
            $state = $args[0];
            return $state->getKey();
        })->shouldBeCalledOnce();
        $ctpMarkingStore = new CtpMarkingStore($stateKeyResolver->reveal(), null);

        $subject = $this->prophesize(StateWrapper::class);
        $stateReference = StateReference::ofKey('foo');
        $subject->getStateReference()->willReturn($stateReference)->shouldBeCalledOnce();

        $marking = $ctpMarkingStore->getMarking($subject->reveal());

        $this->assertInstanceOf(Marking::class, $marking);
        $this->assertEquals(['foo' => 1], $marking->getPlaces());
    }

    public function testGetStateReferenceOfInitial()
    {
        $stateKeyResolver = $this->prophesize(StateKeyResolver::class);
        $stateKeyResolver->resolveKey(Argument::is('initial-state'))->willReturn('state-1')->shouldBeCalledOnce();

        $ctpMarkingStore = new CtpMarkingStore($stateKeyResolver->reveal(), 'initial-state');

        $stateReference = $ctpMarkingStore->getStateReferenceOfInitial();
        $this->assertInstanceOf(StateReference::class, $stateReference);
        $this->assertEquals('state-1', $stateReference->getId());
    }
}
