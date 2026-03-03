<?php

namespace App\Form;

use App\Entity\Game;
use App\Entity\Organization;
use App\Entity\Team;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

class TeamType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'constraints' => [
                    new NotBlank(['message' => 'Team name is required']),
                    new Length([
                        'min' => 2,
                        'max' => 150,
                        'minMessage' => 'Name must be at least {{ limit }} characters',
                        'maxMessage' => 'Name cannot exceed {{ limit }} characters'
                    ])
                ],
                'attr' => [
                    'placeholder' => 'Enter team name',
                    'class' => 'form-control'
                ]
            ])
            ->add('logo', FileType::class, [
                'label' => 'Logo (Image file)',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new Image([
                        'maxSize' => '2M',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
                        'mimeTypesMessage' => 'Please upload a valid image (JPG, PNG, GIF, WebP)',
                        'maxSizeMessage' => 'The image is too large. Maximum size is {{ limit }}'
                    ])
                ],
                'attr' => [
                    'accept' => 'image/*',
                    'class' => 'form-control'
                ]
            ])
            ->add('country', TextType::class, [
                'required' => false,
                'constraints' => [
                    new Length([
                        'max' => 80,
                        'maxMessage' => 'Country cannot exceed {{ limit }} characters'
                    ])
                ],
                'attr' => [
                    'placeholder' => 'Enter country',
                    'class' => 'form-control'
                ]
            ])
            ->add('foundationYear', IntegerType::class, [
                'required' => false,
                'label' => 'Foundation Year',
                'constraints' => [
                    new Range([
                        'min' => 1900,
                        'max' => 2100,
                        'notInRangeMessage' => 'Year must be between {{ min }} and {{ max }}',
                        'invalidMessage' => 'Please enter a valid year'
                    ])
                ],
                'attr' => [
                    'placeholder' => 'e.g., 2020',
                    'class' => 'form-control',
                    'min' => 1900,
                    'max' => 2100
                ]
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
                'constraints' => [
                    new Length([
                        'max' => 2000,
                        'maxMessage' => 'Description cannot exceed {{ limit }} characters'
                    ])
                ],
                'attr' => [
                    'placeholder' => 'Enter team description',
                    'rows' => 4,
                    'class' => 'form-control'
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
            ->add('organization', EntityType::class, [
                'class' => Organization::class,
                'choice_label' => 'name',
                'required' => false,
                'placeholder' => 'Select an organization',
                'attr' => [
                    'class' => 'form-select'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Team::class,
        ]);
    }
}
