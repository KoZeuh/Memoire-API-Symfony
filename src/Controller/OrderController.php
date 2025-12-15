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

use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/order', name: 'app_order_')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class OrderController extends AbstractController
{
    #[Route('/', name: 'list', methods: ['GET'])]
    #[IsGranted('ORDER_LIST')]
    public function list(OrderRepository $orderRepository): JsonResponse
    {
        $orders = $orderRepository->findAll();

        return $this->json($orders, Response::HTTP_OK, [], [
            'groups' => ['order:read', 'order:item']
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    #[IsGranted('ORDER_SHOW', subject: 'order')]
    public function show(Order $order): JsonResponse
    {
        if (!$order) {
            return $this->json(['error' => 'Commande non trouvée'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($order, Response::HTTP_OK, [], [
            'groups' => ['order:read', 'order:item']
        ]);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator, UserRepository $userRepository, ProductRepository $productRepository): JsonResponse
    {
        try {
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

                return $this->json(
                    [
                        'error' => 'Validation échouée',
                        'violations' => $errorMessages
                    ],
                    Response::HTTP_BAD_REQUEST
                );
            }

            $entityManager->persist($order);
            $entityManager->flush();

            return $this->json([
                'message' => 'Commande créée avec succès',
                'orderId' => $order->getId()
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json(
                [
                    'message' => 'Erreur lors de la création de la commande',
                    'error' => $e->getMessage()
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    #[IsGranted('ORDER_EDIT')]
    public function update(Order $order, Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator, UserRepository $userRepository): JsonResponse
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

        return $this->json(['message' => 'Commande mise à jour avec succès'], Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    #[IsGranted('ORDER_DELETE')]
    public function delete(Order $order, EntityManagerInterface $entityManager): JsonResponse
    {
        if (!$order) {
            return $this->json(['error' => 'Commande non trouvée'], Response::HTTP_NOT_FOUND);
        }

        $entityManager->remove($order);
        $entityManager->flush();

        return $this->json(['message' => 'Commande supprimée avec succès'], Response::HTTP_OK);
    }
}
