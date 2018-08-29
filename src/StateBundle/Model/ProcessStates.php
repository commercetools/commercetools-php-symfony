<?php
/**
 *
 */

namespace Commercetools\Symfony\StateBundle\Model;


use Commercetools\Core\Model\State\State;
use Commercetools\Core\Model\State\StateCollection;

class ProcessStates
{
    public function parse(StateCollection $states, $type = 'workflow')
    {
        $workflow = [];
        $initArray = [
            'type' => $type,
            'audit_trail' => true,
            'marking_store' => [
                'service' => null
            ],
            'supports' => [],
            'initial_place' => '',
            'places' => [],
            'transitions' => []
        ];

        foreach ($states as $state) {
            $workflow[$state->getType()] = $workflow[$state->getType()] ?? $initArray;

            $workflow[$state->getType()]['marking_store']['service'] = CtpMarkingStore::class . $state->getType();
            $workflow[$state->getType()]['supports'] = StateType::TYPES[$state->getType()];

            $workflow[$state->getType()]['initial_place'] = $state->getInitial() ? $state->getKey() :
                $workflow[$state->getType()]['initial_place'];

            $workflow[$state->getType()]['places'][] = $state->getKey();

            $workflow[$state->getType()]['transitions'] = $this->filterTransitions(
                $type, $state, $states, $workflow[$state->getType()]['transitions']
            );
        }

        $framework = [
            'framework' => [
                'workflows' => $workflow
            ]
        ];

        return $framework;
    }

    private function filterTransitions($type, $state, $states, $allTransitions)
    {
        return array_filter(array_merge(
            $allTransitions, $this->getTransitions($type, $state, $states, $allTransitions)
        ));
    }

    private function getTransitions($type, State $state, StateCollection $states, $allTransitions)
    {
        $transitions = $state->getTransitions();

        if (is_null($transitions)) {
            return [];
        }

        $result = [];
        foreach ($transitions->toArray() as $transition) {
            $nextState = $this->findNextState($transition['id'], $states->toArray());
            if ($type === 'workflow') {
                $name = $state->getKey() . 'To' . ucfirst($nextState);
                $from = $state->getKey();
            } else {
                $name = 'to' . ucfirst($nextState);
                $from = $this->getFromForStateMachine($name, $state->getKey(), $allTransitions);
            }

            $result[$name] = [
                'from' => $from,
                'to' => $nextState
            ];
        }

        return $result;
    }

    private function getFromForStateMachine($name, $currentState, $allTransitions)
    {
        if (isset($allTransitions[$name])) {
            if (!is_array($allTransitions[$name]['from'])) {
                $allTransitions[$name]['from'] = [$allTransitions[$name]['from']];
            }
            return array_merge($allTransitions[$name]['from'], [$currentState]);
        } else {
            return $currentState;
        }
    }

    private function findNextState($stateId, array $states)
    {
        foreach ($states as $state) {
            if ($state['id'] === $stateId) {
                return $state['key'];
            }
        }
    }

    public static function of()
    {
        return new static();
    }
}
