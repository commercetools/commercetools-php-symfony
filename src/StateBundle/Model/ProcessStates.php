<?php
/**
 *
 */

namespace Commercetools\Symfony\StateBundle\Model;


use Commercetools\Core\Model\State\State;
use Commercetools\Core\Model\State\StateCollection;

class ProcessStates
{
    public function parse(StateCollection $states)
    {
        $workflow = [];

        foreach ($states as $state) {
            $workflow[$state->getType()]['places'][] = $state->getKey();
            $workflow[$state->getType()]['transitions'] = array_filter(array_merge(
                $workflow[$state->getType()]['transitions'] ?? [], $this->getTransitionsForState($state, $states)
            ));
        }

        return $workflow;
    }

    private  function getTransitionsForState(State $state, StateCollection $states)
    {
        $transitions = $state->getTransitions();

        if (is_null($transitions)) {
            return [];
        }

        $result = [];
        foreach ($transitions->toArray() as $transition) {
            $next = $this->findNextState($transition['id'], $states->toArray());
            $name = $state->getKey() . 'To' . ucfirst($next['key']);
            $result[$name] = [
                'from' => $state->getKey(),
                'to' => $next['key']
            ];
        }

        return $result;
    }

    private function findNextState($stateId, array $states)
    {
        foreach ($states as $state) {
            if ($state['id'] === $stateId) {
                return $state;
            }
        }
    }

    public function formatYamlFile(array $states)
    {
        $output = <<<OUTPUT
# config/packages/workflow.yaml
framework:
    workflows:

OUTPUT;
        foreach ($states as $key => $state) {
            $output .= <<<OUTPUT
        {$key}:
            type: 'workflow'
            audit_trail: true
            marking_store:
                type: 'single_state'
                arguments:
                    - {{ user-defined-argument }}
            supports:
                - {{ user-defined-class }}
            initial_place: {{ user-defined-initial-place }}
            places:

OUTPUT;
            foreach ($state['places'] as $place) {
                $output .= <<<OUTPUT
                - {$place}

OUTPUT;
            }

            $output .= <<<OUTPUT
            transitions:

OUTPUT;

            foreach ($state['transitions'] as $transitionName => $fromTo) {
                $output .= <<<OUTPUT
                {$transitionName}:
                    from: {$fromTo['from']}
                    to: {$fromTo['to']}

OUTPUT;
            }
        }

        return $output;
    }

    public static function of()
    {
        return new static();
    }
}
