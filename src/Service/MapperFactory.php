<?php
/**
 * @author @jayS-de <jens.schulze@commercetools.de>
 */

namespace Commercetools\Symfony\CtpBundle\Service;

use Commercetools\Core\Model\JsonObjectMapper;

class MapperFactory
{
    private $factory;

    public function __construct(ContextFactory $factory)
    {
        $this->factory = $factory;
    }

    public function build($locale, $class)
    {
        return JsonObjectMapper::of($class, $this->factory->build($locale));
    }
}
