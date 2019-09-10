<?php
/**
 */

namespace Commercetools\Symfony\ReviewBundle\Event;

use Commercetools\Core\Model\Review\Review;
use Commercetools\Core\Request\AbstractAction;
use Symfony\Contracts\EventDispatcher\Event;

class ReviewUpdateEvent extends Event
{
    /**
     * @var Review
     */
    private $review;

    /**
     * @var AbstractAction[]
     */
    private $actions;

    public function __construct(Review $review, AbstractAction $action)
    {
        $this->review = $review;
        $this->actions = [$action];
    }

    /**
     * @return Review
     */
    public function getReview()
    {
        return $this->review;
    }

    /**
     * @return AbstractAction[]
     */
    public function getActions()
    {
        return $this->actions;
    }

    /**
     * @param array $actions
     */
    public function setActions(array $actions)
    {
        $this->actions = $actions;
    }

    /**
     * @param AbstractAction $action
     */
    public function addAction(AbstractAction $action)
    {
        $this->actions[] = $action;
    }
}
