<?php
/**
 *
 */

namespace Commercetools\Symfony\ReviewBundle\Tests\Event;

use Commercetools\Core\Model\Review\Review;
use Commercetools\Core\Request\Reviews\Command\ReviewSetCustomerAction;
use Commercetools\Symfony\ReviewBundle\Event\ReviewPostUpdateEvent;
use PHPUnit\Framework\TestCase;

class ReviewPostUpdateEventTest extends TestCase
{
    public function testReviewPostUpdateEvent()
    {
        $review = $this->prophesize(Review::class);
        $action = $this->prophesize(ReviewSetCustomerAction::class);
        $secondReview = $this->prophesize(Review::class);

        $postUpdateEvent = new ReviewPostUpdateEvent($review->reveal(), [$action->reveal()]);
        $postUpdateEvent->setReview($secondReview->reveal());

        $this->assertNotSame($review->reveal(),$secondReview->reveal());
        $this->assertSame($secondReview->reveal(), $postUpdateEvent->getReview());
        $this->assertNotSame($review->reveal(), $postUpdateEvent->getReview());

        $this->assertEquals([$action->reveal()], $postUpdateEvent->getActions());
    }
}
