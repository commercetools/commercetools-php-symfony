<?php
/**
 */

namespace Commercetools\Symfony\ReviewBundle\Event;

use Commercetools\Core\Model\Review\Review;
use Commercetools\Core\Request\AbstractAction;
use Symfony\Component\EventDispatcher\Event;

class ReviewPostUpdateEvent extends Event
{
    /**
     * @var Review
     */
    private $review;

    /**
     * @var AbstractAction[]
     */
    private $actions;

    public function __construct(Review $review, array $actions)
    {
        $this->review = $review;
        $this->actions = $actions;
    }

    /**
     * @return AbstractAction[]
     */
    public function getActions()
    {
        return $this->actions;
    }

    /**
     * @return Review
     */
    public function getReview()
    {
        return $this->review;
    }

    /**
     * @param Review $review
     */
    public function setReview(Review $review)
    {
        $this->review = $review;
    }
}
