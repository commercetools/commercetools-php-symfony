<?php
/**
 */

namespace Commercetools\Symfony\ExampleBundle\Controller;

use Commercetools\Core\Client;
use Commercetools\Symfony\CatalogBundle\Manager\CatalogManager;
use Commercetools\Symfony\CtpBundle\Model\QueryParams;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

class SunriseController extends AbstractController
{
    const CSRF_TOKEN_NAME = 'csrfToken';

    /**
     * @var Client
     */
    private $client;

    /**
     * @var CatalogManager
     */
    private $catalogManager;

    /**
     * CartController constructor.
     * @param Client $client
     * @param CatalogManager $catalogManager
     */
    public function __construct(Client $client, CatalogManager $catalogManager)
    {
        $this->client = $client;
        $this->catalogManager = $catalogManager;
    }

    public function getNavMenuAction(Request $request, $sort = 'id asc')
    {
        $params = QueryParams::of()->add('sort', $sort);

        $categories = $this->catalogManager->getCategories($request->getLocale(), $params);

        return $this->render('@Example/partials/common/nav-menu.html.twig', [
            'navMenu' => [
                'new' => true,
                'categories' => $categories
            ]
        ]);
    }
}
