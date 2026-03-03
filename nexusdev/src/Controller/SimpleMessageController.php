<?php

namespace App\Controller;

use App\Entity\Conversation;
use App\Entity\Message;
use App\Entity\User;
use App\Form\MessageType;
use App\Repository\ConversationRepository;
use App\Repository\MessageRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/simple-messages')]
final class SimpleMessageController extends AbstractController
{
    public function __construct(
        private ConversationRepository $conversationRepository,
        private MessageRepository $messageRepository,
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('', name: 'app_simple_messages')]
    #[IsGranted('ROLE_USER')]
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Handle new conversation
        if ($request->isMethod('POST')) {
            $recipientId = $request->request->get('recipient_id');
            $content = $request->request->get('content');
            
            if ($recipientId && $content) {
                $recipient = $this->userRepository->find($recipientId);
                if ($recipient && $recipient !== $user) {
                    $conversation = $this->conversationRepository->findConversationBetweenUsers($user, $recipient);
                    
                    if (!$conversation) {
                        $conversation = new Conversation();
                        $conversation->addParticipant($user);
                        $conversation->addParticipant($recipient);
                        $this->conversationRepository->save($conversation, true);
                    }
                    
                    $message = new Message($conversation, $user, $content);
                    $this->messageRepository->save($message, true);
                    
                    $this->addFlash('success', 'Message sent!');
                    return $this->redirectToRoute('app_simple_messages');
                }
            }
        }
        
        $conversations = $this->conversationRepository->findConversationsForUser($user);
        $allUsers = $this->userRepository->createQueryBuilder('u')
            ->where('u.id != :currentUserId')
            ->setParameter('currentUserId', $user->getId())
            ->orderBy('u.username', 'ASC')
            ->getQuery()
            ->getResult();

        return $this->render('simple_message/index.html.twig', [
            'conversations' => $conversations,
            'allUsers' => $allUsers,
        ]);
    }

    #[Route('/conversation/{id}', name: 'app_simple_message_conversation')]
    #[IsGranted('ROLE_USER')]
    public function conversation(Conversation $conversation, Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$conversation->getParticipants()->contains($user)) {
            throw $this->createAccessDeniedException('You are not a participant of this conversation.');
        }

        // Handle new message
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('simple_message_send', (string) $request->request->get('_token'))) {
                $this->addFlash('error', 'Invalid request. Please try again.');
                return $this->redirectToRoute('app_simple_message_conversation', ['id' => $conversation->getId()]);
            }
            $content = $request->request->get('content');
            if ($content) {
                $message = new Message($conversation, $user, $content);
                $this->messageRepository->save($message, true);
                $this->addFlash('success', 'Message sent!');
                return $this->redirectToRoute('app_simple_message_conversation', ['id' => $conversation->getId()]);
            }
            $this->addFlash('error', 'Message cannot be empty.');
        }

        $messages = $this->messageRepository->findMessagesInConversation($conversation);
        $this->messageRepository->markMessagesAsReadInConversation($conversation, $user);

        return $this->render('simple_message/conversation.html.twig', [
            'conversation' => $conversation,
            'messages' => $messages,
        ]);
    }

    #[Route('/new/{userId}', name: 'app_simple_message_new')]
    #[IsGranted('ROLE_USER')]
    public function new(int $userId): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $recipient = $this->userRepository->find($userId);

        if (!$recipient) {
            throw $this->createNotFoundException('User not found');
        }

        if ($user === $recipient) {
            throw $this->createAccessDeniedException('You cannot start a conversation with yourself.');
        }

        $conversation = $this->conversationRepository->findConversationBetweenUsers($user, $recipient);

        if (!$conversation) {
            $conversation = new Conversation();
            $conversation->addParticipant($user);
            $conversation->addParticipant($recipient);
            $this->conversationRepository->save($conversation, true);
        }

        return $this->redirectToRoute('app_simple_message_conversation', ['id' => $conversation->getId()]);
    }
}
