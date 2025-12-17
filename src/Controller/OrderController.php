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
use App\Dto\OrderCreateDto;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Serializer\SerializerInterface;


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
    public function create(
        #[MapRequestPayload] OrderCreateDto $orderDto,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        ProductRepository $productRepository
    ): JsonResponse {

        $user = $userRepository->find($orderDto->userId);
        if (!$user) {
            return $this->json(['message' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $order = new Order();
        $order->setUser($user);

        foreach ($orderDto->products as $productDto) {
            $product = $productRepository->find($productDto->id);
            if (!$product) {
                return $this->json(['error' => 'Produit id ' . $productDto->id . ' non trouvé'], Response::HTTP_NOT_FOUND);
            }

            $orderProduct = new OrderProduct();
            $orderProduct->setOrders($order);
            $orderProduct->setProduct($product);
            $orderProduct->setQuantity($productDto->quantity);

            $entityManager->persist($orderProduct);
            $order->addOrderProduct($orderProduct);
        }

        $entityManager->persist($order);
        $entityManager->flush();

        return $this->json([
            'message' => 'Commande créée avec succès',
            'orderId' => $order->getId()
        ], Response::HTTP_CREATED);
    }


    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    #[IsGranted('ORDER_EDIT')]
    public function update(
        Order $order,
        Request $request,
        SerializerInterface $serializer,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator
    ): JsonResponse {

       $serializer->deserialize(
            $request->getContent(),
            Order::class,
            'json',
            [
                'object_to_populate' => $order,
                'groups' => ['order:update'] 
            ]
        );

        $errors = $validator->validate($order);
        if (count($errors) > 0) {
             return $this->json($errors, Response::HTTP_BAD_REQUEST);
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
