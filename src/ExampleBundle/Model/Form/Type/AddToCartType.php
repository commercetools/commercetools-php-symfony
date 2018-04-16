<?php
/**
 * @author: Ylambers <yaron.lambers@commercetools.de>
 */

namespace Commercetools\Symfony\ExampleBundle\Model\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class AddToCartType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $quantityChoices = array(1,2,3,4,5,6,7,8,9,10);
        $quantityChoices = array_combine($quantityChoices, $quantityChoices);

        $builder
            ->add('productId', HiddenType::class);

        if (isset($options['data']['variantIdText']) && $options['data']['variantIdText'] === true) {
            $builder->add(
                'variantId',
                TextType::class
            );
        } else {
            $variantChoices = (isset($options['data']['variant_choices']) ? $options['data']['variant_choices'] : []);
            $builder->add(
                'variantId',
                ChoiceType::class,
                [
                    'choices' => $variantChoices,
                    'label' => "Select the variant",
                    'attr' => [
                        'class' => 'form'
                    ]
                ]
            );
        }

        $builder
            ->add(
                'quantity',
                ChoiceType::class,
                [
                    'choices' => $quantityChoices,
                    'label' => "Select the amount of items",
                    'attr' => [
                        'class' => 'form'
                    ]
                ]
            )
            ->add('slug', HiddenType::class)
            ->add('addToCart', SubmitType::class, ['label' => 'Add to cart'])
        ;
    }
}
