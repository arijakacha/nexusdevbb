<?php

namespace App\Form;

use App\Entity\Player;
use App\Entity\Game;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PlayerProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nickname', TextType::class, [
                'label' => 'Gamer Tag',
                'attr' => ['placeholder' => 'Enter your gamer tag'],
            ])
            ->add('realName', TextType::class, [
                'label' => 'Real Name (Optional)',
                'required' => false,
                'attr' => ['placeholder' => 'Your real name'],
            ])
            ->add('role', ChoiceType::class, [
                'label' => 'Primary Role',
                'choices' => [
                    'Attacker / Carry' => 'Attacker',
                    'Defender / Tank' => 'Defender',
                    'Support / Healer' => 'Support',
                    'Jungler' => 'Jungler',
                    'Mid Laner' => 'Mid',
                    'All-Rounder' => 'All-Rounder',
                ],
                'placeholder' => 'Select your role',
            ])
            ->add('nationality', ChoiceType::class, [
                'label' => 'Nationality',
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
            ])
            ->add('game', EntityType::class, [
                'label' => 'Primary Game',
                'class' => Game::class,
                'choice_label' => 'name',
                'placeholder' => 'Choose your main game',
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
