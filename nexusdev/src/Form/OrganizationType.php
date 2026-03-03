<?php

namespace App\Form;

use App\Entity\Organization;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Image;

class OrganizationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'constraints' => [
                    new NotBlank(['message' => 'Organization name is required']),
                    new Length([
                        'min' => 2,
                        'max' => 150,
                        'minMessage' => 'Name must be at least {{ limit }} characters',
                        'maxMessage' => 'Name cannot exceed {{ limit }} characters'
                    ])
                ],
                'attr' => [
                    'placeholder' => 'Enter organization name',
                    'class' => 'form-control'
                ]
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
                'constraints' => [
                    new Length([
                        'max' => 1000,
                        'maxMessage' => 'Description cannot exceed {{ limit }} characters'
                    ])
                ],
                'attr' => [
                    'placeholder' => 'Enter organization description',
                    'rows' => 4,
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
            ->add('isValidated', CheckboxType::class, [
                'required' => false,
                'label' => 'Is Validated',
                'attr' => [
                    'class' => 'form-check-input'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Organization::class,
        ]);
    }
}
