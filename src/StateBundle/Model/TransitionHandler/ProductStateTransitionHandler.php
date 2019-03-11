<?php
/**
 *
 */

namespace Commercetools\Symfony\StateBundle\Model\TransitionHandler;

use Commercetools\Core\Model\State\StateReference;
use Commercetools\Core\Request\Products\Command\ProductTransitionStateAction;
use Commercetools\Symfony\CatalogBundle\Model\ProductUpdateBuilder;
use Commercetools\Symfony\CatalogBundle\Manager\CatalogManager;
use Symfony\Component\Workflow\Event\Event;

class ProductStateTransitionHandler implements SubjectHandler
{
    private $manager;

    public function __construct(CatalogManager $manager)
    {
        $this->manager = $manager;
    }

    public function handle(Event $event)
    {
        $orderBuilder = new ProductUpdateBuilder($event->getSubject(), $this->manager);
        $orderBuilder->addAction(
            ProductTransitionStateAction::ofState(
                StateReference::ofKey(current($event->getTransition()->getTos()))
            )
        );

        $orderBuilder->flush();
    }
}
