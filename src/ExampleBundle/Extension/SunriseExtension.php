<?php
/**
 *
 */

namespace Commercetools\Symfony\ExampleBundle\Extension;

use Commercetools\Symfony\CatalogBundle\Manager\CatalogManager;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class SunriseExtension extends AbstractExtension
{
    private $catalogManager;

    public function __construct(CatalogManager $catalogManager)
    {
        $this->catalogManager = $catalogManager;
    }

    public function getFunctions()
    {
        return array(
            new TwigFunction('getCategoryById', [$this, 'getCategoryById']),
        );
    }

    public function getCategoryById($locale, $id)
    {
        $categories = $this->catalogManager->getCategories($locale);

        return $categories->getById($id);
    }
}
