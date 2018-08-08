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

    public static function of()
    {
        return new static();
    }
}
