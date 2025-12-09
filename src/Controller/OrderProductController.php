<?php

namespace App\Controller;

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

#[Route('/order-product', name: 'app_order_product_')]
final class OrderProductController extends AbstractController
{
    #[Route('/', name: 'list', methods: ['GET'])]
    public function list(OrderProductRepository $orderProductRepository): JsonResponse
    {
        $orderProducts = $orderProductRepository->findAll();

        $data = [];
        foreach ($orderProducts as $orderProduct) {
            $data[] = [
                'id' => $orderProduct->getId(),
                'quantity' => $orderProduct->getQuantity(),
                'order' => [
                    'id' => $orderProduct->getOrders()->getId(),
                    'status' => $orderProduct->getOrders()->getStatus(),
                ],
                'product' => [
                    'id' => $orderProduct->getProduct()->getId(),
                    'name' => $orderProduct->getProduct()->getName(),
                    'price' => $orderProduct->getProduct()->getPrice(),
                ],
            ];
        };

        return $this->json($data);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(OrderProduct $orderProduct): JsonResponse
    {
        if (!$orderProduct) {
            return $this->json(['error' => 'Produit de commande non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $data = [
            'id' => $orderProduct->getId(),
            'quantity' => $orderProduct->getQuantity(),
            'order' => [
                'id' => $orderProduct->getOrders()->getId(),
                'status' => $orderProduct->getOrders()->getStatus(),
            ],
            'product' => [
                'id' => $orderProduct->getProduct()->getId(),
                'name' => $orderProduct->getProduct()->getName(),
                'price' => $orderProduct->getProduct()->getPrice(),
            ],
        ];

        return $this->json($data, Response::HTTP_OK);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager, OrderRepository $orderRepository, ProductRepository $productRepository): JsonResponse
    {
        $payload = $request->getPayload();

        if (!$payload->has('order_id') || !$payload->has('product_id') || !$payload->has('quantity')) {
            return $this->json(['error' => 'Champs requis manquants'], Response::HTTP_BAD_REQUEST);
        }

        $order = $orderRepository->find($payload->get('order_id'));
        if (!$order) {
            return $this->json(['error' => 'Commande non trouvée'], Response::HTTP_NOT_FOUND);
        }

        $product = $productRepository->find($payload->get('product_id'));
        if (!$product) {
            return $this->json(['error' => 'Produit non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $orderProduct = new OrderProduct();
        $orderProduct->setOrders($order);
        $orderProduct->setProduct($product);
        $orderProduct->setQuantity($payload->getInt('quantity'));

        $entityManager->persist($orderProduct);
        $entityManager->flush();

        return $this->json([
            'message' => 'Produit de commande créé avec succès',
            'id' => $orderProduct->getId()
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(OrderProduct $orderProduct, Request $request, EntityManagerInterface $entityManager, OrderRepository $orderRepository, ProductRepository $productRepository): JsonResponse
    {
        if (!$orderProduct) {
            return $this->json(['error' => 'Produit de commande non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $payload = $request->getPayload();

        if ($payload->has('order_id')) {
            $order = $orderRepository->find($payload->get('order_id'));
            if (!$order) {
                return $this->json(['error' => 'Commande non trouvée'], Response::HTTP_NOT_FOUND);
            }
            $orderProduct->setOrders($order);
        }
        if ($payload->has('product_id')) {
            $product = $productRepository->find($payload->get('product_id'));
            if (!$product) {
                return $this->json(['error' => 'Produit non trouvé'], Response::HTTP_NOT_FOUND);
            }
            $orderProduct->setProduct($product);
        }
        if ($payload->has('quantity')) {
            $orderProduct->setQuantity($payload->getInt('quantity'));
        }

        $entityManager->flush();

        return $this->json(['message' => 'Produit de commande mis à jour avec succès'], Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(OrderProduct $orderProduct, EntityManagerInterface $entityManager): JsonResponse
    {
        if (!$orderProduct) {
            return $this->json(['error' => 'Produit de commande non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $entityManager->remove($orderProduct);
        $entityManager->flush();

        return $this->json(['message' => 'Produit de commande supprimé avec succès'], Response::HTTP_OK);
    }
}
