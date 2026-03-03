<?php

namespace App\Form;

use App\Entity\Coach;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Url;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username', TextType::class, [
                'constraints' => [
                    new NotBlank(['message' => 'Username is required']),
                    new Length([
                        'min' => 3,
                        'max' => 50,
                        'minMessage' => 'Username must be at least {{ limit }} characters',
                        'maxMessage' => 'Username cannot exceed {{ limit }} characters'
                    ])
                ],
                'attr' => [
                    'placeholder' => 'Enter username',
                    'class' => 'form-control'
                ]
            ])
            ->add('email', EmailType::class, [
                'constraints' => [
                    new NotBlank(['message' => 'Email is required']),
                    new Email(['message' => 'Please enter a valid email address']),
                    new Length([
                        'max' => 180,
                        'maxMessage' => 'Email cannot exceed {{ limit }} characters'
                    ])
                ],
                'attr' => [
                    'placeholder' => 'Enter email',
                    'class' => 'form-control'
                ]
            ])
            ->add('password', PasswordType::class, [
                'constraints' => [
                    new NotBlank(['message' => 'Password is required']),
                    new Length([
                        'min' => 6,
                        'max' => 255,
                        'minMessage' => 'Password must be at least {{ limit }} characters',
                        'maxMessage' => 'Password cannot exceed {{ limit }} characters'
                    ])
                ],
                'attr' => [
                    'placeholder' => 'Enter password',
                    'class' => 'form-control'
                ]
            ])
            ->add('profilePicture', UrlType::class, [
                'required' => false,
                'constraints' => [
                    new Url(['message' => 'Please enter a valid URL']),
                    new Length([
                        'max' => 255,
                        'maxMessage' => 'URL cannot exceed {{ limit }} characters'
                    ])
                ],
                'attr' => [
                    'placeholder' => 'https://example.com/photo.jpg',
                    'class' => 'form-control'
                ]
            ])
            ->add('status', ChoiceType::class, [
                'choices' => [
                    'Active' => 'ACTIVE',
                    'Banned' => 'BANNED',
                ],
                'attr' => [
                    'class' => 'form-select'
                ]
            ])
            ->add('userType', ChoiceType::class, [
                'choices' => [
                    'Visitor' => 'VISITOR',
                    'Registered' => 'REGISTERED',
                    'Coach' => 'COACH',
                    'Organization' => 'ORGANIZATION',
                    'Admin' => 'ADMIN',
                ],
                'attr' => [
                    'class' => 'form-select'
                ]
            ])
            ->add('coach', EntityType::class, [
                'class' => Coach::class,
                'choice_label' => 'id',
                'required' => false,
                'placeholder' => 'Select a coach',
                'attr' => [
                    'class' => 'form-select'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
