<?php

namespace App\Form;

use App\Entity\Player;
use App\Entity\Stream;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Url;

class StreamType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('player', EntityType::class, [
                'class' => Player::class,
                'choice_label' => 'nickname',
                'placeholder' => 'Select a player',
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('p')
                        ->andWhere('p.isPro = :pro')
                        ->setParameter('pro', true)
                        ->orderBy('p.nickname', 'ASC');
                },
                'attr' => [
                    'class' => 'form-select'
                ]
            ])
            ->add('title', TextType::class, [
                'constraints' => [
                    new NotBlank(['message' => 'Stream title is required']),
                    new Length([
                        'min' => 5,
                        'max' => 200,
                        'minMessage' => 'Title must be at least {{ limit }} characters',
                        'maxMessage' => 'Title cannot exceed {{ limit }} characters'
                    ])
                ],
                'attr' => [
                    'placeholder' => 'Enter stream title',
                    'class' => 'form-control'
                ]
            ])
            ->add('url', UrlType::class, [
                'constraints' => [
                    new NotBlank(['message' => 'Stream URL is required']),
                    new Url(['message' => 'Please enter a valid URL']),
                    new Length([
                        'max' => 255,
                        'maxMessage' => 'URL cannot exceed {{ limit }} characters'
                    ])
                ],
                'attr' => [
                    'placeholder' => 'https://twitch.tv/yourchannel',
                    'class' => 'form-control'
                ]
            ])
            ->add('isLive', CheckboxType::class, [
                'required' => false,
                'label' => 'Currently Live',
                'attr' => [
                    'class' => 'form-check-input'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Stream::class,
        ]);
    }
}
