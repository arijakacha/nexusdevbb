<?php

namespace App\Form;

use App\Entity\Game;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\Constraints\Url;

class GameType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'constraints' => [
                    new NotBlank(['message' => 'Game name is required']),
                    new Length([
                        'min' => 2,
                        'max' => 150,
                        'minMessage' => 'Name must be at least {{ limit }} characters',
                        'maxMessage' => 'Name cannot exceed {{ limit }} characters'
                    ])
                ],
                'attr' => [
                    'placeholder' => 'Enter game name',
                    'class' => 'form-control'
                ]
            ])
            ->add('logo', UrlType::class, [
                'required' => false,
                'constraints' => [
                    new Url(['message' => 'Please enter a valid URL']),
                    new Length([
                        'max' => 255,
                        'maxMessage' => 'URL cannot exceed {{ limit }} characters'
                    ])
                ],
                'attr' => [
                    'placeholder' => 'https://example.com/logo.jpg',
                    'class' => 'form-control'
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
                    'placeholder' => 'Enter game description',
                    'rows' => 4,
                    'class' => 'form-control'
                ]
            ])
            ->add('releaseYear', IntegerType::class, [
                'required' => false,
                'label' => 'Release Year',
                'constraints' => [
                    new Range([
                        'min' => 1970,
                        'max' => 2100,
                        'notInRangeMessage' => 'Year must be between {{ min }} and {{ max }}',
                        'invalidMessage' => 'Please enter a valid year'
                    ])
                ],
                'attr' => [
                    'placeholder' => 'e.g., 2020',
                    'class' => 'form-control',
                    'min' => 1970,
                    'max' => 2100
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Game::class,
        ]);
    }
}
