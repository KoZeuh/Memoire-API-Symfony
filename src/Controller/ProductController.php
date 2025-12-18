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
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Serializer\SerializerInterface;
use App\Dto\ProductCreateDto;
use App\Service\FileUploader;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

#[Route('/product', name: 'app_product_')]
final class ProductController extends AbstractController
{
    #[Route('/test-error', name: 'test', methods: ['GET'])]
    public function testError(): Response
    {
        throw new \RuntimeException("Ceci est une erreur de test !");
    }

    #[Route('/', name: 'list', methods: ['GET'])]
    public function list(ProductRepository $productRepository): JsonResponse
    {
        $products = $productRepository->findAll();

        return $this->json($products, Response::HTTP_OK, [], [
            'groups' => ['product:read']
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Product $product): JsonResponse
    {
        return $this->json($product, Response::HTTP_OK, [], [
            'groups' => ['product:read']
        ]);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function create(
        #[MapRequestPayload] ProductCreateDto $dto,
        Request $request,
        EntityManagerInterface $entityManager,
        FileUploader $fileUploader
    ): JsonResponse {

        $imageFile = $request->files->get('image');
        if ($imageFile) {
            $dto->image = $imageFile;
        }

        $product = new Product();
        $product->setName($dto->name);
        $product->setPrice($dto->price);
        $product->setDescription($dto->description);

         if ($dto->image) {
            $filename = $fileUploader->upload($dto->image);
            $product->setImage($filename);
        }

        $entityManager->persist($product);
        $entityManager->flush();

        return $this->json([
            'message' => 'Produit créé avec succès',
            'id' => $product->getId(),
            'image' => $product->getImage()
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function update(
        Product $product,
        Request $request,
        SerializerInterface $serializer,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
        FileUploader $fileUploader
    ): JsonResponse {

        $imageFile = $request->files->get('image');
        $jsonData = $request->request->get('data');
        
        if ($jsonData) {
            $serializer->deserialize(
                $jsonData,
                Product::class,
                'json',
                ['object_to_populate' => $product]
            );
        }

        if ($imageFile) {
            $filename = $fileUploader->upload($imageFile);
            $product->setImage($filename);
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

        $entityManager->remove($product);
        $entityManager->flush();

        return $this->json(['message' => 'Produit supprimé avec succès'], Response::HTTP_OK);
    }
}
