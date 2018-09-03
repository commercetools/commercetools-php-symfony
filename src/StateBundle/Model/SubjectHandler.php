<?php
/**
 *
 */

namespace Commercetools\Symfony\StateBundle\Model;


use Symfony\Component\Workflow\Event\Event;

interface SubjectHandler
{
    public function handle(Event $event);
}
