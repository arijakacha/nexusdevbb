<?php

namespace App\Controller;

use App\Entity\Coach;
use App\Repository\CoachRepository;
use App\Repository\PlayerRepository;
use App\Entity\CoachingSession;
use Knp\Component\Pager\PaginatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/FCoach')]
final class FrontCoachController extends AbstractController
{
    #[Route(name: 'front_coach_index', methods: ['GET'])]
    public function index(
        CoachRepository $coachRepository,
        PaginatorInterface $paginator,
        Request $request
    ): Response {
        $qb = $coachRepository->createQueryBuilder('c')
            ->leftJoin('c.user', 'u')
            ->addSelect('u');

        // Search filters
        $search = $request->query->get('search');
        $experience = $request->query->get('experience');
        $minRate = $request->query->getInt('minRate');
        $maxRate = $request->query->getInt('maxRate');
        $sortBy = $request->query->get('sortBy', 'experienceLevel');
        $sortOrder = $request->query->get('sortOrder', 'DESC');

        if ($search) {
            $qb->andWhere('u.username LIKE :search OR c.bio LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        if ($experience) {
            $qb->andWhere('c.experienceLevel = :experience')
               ->setParameter('experience', $experience);
        }

        if ($minRate) {
            $qb->andWhere('c.pricePerSession >= :minRate')
               ->setParameter('minRate', $minRate);
        }

        if ($maxRate) {
            $qb->andWhere('c.pricePerSession <= :maxRate')
               ->setParameter('maxRate', $maxRate);
        }

        $allowedSortFields = ['experienceLevel', 'pricePerSession', 'createdAt'];
        if (\in_array($sortBy, $allowedSortFields, true)) {
            $qb->orderBy('c.' . $sortBy, strtoupper($sortOrder) === 'DESC' ? 'DESC' : 'ASC');
        }

        $pagination = $paginator->paginate(
            $qb,
            $request->query->getInt('page', 1),
            12
        );

        return $this->render('front/coach/index.html.twig', [
            'pagination' => $pagination,
            'experienceLevels' => ['Beginner', 'Intermediate', 'Advanced', 'Expert'],
        ]);
    }

    #[Route('/book/{coachId}', name: 'front_coaching_book', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function book(
        int $coachId,
        Request $request,
        CoachRepository $coachRepository,
        PlayerRepository $playerRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();
        
        // Find player by user - Player has user relationship
        $player = $playerRepository->findOneBy(['user' => $user]);
        
        if (!$player) {
            $this->addFlash('warning', 'You need to create a player profile first to book coaching sessions.');
            return $this->redirectToRoute('app_player_become');
        }
        
        $coach = $coachRepository->find($coachId);
        
        if (!$coach) {
            throw $this->createNotFoundException('Coach not found');
        }
        
        $session = new CoachingSession();
        $session->setPlayer($player);
        $session->setCoach($coach);
        
        $form = $this->createFormBuilder($session)
            ->add('scheduledAt', DateTimeType::class, [
                'label' => 'Session Date & Time',
                'widget' => 'single_text',
                'attr' => [
                    'min' => (new \DateTime('+1 hour'))->format('Y-m-d\TH:i'),
                    'class' => 'form-control'
                ],
                'help' => 'Choose a date and time for your coaching session (at least 1 hour from now)'
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Notes for Coach',
                'required' => false,
                'attr' => [
                    'rows' => 3,
                    'placeholder' => 'What would you like to focus on? (e.g., specific champions, macro play, etc.)',
                    'class' => 'form-control'
                ]
            ])
            ->getForm();
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $session->setStatus('PENDING');
            $entityManager->persist($session);
            $entityManager->flush();
            
            $this->addFlash('success', 'Your coaching session request has been sent to ' . $coach->getUser()->getUsername() . '. You will be notified when they confirm.');
            
            return $this->redirectToRoute('front_coach_index');
        }
        
        return $this->render('front/coach/book.html.twig', [
            'coach' => $coach,
            'player' => $player,
            'form' => $form,
        ]);
    }
    
    #[Route('/my-sessions', name: 'front_coaching_player_sessions', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function mySessions(PlayerRepository $playerRepository): Response
    {
        $user = $this->getUser();
        
        $player = $playerRepository->findOneBy(['user' => $user]);
        
        if (!$player) {
            $this->addFlash('warning', 'You need a player profile to view coaching sessions.');
            return $this->redirectToRoute('app_player_become');
        }
        
        $sessions = $player->getCoachingSessions();
        
        return $this->render('front/coach/player_sessions.html.twig', [
            'sessions' => $sessions,
            'player' => $player,
        ]);
    }
}
