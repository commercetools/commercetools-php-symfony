<?php
/**
 *
 */

namespace Commercetools\Symfony\StateBundle\Tests\Model;

use Commercetools\Core\Model\State\State;
use Commercetools\Core\Model\State\StateCollection;
use Commercetools\Core\Model\State\StateReference;
use Commercetools\Core\Model\State\StateReferenceCollection;
use Commercetools\Symfony\StateBundle\Model\ProcessStates;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

class ProcessStatesTest extends TestCase
{
    private $stateCollection;

    public function setUp()
    {
        $this->stateCollection = StateCollection::of()
            ->add(State::of()
                ->setType('OrderState')
                ->setKey('created')
                ->setInitial(true)
                ->setId('foo')
                ->setTransitions(
                    StateReferenceCollection::of()
                        ->add(StateReference::ofId('123'))
                        ->add(StateReference::ofId('456'))
                ))
            ->add(State::of()
                ->setType('OrderState')
                ->setKey('canceled')
                ->setId('123'))
            ->add(State::of()
                ->setType('OrderState')
                ->setKey('shipped')
                ->setId('456'))->add(State::of()
                ->setType('OrderState')
                ->setKey('completed')
                ->setTransitions(
                    StateReferenceCollection::of()
                        ->add(StateReference::ofId('123'))
                ));
    }

    public function testParseAsStateMachine()
    {
        $expected = 'framework:
    workflows:
        OrderState:
            type: state_machine
            audit_trail: true
            marking_store:
                service: Commercetools\Symfony\StateBundle\Model\CtpMarkingStore\CtpMarkingStoreOrderState
            supports:
                - Commercetools\Core\Model\Order\Order
            initial_place: created
            places:
                - created
                - canceled
                - shipped
                - completed
            transitions:
                toCanceled:
                    from:
                        - created
                        - completed
                    to: canceled
                toShipped:
                    from: created
                    to: shipped
';

        $helper = ProcessStates::of();
        $parsed = $helper->parse($this->stateCollection, 'state_machine');

        $yaml = Yaml::dump($parsed, 100, 4);
        $this->assertSame($expected, $yaml);
    }

    public function testParseAsWorkflow()
    {
        $expected = 'framework:
    workflows:
        OrderState:
            type: workflow
            audit_trail: true
            marking_store:
                service: Commercetools\Symfony\StateBundle\Model\CtpMarkingStore\CtpMarkingStoreOrderState
            supports:
                - Commercetools\Core\Model\Order\Order
            initial_place: created
            places:
                - created
                - canceled
                - shipped
                - completed
            transitions:
                createdToCanceled:
                    from: created
                    to: canceled
                createdToShipped:
                    from: created
                    to: shipped
                completedToCanceled:
                    from: completed
                    to: canceled
';

        $helper = ProcessStates::of();
        $parsed = $helper->parse($this->stateCollection, 'workflow');

        $yaml = Yaml::dump($parsed, 100, 4);
        $this->assertSame($expected, $yaml);
    }
}
