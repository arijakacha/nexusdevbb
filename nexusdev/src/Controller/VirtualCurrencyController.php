<?php

namespace App\Controller;

use App\Entity\VirtualCurrency;
use App\Form\VirtualCurrencyType;
use App\Repository\VirtualCurrencyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/BVirtualCurrency')]
final class VirtualCurrencyController extends AbstractController
{
    #[Route(name: 'app_virtual_currency_back', methods: ['GET', 'POST'])]
    public function back(
        Request $request,
        VirtualCurrencyRepository $virtualCurrencyRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $virtualCurrencies = $virtualCurrencyRepository->findAll();

        $currencyId = $request->query->getInt('id', 0);
        if ($currencyId > 0) {
            $virtualCurrency = $virtualCurrencyRepository->find($currencyId);
            if (!$virtualCurrency) {
                throw $this->createNotFoundException('Virtual currency not found');
            }
        } else {
            $virtualCurrency = new VirtualCurrency();
        }

        $form = $this->createForm(VirtualCurrencyType::class, $virtualCurrency);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $isNew = $virtualCurrency->getId() === null;
            if ($isNew) {
                $entityManager->persist($virtualCurrency);
            }
            $entityManager->flush();

            $this->addFlash('success', $isNew ? 'Virtual currency created successfully.' : 'Virtual currency updated successfully.');

            return $this->redirectToRoute('app_virtual_currency_back', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('virtual_currency/back.html.twig', [
            'virtualCurrencies' => $virtualCurrencies,
            'form' => $form,
            'editing' => $virtualCurrency->getId() !== null,
            'currentVirtualCurrency' => $virtualCurrency,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_virtual_currency_delete', methods: ['POST'])]
    public function delete(Request $request, VirtualCurrency $virtualCurrency, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$virtualCurrency->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($virtualCurrency);
            $entityManager->flush();
            $this->addFlash('success', 'Virtual currency deleted successfully.');
        }

        return $this->redirectToRoute('app_virtual_currency_back', [], Response::HTTP_SEE_OTHER);
    }
}
