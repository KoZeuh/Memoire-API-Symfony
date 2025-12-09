<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderProduct;

use App\Repository\OrderRepository;
use App\Repository\UserRepository;
use App\Repository\ProductRepository;

use Doctrine\ORM\EntityManagerInterface;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/order', name: 'app_order_')]
final class OrderController extends AbstractController
{
    #[Route('/', name: 'list', methods: ['GET'])]
    public function list(OrderRepository $orderRepository): JsonResponse
    {
        $orders = $orderRepository->findAll();

        $data = [];
        foreach ($orders as $order) {
            $data[] = [
                'id' => $order->getId(),
                'status' => $order->getStatus(),
                'user' => [
                    'id' => $order->getUser()->getId(),
                    'email' => $order->getUser()->getEmail(),
                ],
                'orderProducts' => array_map(function ($orderProduct) {
                    return [
                        'id' => $orderProduct->getId(),
                        'quantity' => $orderProduct->getQuantity(),
                        'product' => [
                            'id' => $orderProduct->getProduct()->getId(),
                            'name' => $orderProduct->getProduct()->getName(),
                            'price' => $orderProduct->getProduct()->getPrice(),
                        ],
                    ];
                }, $order->getOrderProducts()->toArray()),
            ];
        };

        return $this->json($data);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Order $order): JsonResponse
    {
        if (!$order) {
            return $this->json(['error' => 'Order not found'], Response::HTTP_NOT_FOUND);
        }

        $data = [
            'id' => $order->getId(),
            'status' => $order->getStatus(),
            'user' => [
                'id' => $order->getUser()->getId(),
                'email' => $order->getUser()->getEmail(),
            ],
            'orderProducts' => array_map(function ($orderProduct) {
                return [
                    'id' => $orderProduct->getId(),
                    'quantity' => $orderProduct->getQuantity(),
                    'product' => [
                        'id' => $orderProduct->getProduct()->getId(),
                        'name' => $orderProduct->getProduct()->getName(),
                        'price' => $orderProduct->getProduct()->getPrice(),
                    ],
                ];
            }, $order->getOrderProducts()->toArray()),
        ];

        return $this->json($data);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator, UserRepository $userRepository, ProductRepository $productRepository): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $user = $userRepository->find($data['userId']);

            if (!$user) {
                return $this->json([
                    'message' => 'Utilisateur non trouvé'
                ], Response::HTTP_NOT_FOUND);
            }

            $order = new Order();
            $order->setUser($user);

            foreach ($data['products'] as $index => $productData) {
                $product = $productRepository->find($productData['id']);

                if (!$product) {
                    return $this->json([
                        'error' => 'Produit non trouvé'
                    ], Response::HTTP_NOT_FOUND);
                }

                $orderProduct = new OrderProduct();
                $orderProduct->setOrders($order);
                $orderProduct->setProduct($product);
                $orderProduct->setQuantity((int)$productData['quantity']);

                $order->addOrderProduct($orderProduct);

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
            }

            $errors = $validator->validate($order);

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

            $entityManager->persist($order);
            $entityManager->flush();

            return $this->json([
                'message' => 'Commande créée avec succès',
                'orderId' => $order->getId()
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json(['message' => 'Erreur lors de la création de la commande', 'error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(Order $order, Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator, UserRepository $userRepository): JsonResponse
    {
        if (!$order) {
            return $this->json(['error' => 'Order not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['user_id'])) {
            $user = $userRepository->find($data['user_id']);
            if (!$user) {
                return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
            }
            $order->setUser($user);
        }
        if (isset($data['status'])) {
            $order->setStatus($data['status']);
        }

        $errors = $validator->validate($order);

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

        return $this->json(['message' => 'Order updated successfully']);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(Order $order, EntityManagerInterface $entityManager): JsonResponse
    {
        if (!$order) {
            return $this->json(['error' => 'Order not found'], Response::HTTP_NOT_FOUND);
        }

        $entityManager->remove($order);
        $entityManager->flush();

        return $this->json(['message' => 'Order deleted successfully'], Response::HTTP_OK);
    }
}
