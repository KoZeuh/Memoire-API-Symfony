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

use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Security\Voter\ProductVoter;

#[Route('/api/products')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class ProductController extends AbstractController
{
    #[Route('', methods: ['GET'])]
    public function list(ProductRepository $productRepository): JsonResponse
    {
        $products = $productRepository->findAll();
        $data = array_map(function(Product $product) {
            return [
                'id' => $product->getId(),
                'name' => $product->getName(),
                'description' => $product->getDescription(),
                'price' => $product->getPrice(),
            ];
        }, $products);
        return $this->json($data);
    }

    #[Route('', methods: ['POST'])]
    #[IsGranted('PRODUCT_CREATE', subject: 'product')]
    public function create(
        Request $request,
        EntityManagerInterface $em,
        ValidatorInterface $validator
    ): JsonResponse {
        try {
            $data = json_decode($request->getContent(), true);
        
            $product = new Product();
            $product->setName($data['name'] ?? null);
            $product->setDescription($data['description'] ?? null);
            $product->setPrice((float)$data['price']);

            // Validation de l'entité
            $errors = $validator->validate($product);

            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[$error->getPropertyPath()] = $error->getMessage();
                }

                return $this->json([
                    'message' => 'Validation échouée',
                    'violations' => $errorMessages
                ], 400);
            }

            $em->persist($product);
            $em->flush();

            return $this->json($product, 201);
        } catch (\Exception $e) {
            return $this->json([
                'message' => 'Erreur lors de la création du produit'
            ], 400);
        }
    }

    #[Route('/{id}', methods: ['PUT'])]
    #[IsGranted('PRODUCT_EDIT', subject: 'product')]
    public function edit(
        Product $product,
        Request $request,
        EntityManagerInterface $em,
        ValidatorInterface $validator
    ): JsonResponse {
        try {
            $data = json_decode($request->getContent(), true);
        
            $product->setName($data['name'] ?? $product->getName());
            $product->setDescription($data['description'] ?? $product->getDescription());
            if (isset($data['price'])) {
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
                    'message' => 'Validation échouée',
                    'violations' => $errorMessages
                ], 400);
            }

            $em->flush();

            return $this->json([
                'message' => 'Produit mis à jour avec succès'
            ], 200);
        } catch (\Exception $e) {
            return $this->json([
                'message' => 'Erreur lors de la mise à jour du produit'
            ], 400);
        }
    }

    #[Route('/{id}', methods: ['DELETE'])]
    #[IsGranted('PRODUCT_DELETE', subject: 'product')]
    public function delete(Product $product, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($product);
        $em->flush();

        return $this->json([
            'message' => 'Produit supprimé avec succès'
        ], 200);
    }
}
