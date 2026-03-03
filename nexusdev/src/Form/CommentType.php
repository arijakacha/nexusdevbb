<?php

namespace App\Form;

use App\Entity\Comment;
use App\Entity\ForumPost;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use FOS\CKEditorBundle\Form\Type\CKEditorType;

class CommentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('content', CKEditorType::class, [
                'label' => 'Comment',
                'constraints' => [
                    new NotBlank(['message' => 'Comment cannot be empty']),
                    new Length([
                        'min' => 5,
                        'max' => 1000,
                        'minMessage' => 'Comment must be at least {{ limit }} characters',
                        'maxMessage' => 'Comment cannot exceed {{ limit }} characters'
                    ])
                ],
                'attr' => [
                    'rows' => 4,
                    'class' => 'form-control',
                    'placeholder' => 'Write your comment...'
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
            ->add('post', EntityType::class, [
                'class' => ForumPost::class,
                'choice_label' => 'title',
                'placeholder' => 'Select a post',
                'attr' => [
                    'class' => 'form-select'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Comment::class,
        ]);
    }
}
