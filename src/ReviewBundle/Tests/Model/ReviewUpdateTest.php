<?php
declare(strict_types=1);

namespace Commercetools\Symfony\ReviewBundle\Tests\Model;

use Commercetools\Core\Model\Review\Review;
use Commercetools\Core\Request\Reviews\Command\ReviewSetAuthorNameAction;
use Commercetools\Core\Request\Reviews\Command\ReviewSetCustomerAction;
use Commercetools\Core\Request\Reviews\Command\ReviewSetCustomFieldAction;
use Commercetools\Core\Request\Reviews\Command\ReviewSetCustomTypeAction;
use Commercetools\Core\Request\Reviews\Command\ReviewSetKeyAction;
use Commercetools\Core\Request\Reviews\Command\ReviewSetLocaleAction;
use Commercetools\Core\Request\Reviews\Command\ReviewSetRatingAction;
use Commercetools\Core\Request\Reviews\Command\ReviewSetTargetAction;
use Commercetools\Core\Request\Reviews\Command\ReviewSetTextAction;
use Commercetools\Core\Request\Reviews\Command\ReviewSetTitleAction;
use Commercetools\Core\Request\Reviews\Command\ReviewTransitionStateAction;
use Commercetools\Symfony\ReviewBundle\Manager\ReviewManager;
use Commercetools\Symfony\ReviewBundle\Model\ReviewUpdateBuilder;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

class ReviewUpdateTest extends TestCase
{
    public function getActionProvider()
    {
        return [
            ['setAuthorName', ReviewSetAuthorNameAction::class],
            ['setCustomField', ReviewSetCustomFieldAction::class],
            ['setCustomType', ReviewSetCustomTypeAction::class],
            ['setCustomer', ReviewSetCustomerAction::class],
            ['setKey', ReviewSetKeyAction::class],
            ['setLocale', ReviewSetLocaleAction::class],
            ['setRating', ReviewSetRatingAction::class],
            ['setTarget', ReviewSetTargetAction::class],
            ['setText', ReviewSetTextAction::class],
            ['setTitle', ReviewSetTitleAction::class],
            ['transitionState', ReviewTransitionStateAction::class],
        ];
    }

    /**
     * @dataProvider getActionProvider
     */
    public function testUpdateMethods($updateMethod, $actionClass)
    {
        $review = $this->prophesize(Review::class);

        $manager = $this->prophesize(ReviewManager::class);
        $manager->apply(
            $review,
            Argument::allOf(
                Argument::containing(Argument::type($actionClass))
            )
        )->shouldBeCalledTimes(1);

        $manager->dispatch(
            $review,
            Argument::type($actionClass),
            Argument::is(null)
        )->will(function ($args) { return [$args[1]]; })->shouldBeCalledTimes(1);

        $update = new ReviewUpdateBuilder($review->reveal(), $manager->reveal());

        $action = $actionClass::of();
        $update->$updateMethod($action);

        $update->flush();
    }

    /**
     * @dataProvider getActionProvider
     */
    public function testUpdateMethodsCallback($updateMethod, $actionClass)
    {
        $review = $this->prophesize(Review::class);

        $manager = $this->prophesize(ReviewManager::class);
        $manager->apply(
            $review,
            Argument::allOf(
                Argument::containing(Argument::type($actionClass))
            )
        )->shouldBeCalledTimes(1);

        $manager->dispatch(
            $review,
            Argument::type($actionClass),
            Argument::is(null)
        )->will(function ($args) { return [$args[1]]; })->shouldBeCalledTimes(1);

        $update = new ReviewUpdateBuilder($review->reveal(), $manager->reveal());

        $callback = function ($action) use ($actionClass) {
            static::assertInstanceOf($actionClass, $action);
            return $action;
        };
        $update->$updateMethod($callback);

        $update->flush();
    }
}
