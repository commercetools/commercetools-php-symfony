<?php
/**
 *
 */

namespace Commercetools\Symfony\StateBundle\EventListener;


use Commercetools\Symfony\StateBundle\Model\TransitionHandler\SubjectHandler;
use Symfony\Component\Workflow\Event\Event;

class TransitionSubscriber
{
    private $subjectHandler;

    public function __construct(SubjectHandler $subjectHandler)
    {
        $this->subjectHandler = $subjectHandler;
    }

    public function transitionSubject(Event $event)
    {
        $this->subjectHandler->handle($event);
    }
}
