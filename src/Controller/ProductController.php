<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Security\Voter\ProductVoter;

#[Route('/product', name: 'app_product_')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class ProductController extends AbstractController
{
    #[Route('/', name: 'list', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function list(ProductRepository $productRepository): JsonResponse
    {
        $products = $productRepository->findAll();

        $data = [];
        foreach ($products as $product) {
            $data[] = [
                'id' => $product->getId(),
                'name' => $product->getName(),
                'description' => $product->getDescription(),
                'price' => $product->getPrice(),
            ];
        };

        return $this->json($data);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Product $product): JsonResponse
    {
        if (!$product) {
            return $this->json(['error' => 'Product not found'], Response::HTTP_NOT_FOUND);
        }

        $data = [
            'id' => $product->getId(),
            'name' => $product->getName(),
            'description' => $product->getDescription(),
            'price' => $product->getPrice(),
        ];

        return $this->json($data);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    #[IsGranted('PRODUCT_CREATE', subject: 'product')]
    public function create(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['name']) || !isset($data['price'])) {
            return $this->json(['error' => 'Missing required fields'], Response::HTTP_BAD_REQUEST);
        }

        $product = new Product();
        $product->setName($data['name']);
        $product->setDescription($data['description'] ?? null);
        $product->setPrice($data['price']);

        $entityManager->persist($product);
        $entityManager->flush();

        return $this->json([
            'message' => 'Product created successfully',
            'id' => $product->getId()
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    #[IsGranted('PRODUCT_EDIT', subject: 'product')]
    public function update(Product $product, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        if (!$product) {
            return $this->json(['error' => 'Product not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['name'])) {
            $product->setName($data['name']);
        }
        if (isset($data['description'])) {
            $product->setDescription($data['description']);
        }
        if (isset($data['price'])) {
            $product->setPrice($data['price']);
        }

        $entityManager->flush();

        return $this->json(['message' => 'Product updated successfully']);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    #[IsGranted('PRODUCT_DELETE', subject: 'product')]
    public function delete(Product $product, EntityManagerInterface $entityManager): JsonResponse
    {
        if (!$product) {
            return $this->json(['error' => 'Product not found'], Response::HTTP_NOT_FOUND);
        }

        $entityManager->remove($product);
        $entityManager->flush();

        return $this->json(['message' => 'Product deleted successfully'], Response::HTTP_OK);
    }
}
