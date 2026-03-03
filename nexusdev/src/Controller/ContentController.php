<?php

namespace App\Controller;

use App\Entity\Content;
use App\Form\ContentType;
use App\Repository\ContentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/BContent')]
final class ContentController extends AbstractController
{
    #[Route(name: 'app_content_back', methods: ['GET', 'POST'])]
    public function back(
        Request $request,
        ContentRepository $contentRepository,
        EntityManagerInterface $entityManager,
        PaginatorInterface $paginator
    ): Response {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('You must be logged in.');
        }

        $qb = $contentRepository->createQueryBuilder('c');

        $search = $request->query->get('search');
        if ($search) {
            $qb->andWhere('c.title LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        // Sorting
        $sort = $request->query->get('sort', 'id');
        $direction = $request->query->get('direction', 'ASC');
        
        $allowedSorts = ['id', 'title', 'type', 'createdAt'];
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
            1
        );

        $isCoachMode = $this->isGranted('ROLE_COACH') && !$this->isGranted('ROLE_ADMIN');

        $contentId = $request->query->getInt('id', 0);
        if ($contentId > 0) {
            $content = $contentRepository->createQueryBuilder('c')
                ->leftJoin('c.comments', 'cm')
                ->addSelect('cm')
                ->leftJoin('cm.author', 'cma')
                ->addSelect('cma')
                ->select('c, cm, cma') // Fix select statement
                ->where('c.id = :id')
                ->setParameter('id', $contentId)
                ->getQuery()
                ->getOneOrNullResult();
            
            if (!$content) {
                throw $this->createNotFoundException('Content not found');
            }

            if ($isCoachMode && $content->getAuthor() !== $user) {
                throw $this->createAccessDeniedException('You can only edit your own guides.');
            }
        } else {
            $content = new Content();
            if ($isCoachMode) {
                $content->setAuthor($user);
            }
        }

        $form = $this->createForm(ContentType::class, $content);
        if ($isCoachMode) {
            $form->remove('author');
        }
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $isNew = $content->getId() === null;
            if ($isCoachMode) {
                $content->setAuthor($user);
            }
            
            // Handle image file upload
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $uploadsDir = $this->getParameter('kernel.project_dir').'/public/uploads/content';
                
                if (!is_dir($uploadsDir)) {
                    mkdir($uploadsDir, 0775, true);
                }
                
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = preg_replace('/[^a-zA-Z0-9_-]/', '-', $originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();
                
                $imageFile->move($uploadsDir, $newFilename);
                $content->setImage('/uploads/content/'.$newFilename);
            }
            
            if ($isNew) {
                $entityManager->persist($content);
            }
            $entityManager->flush();
            $this->addFlash('success', $isNew ? 'Content created successfully.' : 'Content updated successfully.');
            return $this->redirectToRoute('app_content_back', [], Response::HTTP_SEE_OTHER);
        }

        $template = $isCoachMode ? 'coach/content_back.html.twig' : 'content/back.html.twig';

