<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/products')]
class ProductController extends AbstractController
{
    #[Route('', methods: ['GET'])]
    public function list(ProductRepository $repo): JsonResponse
    {
        $products = $repo->findAll();
        return $this->json($products);
    }

    #[Route('', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $em,
        ValidatorInterface $validator
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        
        // Vérifier que le JSON est valide
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->json([
                'error' => 'JSON invalide',
                'message' => json_last_error_msg()
            ], 400);
        }
        
        // Vérifier que $data est un tableau
        if (!is_array($data)) {
            return $this->json([
                'error' => 'Le corps de la requête doit être un objet JSON'
            ], 400);
        }
        
        $product = new Product();
        $product->setName($data['name'] ?? null);
        $product->setDescription($data['description'] ?? null);
        
        // Gérer le prix avec validation du type
        if (isset($data['price'])) {
            if (!is_numeric($data['price'])) {
                return $this->json([
                    'error' => 'Validation échouée',
                    'violations' => ['price' => 'Le prix doit être un nombre']
                ], 400);
            }
            $product->setPrice((float)$data['price']);
        } else {
            $product->setPrice(null);
        }

        // Validation de l'entité
        $errors = $validator->validate($product);

        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }

            return $this->json([
                'error' => 'Validation échouée',
                'violations' => $errorMessages
            ], 400);
        }

        $em->persist($product);
        $em->flush();

        return $this->json($product, 201);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function show(Product $product): JsonResponse
    {
        return $this->json($product);
    }

    #[Route('/{id}', methods: ['PUT', 'PATCH'])]
    public function update(
        Request $request, 
        Product $product, 
        EntityManagerInterface $em,
        ValidatorInterface $validator
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        // Vérifier que le JSON est valide
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->json([
                'error' => 'JSON invalide',
                'message' => json_last_error_msg()
            ], 400);
        }
        
        // Vérifier que $data est un tableau
        if (!is_array($data)) {
            return $this->json([
                'error' => 'Le corps de la requête doit être un objet JSON'
            ], 400);
        }

        if (isset($data['name'])) {
            $product->setName($data['name']);
        }
        
        if (isset($data['description'])) {
            $product->setDescription($data['description']);
        }
        
        if (isset($data['price'])) {
            if (!is_numeric($data['price'])) {
                return $this->json([
                    'error' => 'Validation échouée',
                    'violations' => ['price' => 'Le prix doit être un nombre']
                ], 400);
            }
            $product->setPrice((float)$data['price']);
        }

        // Validation de l'entité
        $errors = $validator->validate($product);

        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }

            return $this->json([
                'error' => 'Validation échouée',
                'violations' => $errorMessages
            ], 400);
        }

        $em->persist($product);
        $em->flush();

        return $this->json($product);
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(Product $product, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($product);
        $em->flush();

        return $this->json(null, 204);
    }
}
