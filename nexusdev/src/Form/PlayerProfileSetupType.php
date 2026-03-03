<?php

namespace App\Form;

use App\Entity\Player;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class PlayerProfileSetupType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nickname', TextType::class, [
                'label' => 'Gamer Tag',
                'constraints' => [
                    new NotBlank(['message' => 'Gamer tag is required']),
                    new Length([
                        'min' => 2,
                        'max' => 100,
                        'minMessage' => 'Gamer tag must be at least {{ limit }} characters',
                        'maxMessage' => 'Gamer tag cannot exceed {{ limit }} characters'
                    ])
                ],
                'attr' => [
                    'placeholder' => 'Enter your gamer tag',
                    'class' => 'form-control'
                ]
            ])
            ->add('realName', TextType::class, [
                'label' => 'Real Name (Optional)',
                'required' => false,
                'constraints' => [
                    new Length([
                        'max' => 150,
                        'maxMessage' => 'Real name cannot exceed {{ limit }} characters'
                    ])
                ],
                'attr' => [
                    'placeholder' => 'Your real name',
                    'class' => 'form-control'
                ]
            ])
            ->add('role', ChoiceType::class, [
                'label' => 'Primary Role',
                'constraints' => [
                    new NotBlank(['message' => 'Please select your role'])
                ],
                'choices' => [
                    'Attacker / Carry' => 'Attacker',
                    'Defender / Tank' => 'Defender',
                    'Support / Healer' => 'Support',
                    'Jungler' => 'Jungler',
                    'Mid Laner' => 'Mid',
                    'All-Rounder' => 'All-Rounder',
                ],
                'placeholder' => 'Select your role',
                'attr' => [
                    'class' => 'form-select'
                ]
            ])
            ->add('nationality', ChoiceType::class, [
                'label' => 'Nationality',
                'constraints' => [
                    new NotBlank(['message' => 'Please select your nationality'])
                ],
                'choices' => [
                    'Tunisia' => 'Tunisia',
                    'France' => 'France',
                    'Germany' => 'Germany',
                    'United Kingdom' => 'United Kingdom',
                    'United States' => 'United States',
                    'Canada' => 'Canada',
                    'Morocco' => 'Morocco',
                    'Algeria' => 'Algeria',
                    'Egypt' => 'Egypt',
                    'Saudi Arabia' => 'Saudi Arabia',
                    'UAE' => 'UAE',
                    'Qatar' => 'Qatar',
                    'Other' => 'Other',
                ],
                'placeholder' => 'Select your nationality',
                'attr' => [
                    'class' => 'form-select'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Player::class,
        ]);
    }
}
