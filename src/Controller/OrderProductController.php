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
    public function show(int $id, OrderProductRepository $orderProductRepository): JsonResponse
    {
        $orderProduct = $orderProductRepository->find($id);

        if (!$orderProduct) {
            return $this->json(['error' => 'OrderProduct not found'], Response::HTTP_NOT_FOUND);
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

        return $this->json($data);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager, OrderRepository $orderRepository, ProductRepository $productRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['order_id']) || !isset($data['product_id']) || !isset($data['quantity'])) {
            return $this->json(['error' => 'Missing required fields'], Response::HTTP_BAD_REQUEST);
        }

        $order = $orderRepository->find($data['order_id']);
        if (!$order) {
            return $this->json(['error' => 'Order not found'], Response::HTTP_NOT_FOUND);
        }

        $product = $productRepository->find($data['product_id']);
        if (!$product) {
            return $this->json(['error' => 'Product not found'], Response::HTTP_NOT_FOUND);
        }

        $orderProduct = new OrderProduct();
        $orderProduct->setOrders($order);
        $orderProduct->setProduct($product);
        $orderProduct->setQuantity($data['quantity']);

        $entityManager->persist($orderProduct);
        $entityManager->flush();

        return $this->json([
            'message' => 'OrderProduct created successfully',
            'id' => $orderProduct->getId()
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(int $id, Request $request, OrderProductRepository $orderProductRepository, EntityManagerInterface $entityManager, OrderRepository $orderRepository, ProductRepository $productRepository): JsonResponse
    {
        $orderProduct = $orderProductRepository->find($id);

        if (!$orderProduct) {
            return $this->json(['error' => 'OrderProduct not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['order_id'])) {
            $order = $orderRepository->find($data['order_id']);
            if (!$order) {
                return $this->json(['error' => 'Order not found'], Response::HTTP_NOT_FOUND);
            }
            $orderProduct->setOrders($order);
        }
        if (isset($data['product_id'])) {
            $product = $productRepository->find($data['product_id']);
            if (!$product) {
                return $this->json(['error' => 'Product not found'], Response::HTTP_NOT_FOUND);
            }
            $orderProduct->setProduct($product);
        }
        if (isset($data['quantity'])) {
            $orderProduct->setQuantity($data['quantity']);
        }

        $entityManager->flush();

        return $this->json(['message' => 'OrderProduct updated successfully']);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id, OrderProductRepository $orderProductRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $orderProduct = $orderProductRepository->find($id);

        if (!$orderProduct) {
            return $this->json(['error' => 'OrderProduct not found'], Response::HTTP_NOT_FOUND);
        }

        $entityManager->remove($orderProduct);
        $entityManager->flush();

        return $this->json(['message' => 'OrderProduct deleted successfully'], Response::HTTP_OK);
    }
}
