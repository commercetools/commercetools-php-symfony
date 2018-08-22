<?php
/**
 *
 */

namespace Commercetools\Symfony\StateBundle\Model;


use Commercetools\Core\Model\Common\Resource;
use Commercetools\Core\Model\Order\ItemState;
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
    private $initialState;

    /**
     * CtpMarkingStore constructor.
     */
    public function __construct(StateRepository $stateRepository, CacheItemPoolInterface $cache, $initialState)
    {
        $this->initialState = $initialState;
        $this->cache = $cache;
        $this->stateRepository = $stateRepository;
    }

    private function resolveFromId($id)
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

        if ($subject instanceof Resource || $subject instanceof ItemState) {
            $state = $subject->getState();

            if ($state instanceof StateReference) {
                $markingName = $this->resolveFromId($state->getId());
            }

            return new Marking([$markingName => 1]);
        }
    }

    public function setMarking($subject, Marking $marking)
    {
        // TODO: Implement setMarking() method.
    }
}
