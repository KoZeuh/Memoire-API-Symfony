<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderProduct;
use App\Repository\UserRepository;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

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

        return $this->json($data, Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Order $order): JsonResponse
    {
        if (!$order) {
            return $this->json(['error' => 'Commande non trouvée'], Response::HTTP_NOT_FOUND);
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

        return $this->json($data, Response::HTTP_OK);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager, UserRepository $userRepository, ProductRepository $productRepository): JsonResponse
    {
        $payload = $request->getPayload();
        $user = $userRepository->find($payload->get('userId'));

        if (!$user) {
            return $this->json(['message' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $order = new Order();
        $order->setUser($user);

        foreach ($payload->all()['products'] as $index => $productData) {
            $product = $productRepository->find($productData['id']);

            if (!$product) {
                return $this->json(['error' => 'Produit non trouvé'], Response::HTTP_NOT_FOUND);
            }

            $orderProduct = new OrderProduct();
            $orderProduct->setOrders($order);
            $orderProduct->setProduct($product);
            $orderProduct->setQuantity((int)$productData['quantity']);

            $order->addOrderProduct($orderProduct);

            $entityManager->persist($orderProduct);
        }

        $entityManager->persist($order);
        $entityManager->flush();

        return $this->json([
            'message' => 'Commande créée avec succès',
            'orderId' => $order->getId()
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(Order $order, Request $request, EntityManagerInterface $entityManager, UserRepository $userRepository): JsonResponse
    {
        if (!$order) {
            return $this->json(['error' => 'Commande non trouvée'], Response::HTTP_NOT_FOUND);
        }

        $payload = $request->getPayload();

        if ($payload->has('user_id')) {
            $user = $userRepository->find($payload->get('user_id'));
            if (!$user) {
                return $this->json(['error' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
            }
            $order->setUser($user);
        }
        if ($payload->has('status')) {
            $order->setStatus($payload->getString('status'));
        }

        $entityManager->flush();

        return $this->json(['message' => 'Commande mise à jour avec succès'], Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(Order $order, OrderRepository $orderRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        if (!$order) {
            return $this->json(['error' => 'Commande non trouvée'], Response::HTTP_NOT_FOUND);
        }

        $entityManager->remove($order);
        $entityManager->flush();

        return $this->json(['message' => 'Commande supprimée avec succès'], Response::HTTP_OK);
    }
}
