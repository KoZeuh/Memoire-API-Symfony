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

use Symfony\Component\Validator\Validator\ValidatorInterface;

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
    public function create(Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator, OrderRepository $orderRepository, ProductRepository $productRepository): JsonResponse
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

        $errors = $validator->validate($orderProduct);

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

        $entityManager->persist($orderProduct);
        $entityManager->flush();

        return $this->json([
            'message' => 'OrderProduct created successfully',
            'id' => $orderProduct->getId()
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(OrderProduct $orderProduct, Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator,OrderRepository $orderRepository, ProductRepository $productRepository): JsonResponse
    {
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

        $errors = $validator->validate($orderProduct);

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

        return $this->json(['message' => 'OrderProduct updated successfully']);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(OrderProduct $orderProduct, EntityManagerInterface $entityManager): JsonResponse
    {
        if (!$orderProduct) {
            return $this->json(['error' => 'OrderProduct not found'], Response::HTTP_NOT_FOUND);
        }

        $entityManager->remove($orderProduct);
        $entityManager->flush();

        return $this->json(['message' => 'OrderProduct deleted successfully'], Response::HTTP_OK);
    }
}