        return $this->render($template, [
            'pagination' => $pagination,
            'form' => $form,
            'editing' => $content->getId() !== null,
            'currentContent' => $content,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_content_delete', methods: ['POST'])]
    public function delete(Request $request, Content $content, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$content->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($content);
            $entityManager->flush();
            $this->addFlash('success', 'Content deleted successfully.');
        }

        return $this->redirectToRoute('app_content_back', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/transcribe-temp', name: 'app_content_transcribe_temp', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function transcribeTemp(Request $request, HttpClientInterface $httpClient): JsonResponse
    {
        $audio = $request->files->get('audio');
        if (!$audio) {
            return $this->json(['error' => 'Missing audio file.'], 400);
        }

        $apiKey = (string) $this->getParameter('assemblyai.api_key');
        if ($apiKey === '' || str_contains($apiKey, '%env(')) {
            return $this->json(['error' => 'AssemblyAI API key is not configured.'], 500);
        }

        try {
            $uploadResp = $httpClient->request('POST', 'https://api.assemblyai.com/v2/upload', [
                'headers' => [
                    'authorization' => $apiKey,
                ],
                'body' => fopen($audio->getPathname(), 'rb'),
            ]);
            $uploadData = $uploadResp->toArray(false);
            $uploadUrl = $uploadData['upload_url'] ?? null;
            if (!$uploadUrl) {
                return $this->json(['error' => 'Upload failed.'], 502);
            }

            $transcribeResp = $httpClient->request('POST', 'https://api.assemblyai.com/v2/transcript', [
                'headers' => [
                    'authorization' => $apiKey,
                    'content-type' => 'application/json',
                ],
                'json' => [
                    'audio_url' => $uploadUrl,
                ],
            ]);
            if ($transcribeResp->getStatusCode() >= 400) {
                return $this->json([
                    'error' => 'Transcript creation failed.',
                    'statusCode' => $transcribeResp->getStatusCode(),
                    'details' => $transcribeResp->getContent(false),
                ], 502);
            }

            $transcribeData = $transcribeResp->toArray(false);
            $transcriptId = $transcribeData['id'] ?? null;
            if (!$transcriptId) {
                return $this->json([
                    'error' => 'Transcript creation failed.',
                    'statusCode' => $transcribeResp->getStatusCode(),
                    'details' => $transcribeResp->getContent(false),
                ], 502);
            }

            $status = 'queued';
            $text = null;
            $error = null;

            $deadline = microtime(true) + 25;
            while (microtime(true) < $deadline) {
                usleep(900000);
                $pollResp = $httpClient->request('GET', 'https://api.assemblyai.com/v2/transcript/'.$transcriptId, [
                    'headers' => [
                        'authorization' => $apiKey,
                    ],
                ]);
                $pollData = $pollResp->toArray(false);
                $status = $pollData['status'] ?? $status;
                if ($status === 'completed') {
                    $text = $pollData['text'] ?? '';
                    break;
                }
                if ($status === 'error') {
                    $error = $pollData['error'] ?? 'Transcription failed.';
                    break;
                }
            }

            if ($status !== 'completed' || $text === null) {
                return $this->json([
                    'status' => $status,
                    'error' => $error,
                    'transcriptId' => $transcriptId,
                ], 202);
            }

            return $this->json([
                'status' => 'completed',
                'text' => $text,
            ]);
        } catch (\Throwable $e) {
            return $this->json(['error' => 'Transcription error: '.$e->getMessage()], 500);
        }
    }

    #[Route('/{id}/transcribe', name: 'app_content_transcribe', requirements: ['id' => '\\d+'], methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function transcribeGuide(
        Content $content,
        Request $request,
        EntityManagerInterface $entityManager,
        HttpClientInterface $httpClient
    ): JsonResponse {
        if ($content->getType() !== 'GUIDE') {
            return $this->json(['error' => 'Transcription is only supported for guides.'], 400);
        }

        $user = $this->getUser();
        $isCoachMode = $this->isGranted('ROLE_COACH') && !$this->isGranted('ROLE_ADMIN');
        if ($isCoachMode && $content->getAuthor() !== $user) {
            return $this->json(['error' => 'You can only transcribe your own guides.'], 403);
        }

        $audio = $request->files->get('audio');
        if (!$audio) {
            return $this->json(['error' => 'Missing audio file.'], 400);
        }

        $apiKey = (string) $this->getParameter('assemblyai.api_key');
        if ($apiKey === '' || str_contains($apiKey, '%env(')) {
            return $this->json(['error' => 'AssemblyAI API key is not configured.'], 500);
        }

        try {
            $uploadResp = $httpClient->request('POST', 'https://api.assemblyai.com/v2/upload', [
                'headers' => [
                    'authorization' => $apiKey,
                ],
                'body' => fopen($audio->getPathname(), 'rb'),
            ]);
            $uploadData = $uploadResp->toArray(false);
            $uploadUrl = $uploadData['upload_url'] ?? null;
            if (!$uploadUrl) {
                return $this->json(['error' => 'Upload failed.'], 502);
            }

            $transcribeResp = $httpClient->request('POST', 'https://api.assemblyai.com/v2/transcript', [
                'headers' => [
                    'authorization' => $apiKey,
                    'content-type' => 'application/json',
                ],
                'json' => [
                    'audio_url' => $uploadUrl,
                ],
            ]);
            if ($transcribeResp->getStatusCode() >= 400) {
                return $this->json([
                    'error' => 'Transcript creation failed.',
                    'statusCode' => $transcribeResp->getStatusCode(),
                    'details' => $transcribeResp->getContent(false),
                ], 502);
            }

            $transcribeData = $transcribeResp->toArray(false);
            $transcriptId = $transcribeData['id'] ?? null;
            if (!$transcriptId) {
                return $this->json([
                    'error' => 'Transcript creation failed.',
                    'details' => $transcribeData,
                ], 502);
            }

            $status = 'queued';
            $text = null;
            $error = null;

            $deadline = microtime(true) + 25;
            while (microtime(true) < $deadline) {
                usleep(900000);
                $pollResp = $httpClient->request('GET', 'https://api.assemblyai.com/v2/transcript/'.$transcriptId, [
                    'headers' => [
                        'authorization' => $apiKey,
                    ],
                ]);
                $pollData = $pollResp->toArray(false);
                $status = $pollData['status'] ?? $status;
                if ($status === 'completed') {
                    $text = $pollData['text'] ?? '';
                    break;
                }
                if ($status === 'error') {
                    $error = $pollData['error'] ?? 'Transcription failed.';
                    break;
                }
            }

            if ($status !== 'completed' || $text === null) {
                return $this->json([
                    'status' => $status,
                    'error' => $error,
                    'transcriptId' => $transcriptId,
                ], 202);
            }

            $body = $content->getBody();
            $separator = str_contains($body, "\n") ? "\n\n" : "\n\n";
            $content->setBody($body.$separator."--- Transcript ---\n".$text);
            $entityManager->flush();

            return $this->json([
                'status' => 'completed',
                'text' => $text,
            ]);
        } catch (\Throwable $e) {
            return $this->json(['error' => 'Transcription error: '.$e->getMessage()], 500);
        }
    }
}
