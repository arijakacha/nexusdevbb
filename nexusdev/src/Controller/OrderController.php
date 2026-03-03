<?php

namespace App\Controller;

use App\Entity\Order;
use App\Form\OrderType;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/BOrder')]
final class OrderController extends AbstractController
{
    #[Route(name: 'app_order_back', methods: ['GET', 'POST'])]
    public function back(
        Request $request,
        OrderRepository $orderRepository,
        EntityManagerInterface $entityManager,
        PaginatorInterface $paginator
    ): Response {
        $qb = $orderRepository->createQueryBuilder('o');

        $search = $request->query->get('search');
        if ($search) {
            $qb->andWhere('o.id LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        // Sorting
        $sort = $request->query->get('sort', 'id');
        $direction = $request->query->get('direction', 'ASC');
        
        $allowedSorts = ['id', 'createdAt', 'total'];
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

        $orderId = $request->query->getInt('id', 0);
        if ($orderId > 0) {
            $order = $orderRepository->find($orderId);
            if (!$order) {
                throw $this->createNotFoundException('Order not found');
            }
        } else {
            $order = new Order();
        }

        $form = $this->createForm(OrderType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $isNew = $order->getId() === null;
            if ($isNew) {
                $entityManager->persist($order);
            }
            $entityManager->flush();

            $this->addFlash('success', $isNew ? 'Order created successfully.' : 'Order updated successfully.');

            return $this->redirectToRoute('app_order_back', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('order/back.html.twig', [
            'pagination' => $pagination,
            'form' => $form,
            'editing' => $order->getId() !== null,
            'currentOrder' => $order,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_order_delete', methods: ['POST'])]
    public function delete(Request $request, Order $order, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$order->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($order);
            $entityManager->flush();
            $this->addFlash('success', 'Order deleted successfully.');
        }

        return $this->redirectToRoute('app_order_back', [], Response::HTTP_SEE_OTHER);
    }
}
