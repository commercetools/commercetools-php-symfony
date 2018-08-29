<?php
/**
 *
 */

namespace Commercetools\Symfony\StateBundle\Model;


use Commercetools\Core\Model\Order\Order;
use Commercetools\Core\Model\State\StateReference;
use Commercetools\Symfony\StateBundle\Model\Repository\StateRepository;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Workflow\Marking;
use Symfony\Component\Workflow\MarkingStore\MarkingStoreInterface;

class CtpMarkingStore implements MarkingStoreInterface
{
    private $stateRepository;
    private $cache;
    protected $initialState;

    /**
     * CtpMarkingStore constructor.
     */
    public function __construct(StateRepository $stateRepository, CacheItemPoolInterface $cache, $initialState)
    {
        $this->initialState = $initialState;
        $this->cache = $cache;
        $this->stateRepository = $stateRepository;
    }

    protected function resolveFromId($id)
    {
        $item = $this->cache->getItem($id);
        if ($item->isHit()) {
            return $item->get();
        }

        $this->fillCache();

        $item = $this->cache->getItem($id);
        if ($item->isHit()) {
            return $item->get();
        }

        return $this->initialState;
    }

    private function fillCache()
    {
        $states = $this->stateRepository->getStates();

        foreach ($states as $state) {
            $item = $this->cache->getItem($state->getId());
            $item->set($state->getKey());
            $this->cache->save($item);
        }
    }

    /**
     * @param Order $subject
     * @return mixed|null|Marking
     */
    public function getMarking($subject)
    {
        $markingName = $this->initialState;

        $state = $this->getStateReference($subject);

        if ($state instanceof StateReference) {
            $markingName = $this->resolveFromId($state->getId());
        }

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
