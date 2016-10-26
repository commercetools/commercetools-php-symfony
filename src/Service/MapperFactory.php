<?php
/**
 * @author @jayS-de <jens.schulze@commercetools.de>
 */

namespace Commercetools\Symfony\CtpBundle\Service;

use Commercetools\Core\Model\JsonObjectMapper;
use Commercetools\Core\Model\MapperInterface;

class MapperFactory
{
    private $factory;

    public function __construct(ContextFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * @param $locale
     * @return MapperInterface
     */
    public function build($locale)
    {
        return JsonObjectMapper::of($this->factory->build($locale));
    }
}
