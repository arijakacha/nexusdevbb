<?php

namespace App\Form;

use App\Entity\Player;
use App\Entity\Team;
use App\Entity\Game;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

class PlayerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nickname', TextType::class, [
                'constraints' => [
                    new NotBlank(['message' => 'Nickname is required']),
                    new Length([
                        'min' => 2,
                        'max' => 100,
                        'minMessage' => 'Nickname must be at least {{ limit }} characters',
                        'maxMessage' => 'Nickname cannot exceed {{ limit }} characters'
                    ])
                ],
                'attr' => [
                    'placeholder' => 'Enter player nickname',
                    'class' => 'form-control'
                ]
            ])
            ->add('realName', TextType::class, [
                'required' => false,
                'constraints' => [
                    new Length([
                        'max' => 150,
                        'maxMessage' => 'Real name cannot exceed {{ limit }} characters'
                    ])
                ],
                'attr' => [
                    'placeholder' => 'Enter real name (optional)',
                    'class' => 'form-control'
                ]
            ])
            ->add('role', TextType::class, [
                'required' => false,
                'constraints' => [
                    new Length([
                        'max' => 80,
                        'maxMessage' => 'Role cannot exceed {{ limit }} characters'
                    ])
                ],
                'attr' => [
                    'placeholder' => 'e.g., Mid Laner, Attacker',
                    'class' => 'form-control'
                ]
            ])
            ->add('nationality', TextType::class, [
                'required' => false,
                'constraints' => [
                    new Length([
                        'max' => 80,
                        'maxMessage' => 'Nationality cannot exceed {{ limit }} characters'
                    ])
                ],
                'attr' => [
                    'placeholder' => 'Enter nationality',
                    'class' => 'form-control'
                ]
            ])
            ->add('profilePictureFile', FileType::class, [
                'label' => 'Profile Picture',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new Image([
                        'maxSize' => '2M',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
                        'mimeTypesMessage' => 'Please upload a valid image (JPEG, PNG, WebP, GIF)',
                    ])
                ],
                'attr' => [
                    'accept' => 'image/*',
                    'class' => 'form-control',
                ],
            ])
            ->add('score', IntegerType::class, [
                'constraints' => [
                    new Range([
                        'min' => 0,
                        'max' => 999999,
                        'notInRangeMessage' => 'Score must be between {{ min }} and {{ max }}',
                        'invalidMessage' => 'Please enter a valid number'
                    ])
                ],
                'attr' => [
                    'min' => 0,
                    'step' => 1,
                    'class' => 'form-control',
                    'placeholder' => '0'
                ]
            ])
            ->add('team', EntityType::class, [
                'class' => Team::class,
                'choice_label' => 'name',
                'required' => false,
                'placeholder' => 'Select a team',
                'attr' => [
                    'class' => 'form-select'
                ]
            ])
            ->add('game', EntityType::class, [
                'class' => Game::class,
                'choice_label' => 'name',
                'placeholder' => 'Select a game',
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
