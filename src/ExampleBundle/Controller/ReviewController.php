<?php
/**
 *
 */

namespace Commercetools\Symfony\ExampleBundle\Controller;

use Commercetools\Core\Model\Customer\CustomerReference;
use Commercetools\Core\Model\Product\ProductReference;
use Commercetools\Core\Model\Review\Review;
use Commercetools\Symfony\CtpBundle\Model\QueryParams;
use Commercetools\Symfony\ExampleBundle\Model\Form\Type\AddReviewType;
use Commercetools\Symfony\ReviewBundle\Manager\ReviewManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Workflow\Exception\InvalidArgumentException;
use Symfony\Component\Workflow\Registry;

class ReviewController extends AbstractController
{
    /**
     * @var ReviewManager
     */
    private $manager;

    /**
     * @var Registry
     */
    private $workflows;

    /**
     * ReviewController constructor.
     * @param ReviewManager $manager
     * @param Registry $workflows
     */
    public function __construct(ReviewManager $manager, Registry $workflows)
    {
        $this->manager = $manager;
        $this->workflows = $workflows;
    }

    /**
     * @param Request $request
     * @param $productId
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showReviewsForProductAction(Request $request, $productId)
    {
        $params = new QueryParams();
        $params->add('expand', 'customer');
        $params->add('sort', 'createdAt desc');
        $reviews = $this->manager->getByProductId($request->getLocale(), $productId, $params);

        $reviewForm = $this->createForm(AddReviewType::class);

        return $this->render('@Example/partials/catalog/pdp/reviews.html.twig', [
            'reviews' => $reviews,
            'reviewForm' => $reviewForm->createView(),
            'submitUrl' => $this->generateUrl('_ctp_example_review_create', ['productId' => $productId])
        ]);
    }

    /**
     * @param Request $request
     * @param $productId
     * @param UserInterface|null $user
     * @return JsonResponse
     */
    public function createReviewForProductAction(Request $request, $productId, UserInterface $user = null)
    {
        $form = $this->createForm(AddReviewType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $text = $form->get('text')->getData();
            $rating = $form->get('rating')->getData();

            $customerReference = null;
            if (!is_null($user)) {
                $customerReference = CustomerReference::ofId($user->getId());
            }
            $productReference = ProductReference::ofId($productId);

            $this->manager->createForProduct($request->getLocale(), $productReference, $customerReference, $text, $rating);

            return new JsonResponse([
                'success' => true,
                'fetchReviewsUrl' => $this->generateUrl('_ctp_example_review_show', ['productId' => $productId])
            ]);
        }

        return new JsonResponse(array('success' => false));
    }

    /**
     * @param Request $request
     * @param $reviewId
     * @param UserInterface|null $user
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function updateReviewAction(Request $request, $reviewId, UserInterface $user = null)
    {
        if (is_null($user)) {
            $this->addFlash('error', 'Do not allow anonymous reviews for now');
            return $this->render('@Example/index.html.twig');
        }

        $review = $this->manager->getReviewForUser($request->getLocale(), $user->getId(), $reviewId);

        $review = $review->current();

        if (!$review instanceof Review) {
            $this->addFlash('error', 'Cannot find review or not required permissions');
            return $this->render('@Example/index.html.twig');
        }

        try {
            $workflow = $this->workflows->get($review);
        } catch (InvalidArgumentException $e) {
            $this->addFlash('error', 'Cannot find proper workflow configuration. Action aborted');
            return $this->render('@Example/index.html.twig');
        }

        if (!$workflow->can($review, $request->get('toState'))) {
            $this->addFlash('error', 'Cannot perform this action');
            return $this->render('@Example/index.html.twig');
        }

        $workflow->apply($review, $request->get('toState'));
        return $this->redirect($this->generateUrl('_ctp_example_product_by_id', ['id' => $review->getTarget()->getid()]));
    }
}
