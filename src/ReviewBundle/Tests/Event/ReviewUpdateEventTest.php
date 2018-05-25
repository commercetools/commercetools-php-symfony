<?php
/**
 *
 */

namespace Commercetools\Symfony\ReviewBundle\Tests\Event;

use Commercetools\Core\Model\Review\Review;
use Commercetools\Core\Request\Reviews\Command\ReviewSetCustomerAction;
use Commercetools\Core\Request\Reviews\Command\ReviewSetRatingAction;
use Commercetools\Symfony\ReviewBundle\Event\ReviewUpdateEvent;
use PHPUnit\Framework\TestCase;

class ReviewUpdateEventTest extends TestCase
{
    public function testReviewUpdateEvent()
    {
        $review = $this->prophesize(Review::class);
        $action = $this->prophesize(ReviewSetCustomerAction::class);
        $secondAction = $this->prophesize(ReviewSetRatingAction::class);

        $updateEvent = new ReviewUpdateEvent($review->reveal(), $action->reveal());

        $this->assertInstanceOf(Review::class, $updateEvent->getReview());
        $this->assertEquals([$action->reveal()], $updateEvent->getActions());

        $updateEvent->addAction($secondAction->reveal());

        $this->assertEquals([$action->reveal(), $secondAction->reveal()], $updateEvent->getActions());

        $updateEvent->setActions([$secondAction->reveal()]);

        $this->assertEquals([$secondAction->reveal()], $updateEvent->getActions());
    }
}
