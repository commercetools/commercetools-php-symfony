<?php
/**
 *
 */

namespace Commercetools\Symfony\ExampleBundle\Controller;


use Commercetools\Core\Client;
use Commercetools\Core\Model\Customer\CustomerReference;
use Commercetools\Core\Model\Product\ProductReference;
use Commercetools\Symfony\CtpBundle\Model\QueryParams;
use Commercetools\Symfony\ExampleBundle\Model\Form\Type\AddReviewType;
use Commercetools\Symfony\ReviewBundle\Manager\ReviewManager;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;

class ReviewController extends Controller
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var ReviewManager
     */
    private $manager;

    /**
     * ReviewController constructor.
     * @param Client $client
     * @param ReviewManager $manager
     */
    public function __construct(Client $client, ReviewManager $manager)
    {
        $this->client = $client;
        $this->manager = $manager;
    }

    public function showReviewsForProductAction(Request $request, $productId)
    {
        $params = new QueryParams();
        $params->add('expand', 'customer');
        $reviews = $this->manager->getByProductId($request->getLocale(), $productId, $params);

        $reviewForm = $this->createForm(AddReviewType::class);

        return $this->render('ExampleBundle:review:index.html.twig', [
            'reviews' => $reviews,
            'reviewForm' => $reviewForm->createView(),
            'submitUrl' => $this->generateUrl('_ctp_example_review_create', ['productId' => $productId])
        ]);
    }

    public function createReviewForProductAction(Request $request, UserInterface $user, $productId)
    {
        $form = $this->createForm(AddReviewType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $text = $form->get('text')->getData();
            $rating = $form->get('rating')->getData();

            $customerReference = CustomerReference::ofId($user->getId());
            $productReference = ProductReference::ofId($productId);

            $this->manager->createForProduct($request->getLocale(), $productReference, $customerReference, $text, $rating);

            return new JsonResponse([
                'success' => true,
                'fetchReviewsUrl' => $this->generateUrl('_ctp_example_review_show', ['productId' => $productId])
            ]);
        }

        return new JsonResponse(array('success' => false));;
    }

}
