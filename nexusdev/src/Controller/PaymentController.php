<?php

namespace App\Controller;

use App\Entity\Payment;
use App\Form\PaymentType;
use App\Repository\PaymentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/BPayment')]
final class PaymentController extends AbstractController
{
    #[Route(name: 'app_payment_back', methods: ['GET', 'POST'])]
    public function back(
        Request $request,
        PaymentRepository $paymentRepository,
        EntityManagerInterface $entityManager,
        PaginatorInterface $paginator
    ): Response {
        $qb = $paymentRepository->createQueryBuilder('p');

        $search = $request->query->get('search');
        if ($search) {
            $qb->andWhere('p.id LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        // Sorting
        $sort = $request->query->get('sort', 'id');
        $direction = $request->query->get('direction', 'ASC');
        
        $allowedSorts = ['id', 'amount', 'createdAt'];
        $allowedDirections = ['ASC', 'DESC'];
        
        if (!in_array($sort, $allowedSorts)) {
            $sort = 'id';
        }
        if (!in_array(strtoupper($direction), $allowedDirections)) {
            $direction = 'ASC';
        }
        
        $qb->orderBy('p.' . $sort, $direction);

        // Get results manually and create pagination array
        $query = $qb->getQuery();
        $results = $query->getResult();
        
        // Use paginator with array to bypass OrderByWalker
        $pagination = $paginator->paginate(
            $results,
            $request->query->getInt('page', 1),
            10
        );

        $paymentId = $request->query->getInt('id', 0);
        if ($paymentId > 0) {
            $payment = $paymentRepository->find($paymentId);
            if (!$payment) {
                throw $this->createNotFoundException('Payment not found');
            }
        } else {
            $payment = new Payment();
        }

        $form = $this->createForm(PaymentType::class, $payment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $isNew = $payment->getId() === null;
            if ($isNew) {
                $entityManager->persist($payment);
            }
            $entityManager->flush();

            $this->addFlash('success', $isNew ? 'Payment created successfully.' : 'Payment updated successfully.');

            return $this->redirectToRoute('app_payment_back', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('payment/back.html.twig', [
            'pagination' => $pagination,
            'form' => $form,
            'editing' => $payment->getId() !== null,
            'currentPayment' => $payment,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_payment_delete', methods: ['POST'])]
    public function delete(Request $request, Payment $payment, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$payment->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($payment);
            $entityManager->flush();
            $this->addFlash('success', 'Payment deleted successfully.');
        }

        return $this->redirectToRoute('app_payment_back', [], Response::HTTP_SEE_OTHER);
    }
}
