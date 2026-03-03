<?php

namespace App\Controller;

use App\Entity\ProductPurchase;
use App\Form\ProductPurchaseType;
use App\Repository\ProductPurchaseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/BProductPurchase')]
final class ProductPurchaseController extends AbstractController
{
    #[Route(name: 'app_product_purchase_back', methods: ['GET', 'POST'])]
    public function back(
        Request $request,
        ProductPurchaseRepository $productPurchaseRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $purchases = $productPurchaseRepository->findAll();

        $purchaseId = $request->query->getInt('id', 0);
        if ($purchaseId > 0) {
            $purchase = $productPurchaseRepository->find($purchaseId);
            if (!$purchase) {
                throw $this->createNotFoundException('Product purchase not found');
            }
        } else {
            $purchase = new ProductPurchase();
        }

        $form = $this->createForm(ProductPurchaseType::class, $purchase);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $isNew = $purchase->getId() === null;
            if ($isNew) {
                $entityManager->persist($purchase);
            }
            $entityManager->flush();

            $this->addFlash('success', $isNew ? 'Product purchase created successfully.' : 'Product purchase updated successfully.');

            return $this->redirectToRoute('app_product_purchase_back', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('product_purchase/back.html.twig', [
            'purchases' => $purchases,
            'form' => $form,
            'editing' => $purchase->getId() !== null,
            'currentPurchase' => $purchase,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_product_purchase_delete', methods: ['POST'])]
    public function delete(Request $request, ProductPurchase $purchase, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$purchase->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($purchase);
            $entityManager->flush();
            $this->addFlash('success', 'Product purchase deleted successfully.');
        }

        return $this->redirectToRoute('app_product_purchase_back', [], Response::HTTP_SEE_OTHER);
    }
}
