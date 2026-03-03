<?php

namespace App\Controller;

use App\Entity\Product;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/BProduct')]
final class ProductController extends AbstractController
{
    #[Route(name: 'app_product_back', methods: ['GET', 'POST'])]
    public function back(
        Request $request,
        ProductRepository $productRepository,
        EntityManagerInterface $entityManager,
        PaginatorInterface $paginator
    ): Response {
        $qb = $productRepository->createQueryBuilder('p')
            ->where('p.deletedAt IS NULL');

        $search = $request->query->get('search');
        if ($search) {
            $qb->andWhere('p.name LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        // Sorting
        $sort = $request->query->get('sort', 'id');
        $direction = $request->query->get('direction', 'ASC');
        
        $allowedSorts = ['id', 'name', 'type', 'quantity', 'price'];
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

        $productId = $request->query->getInt('id', 0);
        if ($productId > 0) {
            $product = $productRepository->find($productId);
            if (!$product) {
                throw $this->createNotFoundException('Product not found');
            }
        } else {
            $product = new Product();
        }

        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('imageFile')->getData();

            if ($imageFile) {
                $uploadsDir = $this->getParameter('kernel.project_dir').'/public/uploads/products';

                if (!is_dir($uploadsDir)) {
                    mkdir($uploadsDir, 0775, true);
                }

                $extension = $imageFile->guessExtension() ?: 'bin';
                $safeName = preg_replace('~[^a-zA-Z0-9_]+~', '-', $product->getName());
                $filename = strtolower(trim($safeName, '-')).'-'.uniqid().'.'.$extension;

                $imageFile->move($uploadsDir, $filename);

                $product->setImagePath($filename);
            }

            $isNew = $product->getId() === null;
            if ($isNew) {
                $entityManager->persist($product);
            }
            $entityManager->flush();

            $this->addFlash('success', $isNew ? 'Product created successfully.' : 'Product updated successfully.');

            return $this->redirectToRoute('app_product_back', [], Response::HTTP_SEE_OTHER);
        }

        $template = $this->isGranted('ROLE_ADMIN') ? 'product/back.html.twig' : 'product/back_org.html.twig';

        return $this->render('product/back.html.twig', [
            'pagination' => $pagination,
            'form' => $form,
            'editing' => $product->getId() !== null,
            'currentProduct' => $product,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_product_delete', methods: ['POST'])]
    public function delete(Request $request, Product $product, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$product->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($product);
            $entityManager->flush();
            $this->addFlash('success', 'Product deleted successfully.');
        }

        return $this->redirectToRoute('app_product_back', [], Response::HTTP_SEE_OTHER);
    }
}
