<?php
/**
 *
 */

namespace Commercetools\Symfony\StateBundle\Model;


use Commercetools\Core\Model\Order\Order;
use Commercetools\Core\Model\State\StateReference;
use Commercetools\Symfony\StateBundle\Cache\StateKeyResolver;
use Symfony\Component\Workflow\Marking;
use Symfony\Component\Workflow\MarkingStore\MarkingStoreInterface;

class CtpMarkingStore implements MarkingStoreInterface
{
    protected $stateKeyResolver;
    protected $initialState;

    /**
     * CtpMarkingStore constructor.
     */
    public function __construct(StateKeyResolver $stateKeyResolver, $initialState)
    {
        $this->stateKeyResolver = $stateKeyResolver;
        $this->initialState = $initialState;
    }

    /**
     * @param Order $subject
     * @return mixed|null|Marking
     */
    public function getMarking($subject)
    {
        $state = $this->getStateReference($subject);

        if ($state instanceof StateReference) {
            $markingName = $this->stateKeyResolver->resolve($state);
        }

        $markingName = $markingName ?? $this->initialState;

        return new Marking([$markingName => 1]);
    }

    protected function getStateReference($subject)
    {
        if ($subject instanceof ItemStateWrapper) {
            return $subject->getStateReference();
        }

        return $subject->getState();
    }

    public function setMarking($subject, Marking $marking)
    {
    }

}
