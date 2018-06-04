<?php
/**
 */

namespace Commercetools\Symfony\ReviewBundle\Model;


use Commercetools\Core\Builder\Update\ReviewsActionBuilder;
use Commercetools\Core\Model\Review\Review;
use Commercetools\Core\Request\AbstractAction;
use Commercetools\Symfony\ReviewBundle\Manager\ReviewManager;

class ReviewUpdateBuilder extends ReviewsActionBuilder
{
    /**
     * @var ReviewManager
     */
    private $manager;

    /**
     * @var Review
     */
    private $review;

    /**
     * ReviewUpdate constructor.
     * @param ReviewManager $manager
     * @param Review $review
     */
    public function __construct(Review $review, ReviewManager $manager)
    {
        $this->manager = $manager;
        $this->review = $review;
    }
}
