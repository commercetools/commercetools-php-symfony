<?php
/**
 *
 */

namespace Commercetools\Symfony\StateBundle\Model\TransitionHandler;

use Symfony\Component\Workflow\Event\Event;

interface SubjectHandler
{
    public function handle(Event $event);
}
