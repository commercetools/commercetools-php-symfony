<?php
/**
 *
 */

namespace Commercetools\Symfony\StateBundle\Model;


use Commercetools\Core\Model\Order\Order;
use Commercetools\Core\Model\State\StateReference;
use Commercetools\Symfony\StateBundle\Cache\StateCacheHelper;
use Symfony\Component\Workflow\Marking;
use Symfony\Component\Workflow\MarkingStore\MarkingStoreInterface;

class CtpMarkingStore implements MarkingStoreInterface
{
    protected $cacheHelper;
    protected $initialState;

    /**
     * CtpMarkingStore constructor.
     */
    public function __construct(StateCacheHelper $cacheHelper, $initialState)
    {
        $this->cacheHelper = $cacheHelper;
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
            $markingName = $this->cacheHelper->resolveFromId($state->getId());
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
