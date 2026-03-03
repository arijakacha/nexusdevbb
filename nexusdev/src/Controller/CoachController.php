<?php

namespace App\Controller;

use App\Entity\Coach;
use App\Entity\CoachingSession;
use App\Entity\Player;
use App\Form\CoachType;
use App\Repository\CoachRepository;
use App\Repository\CoachingSessionRepository;
use App\Repository\PlayerRepository;
use App\Repository\StatisticRepository;
use App\Service\JitsiService;
use App\Service\SmsService;
use App\Service\WhatsAppService;
use App\Service\SimulatedWhatsAppService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/BCoach')]
final class CoachController extends AbstractController
{
    #[Route(name: 'app_coach_back', methods: ['GET', 'POST'])]
    public function back(
        Request $request,
        CoachRepository $coachRepository,
        CoachingSessionRepository $coachingSessionRepository,
        PlayerRepository $playerRepository,
        EntityManagerInterface $entityManager,
        PaginatorInterface $paginator
    ): Response {
        if ($this->isGranted('ROLE_ADMIN')) {
        $qb = $coachRepository->createQueryBuilder('c')
            ->leftJoin('c.user', 'u')
            ->addSelect('u');

        $search = $request->query->get('search');
        if ($search) {
            $qb->andWhere('u.username LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        // Sorting
        $sort = $request->query->get('sort', 'id');
        $direction = $request->query->get('direction', 'ASC');
        
        $allowedSorts = ['id', 'experienceLevel', 'rating', 'pricePerSession'];
        $allowedDirections = ['ASC', 'DESC'];
        
        if (!in_array($sort, $allowedSorts)) {
            $sort = 'id';
        }
        if (!in_array(strtoupper($direction), $allowedDirections)) {
            $direction = 'ASC';
        }
        
        $qb->orderBy('c.' . $sort, $direction);

        // Get results manually and create pagination array
        $query = $qb->getQuery();
        $results = $query->getResult();
        
        // Use paginator with array to bypass OrderByWalker
        $pagination = $paginator->paginate(
            $results,
            $request->query->getInt('page', 1),
            10
        );

        $coachId = $request->query->getInt('id', 0);
        if ($coachId > 0) {
            $coach = $coachRepository->find($coachId);
            if (!$coach) {
                throw $this->createNotFoundException('Coach not found');
            }
        } else {
            $coach = new Coach();
        }

        $form = $this->createForm(CoachType::class, $coach);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $isNew = $coach->getId() === null;
            if ($isNew) {
                $entityManager->persist($coach);
            }
            $entityManager->flush();

            $this->addFlash('success', $isNew ? 'Coach created successfully.' : 'Coach updated successfully.');

            return $this->redirectToRoute('app_coach_back', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('coach/back.html.twig', [
            'pagination' => $pagination,
            'form' => $form,
            'editing' => $coach->getId() !== null,
            'currentCoach' => $coach,
            'mode' => 'management',
            'sort' => $sort,
            'direction' => $direction,
        ]);
        }

        $user = $this->getUser();
        if (!$user || !$user->getCoach()) {
            throw $this->createAccessDeniedException('You do not have a coach profile.');
        }

        $coach = $user->getCoach();

        $dateParam = (string) $request->query->get('date', '');
        $selectedDate = null;
        if ($dateParam !== '') {
            try {
                $selectedDate = new \DateTimeImmutable($dateParam);
            } catch (\Throwable) {
                $selectedDate = null;
            }
        }
        if (!$selectedDate) {
            $selectedDate = new \DateTimeImmutable('today');
        }

        $dayStart = $selectedDate->setTime(0, 0, 0);
        $dayEnd = $dayStart->modify('+1 day');

        $sessions = $coachingSessionRepository->findForCoachBetween($coach, $dayStart, $dayEnd);

        $players = $playerRepository->findAll();

        $session = new CoachingSession();
        $session->setCoach($coach);
        $session->setStatus('CONFIRMED');

        $createForm = $this->createFormBuilder($session)
            ->add('player', EntityType::class, [
                'class' => Player::class,
                'choice_label' => 'nickname',
                'placeholder' => 'Select a player',
                'attr' => [
                    'class' => 'form-select'
                ]
            ])
            ->add('scheduledAt', DateTimeType::class, [
                'widget' => 'single_text',
                'label' => 'Meeting date & time',
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->getForm();

        $createForm->handleRequest($request);
        if ($createForm->isSubmitted() && $createForm->isValid()) {
            $entityManager->persist($session);
            $entityManager->flush();

            $this->addFlash('success', 'Meeting created.');
            return $this->redirectToRoute('app_coach_back', ['date' => $dayStart->format('Y-m-d')], Response::HTTP_SEE_OTHER);
        }

        // Get analytics data
        $totalSessions = $coachingSessionRepository->countTotalSessionsForCoach($coach);
        $completedSessions = $coachingSessionRepository->countCompletedSessionsForCoach($coach);
        $upcomingSessions = $coachingSessionRepository->countUpcomingSessionsForCoach($coach);
        $uniquePlayers = $coachingSessionRepository->findUniquePlayersForCoach($coach);
        
        // Calculate estimated earnings
        $estimatedEarnings = $completedSessions * (float) ($coach->getPricePerSession() ?? 0);

        // Get calendar data for inline display
        $year = (int) $request->query->get('year', date('Y'));
        $month = (int) $request->query->get('month', date('m'));
        if ($month < 1 || $month > 12) {
            $month = date('m');
        }
        if ($year < 2020 || $year > 2030) {
            $year = date('Y');
        }
        $monthSessions = $coachingSessionRepository->findForCoachInMonth($coach, $year, $month);
        $firstDay = new \DateTimeImmutable("$year-$month-01");
        $daysInMonth = (int) $firstDay->format('t');
        $startDayOfWeek = (int) $firstDay->format('w');

        return $this->render('coach/dashboard.html.twig', [
            'coach' => $coach,
            'sessions' => $sessions,
            'players' => $players,
            'selectedDate' => $dayStart,
            'createSessionForm' => $createForm->createView(),
            'analytics' => [
                'totalSessions' => $totalSessions,
                'completedSessions' => $completedSessions,
                'upcomingSessions' => $upcomingSessions,
                'uniquePlayersCount' => count($uniquePlayers),
                'estimatedEarnings' => $estimatedEarnings,
                'rating' => $coach->getRating(),
            ],
            'calendarData' => [
                'year' => $year,
                'month' => $month,
                'monthName' => $firstDay->format('F'),
                'daysInMonth' => $daysInMonth,
                'startDayOfWeek' => $startDayOfWeek,
                'sessions' => $monthSessions,
            ],
        ]);
    }

    #[Route('/{id}/delete', name: 'app_coach_delete', methods: ['POST'])]
    public function delete(Request $request, Coach $coach, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$coach->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($coach);
            $entityManager->flush();
            $this->addFlash('success', 'Coach deleted successfully.');
        }

        return $this->redirectToRoute('app_coach_back', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/profile', name: 'app_coach_profile', methods: ['GET', 'POST'])]
    public function profile(Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isGranted('ROLE_COACH')) {
            throw $this->createAccessDeniedException();
        }

        $user = $this->getUser();
        if (!$user || !$user->getCoach()) {
            throw $this->createAccessDeniedException('You do not have a coach profile.');
        }

        $coach = $user->getCoach();
        $form = $this->createFormBuilder($coach)
            ->add('experienceLevel', null, [
                'label' => 'Experience Level',
                'attr' => ['class' => 'form-control']
            ])
            ->add('bio', TextareaType::class, [
                'label' => 'Bio',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 5]
            ])
            ->add('pricePerSession', null, [
                'label' => 'Price per Session ($)',
                'attr' => ['class' => 'form-control']
            ])
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Profile updated successfully.');
            return $this->redirectToRoute('app_coach_profile');
        }

        return $this->render('coach/profile.html.twig', [
            'coach' => $coach,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/sessions', name: 'app_coach_sessions', methods: ['GET'])]
    public function sessions(CoachingSessionRepository $coachingSessionRepository): Response
    {
        if (!$this->isGranted('ROLE_COACH')) {
            throw $this->createAccessDeniedException();
        }

        $user = $this->getUser();
        if (!$user || !$user->getCoach()) {
            throw $this->createAccessDeniedException('You do not have a coach profile.');
        }

        $coach = $user->getCoach();
        $allSessions = $coachingSessionRepository->findAllSessionsForCoach($coach);

        return $this->render('coach/sessions.html.twig', [
            'coach' => $coach,
            'sessions' => $allSessions,
        ]);
    }

    #[Route('/session/{id}', name: 'app_coach_session_detail', methods: ['GET', 'POST'])]
    public function sessionDetail(Request $request, CoachingSession $session, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isGranted('ROLE_COACH')) {
            throw $this->createAccessDeniedException();
        }

        $user = $this->getUser();
        if (!$user || !$user->getCoach() || $session->getCoach() !== $user->getCoach()) {
            throw $this->createAccessDeniedException('You can only manage your own sessions.');
        }

        $form = $this->createFormBuilder($session)
            ->add('status', ChoiceType::class, [
                'choices' => [
                    'Pending' => 'PENDING',
                    'Confirmed' => 'CONFIRMED',
                    'Completed' => 'COMPLETED',
                    'Cancelled' => 'CANCELLED',
                ],
                'label' => 'Status',
                'attr' => ['class' => 'form-select']
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Session Notes',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 5, 'placeholder' => 'Add notes about this session...']
            ])
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Session updated successfully.');
            return $this->redirectToRoute('app_coach_session_detail', ['id' => $session->getId()]);
        }

        return $this->render('coach/session_detail.html.twig', [
            'session' => $session,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/calendar', name: 'app_coach_calendar', methods: ['GET'])]
    public function calendar(Request $request, CoachingSessionRepository $coachingSessionRepository): Response
    {
        if (!$this->isGranted('ROLE_COACH')) {
            throw $this->createAccessDeniedException();
        }

        $user = $this->getUser();
        if (!$user || !$user->getCoach()) {
            throw $this->createAccessDeniedException('You do not have a coach profile.');
        }

        $coach = $user->getCoach();

        $year = (int) $request->query->get('year', date('Y'));
        $month = (int) $request->query->get('month', date('m'));

        // Validate month/year
        if ($month < 1 || $month > 12) {
            $month = date('m');
        }
        if ($year < 2020 || $year > 2030) {
            $year = date('Y');
        }

        $sessions = $coachingSessionRepository->findForCoachInMonth($coach, $year, $month);

        // Build calendar data
        $firstDay = new \DateTimeImmutable("$year-$month-01");
        $daysInMonth = (int) $firstDay->format('t');
        $startDayOfWeek = (int) $firstDay->format('w'); // 0 = Sunday

        return $this->render('coach/calendar.html.twig', [
            'coach' => $coach,
            'sessions' => $sessions,
            'year' => $year,
            'month' => $month,
            'monthName' => $firstDay->format('F'),
            'daysInMonth' => $daysInMonth,
            'startDayOfWeek' => $startDayOfWeek,
        ]);
    }

    #[Route('/player/{id}', name: 'app_coach_player_detail', methods: ['GET'])]
    public function playerDetail(Player $player, CoachingSessionRepository $coachingSessionRepository, StatisticRepository $statisticRepository): Response
    {
        if (!$this->isGranted('ROLE_COACH')) {
            throw $this->createAccessDeniedException();
        }

        $user = $this->getUser();
        if (!$user || !$user->getCoach()) {
            throw $this->createAccessDeniedException('You do not have a coach profile.');
        }

        $coach = $user->getCoach();

        // Get coaching history with this player
        $sessions = $coachingSessionRepository->findSessionsBetweenCoachAndPlayer($coach, $player);

        // Get player statistics
        $statistics = $statisticRepository->findBy(['player' => $player]);

        return $this->render('coach/player_detail.html.twig', [
            'coach' => $coach,
            'player' => $player,
            'sessions' => $sessions,
            'statistics' => $statistics,
        ]);
    }

    #[Route('/session/{id}/create-meeting', name: 'app_coach_session_create_meeting', methods: ['POST'])]
    public function createMeeting(
        CoachingSession $session, 
        JitsiService $jitsiService, 
        EntityManagerInterface $entityManager
    ): Response {
        try {
            if (!$this->isGranted('ROLE_COACH')) {
                throw new \Exception('Access denied: Coach role required');
            }

            $user = $this->getUser();
            if (!$user || !$user->getCoach()) {
                throw new \Exception('Access denied: No coach profile found');
            }
            
            if ($session->getCoach() !== $user->getCoach()) {
                throw new \Exception('Access denied: You can only create meetings for your own sessions');
            }

            // Check if meeting already exists and is still active
            if ($session->getMeetingUrl() && $session->isMeetingActive()) {
                return $this->json([
                    'success' => true,
                    'meetingUrl' => $session->getMeetingUrl(),
                    'message' => 'Meeting already exists and is active'
                ]);
            }

            // Generate room name
            $roomName = $jitsiService->generateRoomName(
                $session->getId(),
                $user->getUsername(),
                $session->getPlayer()->getNickname()
            );

            // Create meeting
            $meeting = $jitsiService->createMeeting($roomName, [
                'subject' => 'Coaching Session with ' . $session->getPlayer()->getNickname(),
                'exp' => (time() + 7200), // 2 hours
            ]);

            if (!$meeting['success']) {
                throw new \Exception('Jitsi meeting creation failed: ' . $meeting['error']);
            }

            // Save meeting to database
            $session->setMeetingUrl($meeting['meetingUrl']);
            $session->setMeetingRoom($meeting['roomName']);
            $session->setMeetingExpiresAt(new \DateTimeImmutable('@' . $meeting['expiresAt']));
            $entityManager->flush();

            // Send SMS to player if they consent and have phone number
            $smsSent = false;
            $smsError = null;
            
            // TODO: Enable SMS after Twilio is configured
            /*
            try {
                if ($session->getPlayer()->canReceiveSms()) {
                    $smsResult = $smsService->sendMeetingLink(
                        $session->getPlayer()->getPhoneNumber(),
                        $session->getPlayer()->getNickname(),
                        $meeting['meetingUrl'],
                        $user->getUsername(),
                        $session->getScheduledAt()
                    );
                    
                    $smsSent = $smsResult['success'];
                    $smsError = $smsResult['error'] ?? null;
                }
            } catch (\Exception $smsException) {
                // Don't fail the whole process if SMS fails
                $smsError = 'SMS service error: ' . $smsException->getMessage();
                error_log('SMS sending failed: ' . $smsException->getMessage());
            }
            */

            return $this->json([
                'success' => true,
                'meetingUrl' => $meeting['meetingUrl'],
                'roomName' => $meeting['roomName'],
                'expiresAt' => $meeting['expiresAt'],
                'smsSent' => $smsSent,
                'smsError' => $smsError,
                'canReceiveSms' => $session->getPlayer()->canReceiveSms()
            ]);

        } catch (\Exception $e) {
            // Log the error for debugging
            error_log('Meeting creation error: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            
            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
                'debug' => 'Check server logs for more details'
            ], 500);
        }
    }

    #[Route('/session/{id}/join-meeting', name: 'app_coach_session_join_meeting', methods: ['GET'])]
    public function joinMeeting(CoachingSession $session): Response
    {
        if (!$this->isGranted('ROLE_COACH')) {
            throw $this->createAccessDeniedException();
        }

        $user = $this->getUser();
        if (!$user || !$user->getCoach() || $session->getCoach() !== $user->getCoach()) {
            throw $this->createAccessDeniedException('You can only join meetings for your own sessions.');
        }

        if (!$session->getMeetingUrl() || !$session->isMeetingActive()) {
            $this->addFlash('error', 'Meeting is not available or has expired.');
            return $this->redirectToRoute('app_coach_session_detail', ['id' => $session->getId()]);
        }

        return $this->redirect($session->getMeetingUrl());
    }

    #[Route('/session/{id}/complete', name: 'app_coach_session_complete', methods: ['POST'])]
    public function completeSession(CoachingSession $session, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isGranted('ROLE_COACH')) {
            throw $this->createAccessDeniedException();
        }

        $user = $this->getUser();
        if (!$user || !$user->getCoach() || $session->getCoach() !== $user->getCoach()) {
            throw $this->createAccessDeniedException('You can only complete your own sessions.');
        }

        // Mark session as completed
        $session->setStatus('COMPLETED');
        $entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Session marked as completed'
        ]);
    }

    #[Route('/session/{id}/whatsapp-debug', name: 'app_coach_session_whatsapp_debug', methods: ['GET'])]
    public function whatsappDebug(SimulatedWhatsAppService $whatsAppService): Response
    {
        if (!$this->isGranted('ROLE_COACH')) {
            throw $this->createAccessDeniedException();
        }

        $debug = [
            'configured' => $whatsAppService->isConfigured(),
            'service_info' => $whatsAppService->getServiceInfo(),
            'env_vars' => [
                'TWILIO_ACCOUNT_SID' => $_ENV['TWILIO_ACCOUNT_SID'] ?? 'NOT SET',
                'TWILIO_AUTH_TOKEN' => $_ENV['TWILIO_AUTH_TOKEN'] ? 'SET' : 'NOT SET',
                'TWILIO_PHONE_NUMBER' => $_ENV['TWILIO_PHONE_NUMBER'] ?? 'NOT SET',
            ],
            'services_yaml' => [
                'accountSid_length' => strlen($_ENV['TWILIO_ACCOUNT_SID'] ?? ''),
                'authToken_length' => strlen($_ENV['TWILIO_AUTH_TOKEN'] ?? ''),
                'phoneNumber' => $_ENV['TWILIO_PHONE_NUMBER'] ?? 'NOT SET',
            ]
        ];

        return $this->json($debug);
    }

    #[Route('/session/{id}/send-whatsapp', name: 'app_coach_session_send_whatsapp', methods: ['POST'])]
    public function sendWhatsApp(
        CoachingSession $session, 
        Request $request,
        SimulatedWhatsAppService $whatsAppService,
        JitsiService $jitsiService
    ): Response {
        if (!$this->isGranted('ROLE_COACH')) {
            throw $this->createAccessDeniedException();
        }

        $user = $this->getUser();
        if (!$user || !$user->getCoach() || $session->getCoach() !== $user->getCoach()) {
            throw $this->createAccessDeniedException('You can only send WhatsApp messages for your own sessions.');
        }

        $phoneNumber = $request->request->get('phone_number');
        
        if (empty($phoneNumber)) {
            return $this->json([
                'success' => false,
                'error' => 'Phone number is required'
            ], 400);
        }

        try {
            // Check if WhatsApp service is configured
            if (!$whatsAppService->isConfigured()) {
                return $this->json([
                    'success' => false,
                    'error' => 'WhatsApp service is not configured. Please add TWILIO credentials to .env.local'
                ], 500);
            }

            // Check if meeting exists, if not create one for testing
            $meetingUrl = $session->getMeetingUrl();
            if (!$meetingUrl || !$session->isMeetingActive()) {
                // Create a test meeting URL
                $roomName = 'test-' . $session->getId() . '-' . time();
                $meeting = $jitsiService->createMeeting($roomName);
                
                if ($meeting['success']) {
                    $meetingUrl = $meeting['meetingUrl'];
                } else {
                    return $this->json([
                        'success' => false,
                        'error' => 'Failed to create test meeting: ' . $meeting['error']
                    ], 500);
                }
            }

            // Send WhatsApp message
            $result = $whatsAppService->sendMeetingLink(
                $phoneNumber,
                $session->getPlayer()->getNickname(),
                $meetingUrl,
                $user->getUsername(),
                $session->getScheduledAt()
            );

            if ($result['success']) {
                return $this->json([
                    'success' => true,
                    'message' => 'WhatsApp message sent successfully to ' . $phoneNumber,
                    'messageId' => $result['messageId'] ?? null,
                    'status' => $result['status'] ?? 'queued'
                ]);
            } else {
                return $this->json([
                    'success' => false,
                    'error' => $result['error']
                ], 500);
            }

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'WhatsApp service error: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/session/{id}/send-test-sms', name: 'app_coach_session_send_test_sms', methods: ['POST'])]
    public function sendTestSms(
        CoachingSession $session, 
        Request $request,
        SmsService $smsService,
        JitsiService $jitsiService
    ): Response {
        if (!$this->isGranted('ROLE_COACH')) {
            throw $this->createAccessDeniedException();
        }

        $user = $this->getUser();
        if (!$user || !$user->getCoach() || $session->getCoach() !== $user->getCoach()) {
            throw $this->createAccessDeniedException('You can only send test SMS for your own sessions.');
        }

        $phoneNumber = $request->request->get('phone_number');
        
        if (empty($phoneNumber)) {
            return $this->json([
                'success' => false,
                'error' => 'Phone number is required'
            ], 400);
        }

        try {
            // Check if meeting exists, if not create one for testing
            $meetingUrl = $session->getMeetingUrl();
            if (!$meetingUrl || !$session->isMeetingActive()) {
                // Create a test meeting URL
                $roomName = 'test-' . $session->getId() . '-' . time();
                $meeting = $jitsiService->createMeeting($roomName);
                
                if ($meeting['success']) {
                    $meetingUrl = $meeting['meetingUrl'];
                } else {
                    return $this->json([
                        'success' => false,
                        'error' => 'Failed to create test meeting: ' . $meeting['error']
                    ], 500);
                }
            }

            // Send test SMS
            $result = $smsService->sendMeetingLink(
                $phoneNumber,
                $session->getPlayer()->getNickname(),
                $meetingUrl,
                $user->getUsername(),
                $session->getScheduledAt()
            );

            if ($result['success']) {
                return $this->json([
                    'success' => true,
                    'message' => 'Test SMS sent successfully to ' . $phoneNumber,
                    'messageId' => $result['messageId'] ?? null
                ]);
            } else {
                return $this->json([
                    'success' => false,
                    'error' => $result['error']
                ], 500);
            }

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'SMS service error: ' . $e->getMessage()
            ], 500);
        }
    }
}
