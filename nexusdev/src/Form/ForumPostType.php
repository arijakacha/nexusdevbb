<?php

namespace App\Form;

use App\Entity\ForumPost;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class ForumPostType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'constraints' => [
                    new NotBlank(['message' => 'Post title is required']),
                    new Length([
                        'min' => 5,
                        'max' => 200,
                        'minMessage' => 'Title must be at least {{ limit }} characters',
                        'maxMessage' => 'Title cannot exceed {{ limit }} characters'
                    ])
                ],
                'attr' => [
                    'placeholder' => 'Enter post title',
                    'class' => 'form-control'
                ]
            ])
            ->add('imageFile', FileType::class, [
                'label' => 'Image',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new Image([
                        'maxSize' => '4M',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/webp'],
                        'mimeTypesMessage' => 'Please upload a valid image (JPEG, PNG, WebP)',
                    ])
                ],
                'attr' => [
                    'accept' => 'image/*',
                    'class' => 'form-control',
                ],
            ])
            ->add('content', TextareaType::class, [
                'constraints' => [
                    new NotBlank(['message' => 'Post content is required']),
                    new Length([
                        'min' => 10,
                        'max' => 5000,
                        'minMessage' => 'Content must be at least {{ limit }} characters',
                        'maxMessage' => 'Content cannot exceed {{ limit }} characters'
                    ])
                ],
                'attr' => [
                    'rows' => 8,
                    'class' => 'form-control',
                    'placeholder' => 'Write your post content here...'
                ]
            ])
            ->add('author', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'username',
                'placeholder' => 'Select an author',
                'attr' => [
                    'class' => 'form-select'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ForumPost::class,
        ]);
    }
}
