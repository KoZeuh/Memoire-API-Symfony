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

use Symfony\Component\Validator\Validator\ValidatorInterface;

use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;

#[Route('/product', name: 'app_product_')]
final class ProductController extends AbstractController
{
    #[Route('/', name: 'list', methods: ['GET'])]
    public function list(ProductRepository $productRepository, Request $request, RateLimiterFactoryInterface $productListRouteLimiter): JsonResponse
    {
        $limiter = $productListRouteLimiter->create($request->getClientIp());

        if (false === $limiter->consume(1)->isAccepted()) {
            return $this->json(['error' => 'Trop de requêtes. Veuillez réessayer plus tard.'], Response::HTTP_TOO_MANY_REQUESTS);
        }

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

        return $this->json($data, Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Product $product): JsonResponse
    {
        if (!$product) {
            return $this->json(['error' => 'Produit non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $data = [
            'id' => $product->getId(),
            'name' => $product->getName(),
            'description' => $product->getDescription(),
            'price' => $product->getPrice(),
        ];

        return $this->json($data, Response::HTTP_OK);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function create(Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator, RateLimiterFactoryInterface $createRouteLimiter): JsonResponse
    {
        $limiter = $createRouteLimiter->create($request->getClientIp());

        if (false === $limiter->consume(1)->isAccepted()) {
            return $this->json(['error' => 'Trop de requêtes. Veuillez réessayer plus tard.'], Response::HTTP_TOO_MANY_REQUESTS);
        }
        
        $payload = $request->getPayload();

        $product = new Product();
        $product->setName($payload->get('name'));
        $product->setDescription($payload->get('description'));
        $product->setPrice($payload->get('price'));
        $product->setCode($payload->get('code'));

        $errors = $validator->validate($product);

        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }

            return $this->json([
                'error' => 'Validation échouée',
                'violations' => $errorMessages
            ], Response::HTTP_BAD_REQUEST);
        }

        $entityManager->persist($product);
        $entityManager->flush();

        return $this->json([
            'message' => 'Produit créé avec succès',
            'id' => $product->getId()
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN')]
    public function update(Product $product, Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator): JsonResponse
    {
        if (!$product) {
            return $this->json(['error' => 'Produit non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $payload = $request->getPayload();

        if ($payload->has('name')) {
            $product->setName($payload->getString('name'));
        }
        if ($payload->has('description')) {
            $product->setDescription($payload->getString('description'));
        }
        if ($payload->has('price')) {
            $product->setPrice($payload->get('price'));
        }
        if ($payload->has('code')) {
            $product->setCode($payload->getString('code'));
        }

        $errors = $validator->validate($product);

        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }

            return $this->json([
                'error' => 'Validation échouée',
                'violations' => $errorMessages
            ], Response::HTTP_BAD_REQUEST);
        }

        $entityManager->flush();

        return $this->json(['message' => 'Produit mis à jour avec succès'], Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function delete(Product $product, EntityManagerInterface $entityManager): JsonResponse
    {
        if (!$product) {
            return $this->json(['error' => 'Produit non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $entityManager->remove($product);
        $entityManager->flush();

        return $this->json(['message' => 'Produit supprimé avec succès'], Response::HTTP_OK);
    }
}
