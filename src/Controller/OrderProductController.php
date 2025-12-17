<?php

namespace App\Controller;

use App\Dto\OrderProductDto;
use App\Entity\OrderProduct;
use App\Repository\OrderProductRepository;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Serializer\SerializerInterface;



#[Route('/order-product', name: 'app_order_product_')]
final class OrderProductController extends AbstractController
{
    #[Route('/', name: 'list', methods: ['GET'])]
    public function list(OrderProductRepository $orderProductRepository): JsonResponse
    {
        $orderProducts = $orderProductRepository->findAll();

        return $this->json($orderProducts, Response::HTTP_OK, [], [
            'groups' => ['order:item']
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(OrderProduct $orderProduct): JsonResponse
    {
        return $this->json($orderProduct, Response::HTTP_OK, [], [
            'groups' => ['order:item']
        ]);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(
        #[MapRequestPayload] OrderProductDto $dto,
        EntityManagerInterface $entityManager,
        OrderRepository $orderRepository,
        ProductRepository $productRepository
    ): JsonResponse {
        $order = $orderRepository->find($dto->orderId);
        if (!$order) return $this->json(['error' => 'Commande non trouvée'], Response::HTTP_NOT_FOUND);

        $product = $productRepository->find($dto->productId);
        if (!$product) return $this->json(['error' => 'Produit non trouvé'], Response::HTTP_NOT_FOUND);

        $orderProduct = new OrderProduct();
        $orderProduct->setOrders($order);
        $orderProduct->setProduct($product);
        $orderProduct->setQuantity($dto->quantity);

        $entityManager->persist($orderProduct);
        $entityManager->flush();


        return $this->json([
            'message' => 'Produit de commande créée avec succès',
            'id' => $orderProduct->getId()
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(
        OrderProduct $orderProduct,
        Request $request,
        SerializerInterface $serializer,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator
    ): JsonResponse {

        $serializer->deserialize(
            $request->getContent(),
            OrderProduct::class,
            'json',
            [
                'object_to_populate' => $orderProduct,
                'groups' => ['order:item:update']
            ]
        );

       $errors = $validator->validate($orderProduct);
        if (count($errors) > 0) {
            return $this->json($errors, Response::HTTP_BAD_REQUEST);
        }

        $entityManager->flush();

        return $this->json(['message' => 'Ligne de commande mise à jour'], Response::HTTP_OK);
    }
}
