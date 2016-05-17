<?php
/**
 * @author: Ylambers <yaron.lambers@commercetools.de>
 */

namespace Commercetools\Symfony\CtpBundle\Model\Form\Type;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Choice;

class AddressType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('title', TextType::class, ['required' => false]);
        $builder->add('salutation', ChoiceType::class, [
            'choices' => [
                'Mr' => 'mr',
                'Mrs' => 'mrs'
            ]
        ]);
        $builder->add('firstName', TextType::class, ['attr' => ['data-required' => 'true']]);
        $builder->add('lastName', TextType::class, ['attr' => ['data-required' => 'true']]);
        $builder->add('email', EmailType::class, ['attr' => ['data-required' => 'true']]);
        $builder->add('streetName', TextType::class, ['attr' => ['data-required' => 'true']]);
        $builder->add('streetNumber', TextType::class, ['attr' => ['data-required' => 'true']]);
        $builder->add('building', TextType::class, ['required' => false]);
        $builder->add('apartment', TextType::class, ['required' => false]);
        $builder->add('department', TextType::class, ['required' => false]);
        $builder->add('city', TextType::class, ['attr' => ['data-required' => 'true']]);
        $builder->add('country', CountryType::class, ['attr' => ['data-required' => 'true']]);
        $builder->add('region', TextType::class, ['required' => false]);
        $builder->add('state', TextType::class, ['required' => false]);
        $builder->add('pOBox', TextType::class, ['label' => 'Postal Code']);
        $builder->add('additionalAddressInfo', TextType::class, ['required' => false]);
        $builder->add('additionalStreetInfo', TextType::class, ['required' => false]);
        $builder->add('phone', TextType::class, ['attr' => ['data-required' => 'true']]);
        $builder->add('mobile', TextType::class, ['required' => false]);
    }
}
