<?php

namespace App\Form;

use App\Entity\Coach;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

class CoachType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('experienceLevel', TextType::class, [
                'label' => 'Experience Level',
                'constraints' => [
                    new NotBlank(['message' => 'Experience level is required']),
                    new Length([
                        'max' => 100,
                        'maxMessage' => 'Experience level cannot exceed {{ limit }} characters'
                    ])
                ],
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'e.g. Expert, 5 years, etc.'
                ]
            ])
            ->add('bio', TextareaType::class, [
                'label' => 'Biography',
                'required' => false,
                'constraints' => [
                    new Length([
                        'max' => 2000,
                        'maxMessage' => 'Bio cannot exceed {{ limit }} characters'
                    ])
                ],
                'attr' => [
                    'rows' => 5,
                    'class' => 'form-control',
                    'placeholder' => 'Tell us about your coaching experience...'
                ]
            ])
            ->add('rating', NumberType::class, [
                'constraints' => [
                    new Range([
                        'min' => 1,
                        'max' => 5,
                        'notInRangeMessage' => 'Rating must be between {{ min }} and {{ max }}',
                        'invalidMessage' => 'Please enter a valid rating (1-5)'
                    ])
                ],
                'attr' => [
                    'min' => 1,
                    'max' => 5,
                    'step' => 0.1,
                    'class' => 'form-control',
                    'placeholder' => 'Rating (1-5)'
                ]
            ])
            ->add('pricePerSession', NumberType::class, [
                'label' => 'Price Per Session ($)',
                'constraints' => [
                    new Range([
                        'min' => 0,
                        'max' => 10000,
                        'notInRangeMessage' => 'Price must be between {{ min }} and {{ max }}',
                        'invalidMessage' => 'Please enter a valid price'
                    ])
                ],
                'attr' => [
                    'min' => 0,
                    'step' => 0.01,
                    'class' => 'form-control',
                    'placeholder' => '0.00'
                ]
            ])
            ->add('user', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'username',
                'placeholder' => 'Select a user',
                'attr' => [
                    'class' => 'form-select'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Coach::class,
        ]);
    }
}
