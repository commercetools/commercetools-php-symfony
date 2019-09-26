<?php
/**
 */

namespace Commercetools\Symfony\ExampleBundle\Model\Form\Type;

use Commercetools\Symfony\ExampleBundle\Entity\ProductEntity;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;

class AddToCartType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $quantityChoices = array(1,2,3,4,5,6,7,8,9,10);
        $quantityChoices = array_combine($quantityChoices, $quantityChoices);

        /** @var ProductEntity $productEntity */
        $productEntity = $options['data'];

        $builder
            ->add('productId', HiddenType::class)
            ->add('variantId', HiddenType::class);

        if (!empty($productEntity->getAllVariants())) {
            $builder->add('variantId', ChoiceType::class, [
                'choices' => $productEntity->getAllVariants(),
                'label' => "Select the variant",
                'attr' => ['class' => 'form']
            ]);
        }

        $builder
            ->add('quantity', ChoiceType::class, [
                'choices' => $quantityChoices,
                'label' => "Select the amount of items",
                'attr' => ['class' => 'form']
            ])
            ->add('slug', HiddenType::class)
            ->add('addToCart', SubmitType::class, [
                'label' => 'Add to cart',
                'attr' => ['class' => 'add-to-bag-btn text-center']
            ])
        ;
    }
}
