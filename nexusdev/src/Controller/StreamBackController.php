<?php

namespace App\Controller;

use App\Entity\Stream;
use App\Form\StreamType;
use App\Repository\StreamRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/BStream')]
final class StreamBackController extends AbstractController
{
    #[Route(name: 'app_stream_back', methods: ['GET', 'POST'])]
    public function back(
        Request $request,
        StreamRepository $streamRepository,
        EntityManagerInterface $entityManager,
        PaginatorInterface $paginator
    ): Response {
        $qb = $streamRepository->createQueryBuilder('s')
            ->leftJoin('s.player', 'p')
            ->addSelect('p');

        $search = $request->query->get('search');
        if ($search) {
            $qb->andWhere('s.title LIKE :search OR p.nickname LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        $pagination = $paginator->paginate(
            $qb,
            $request->query->getInt('page', 1),
            10,
            ['sortable' => false]
        );

        $streamId = $request->query->getInt('id', 0);
        if ($streamId > 0) {
            $stream = $streamRepository->find($streamId);
            if (!$stream) {
                throw $this->createNotFoundException('Stream not found');
            }
        } else {
            $stream = new Stream();
        }

        $form = $this->createForm(StreamType::class, $stream);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $isNew = $stream->getId() === null;
            if ($isNew) {
                $entityManager->persist($stream);
            }
            $entityManager->flush();

            $this->addFlash('success', $isNew ? 'Stream created successfully.' : 'Stream updated successfully.');

            return $this->redirectToRoute('app_stream_back', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('stream/back.html.twig', [
            'pagination' => $pagination,
            'form' => $form,
            'editing' => $stream->getId() !== null,
            'currentStream' => $stream,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_stream_delete', methods: ['POST'])]
    public function delete(Request $request, Stream $stream, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$stream->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($stream);
            $entityManager->flush();
            $this->addFlash('success', 'Stream deleted successfully.');
        }

        return $this->redirectToRoute('app_stream_back', [], Response::HTTP_SEE_OTHER);
    }
}
