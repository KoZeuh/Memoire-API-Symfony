<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\OrderRepository;
use App\Repository\UserRepository;
use App\Repository\ProductRepository;
use App\Entity\Order;
use App\Entity\OrderProduct;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

use Symfony\Component\Security\Http\Attribute\IsGranted;

use App\Security\Voter\OrderVoter;

#[Route('/api/orders')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class OrderController extends AbstractController
{
    #[Route('', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function list(OrderRepository $repo): JsonResponse
    {
        $orders = $repo->findAll();
        
        $data = array_map(function($order) {
            return [
                'id' => $order->getId(),
                'user' => [
                    'id' => $order->getUser()->getId(),
                    'email' => $order->getUser()->getEmail(),
                ],
                'status' => $order->getStatus(),
                'products' => array_map(function($orderProduct) {
                    return [
                        'id' => $orderProduct->getProduct()->getId(),
                        'name' => $orderProduct->getProduct()->getName(),
                        'price' => $orderProduct->getProduct()->getPrice(),
                        'quantity' => $orderProduct->getQuantity(),
                    ];
                }, $order->getOrderProducts()->toArray()),
            ];
        }, $orders);
        
        return $this->json($data);
    }

    #[Route('', methods: ['POST'])]
    public function create(
        Request $request, 
        EntityManagerInterface $em,
        UserRepository $userRepo,
        ProductRepository $productRepo,
        ValidatorInterface $validator,
    ): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $user = $userRepo->find($data['userId']);

            if (!$user) {
                return $this->json([
                    'message' => 'Utilisateur non trouvé'
                ], 404);
            }

            $order = new Order();
            $order->setUser($user);

            foreach ($data['products'] as $index => $productData) {
                $product = $productRepo->find($productData['id']);

                if (!$product) {
                    return $this->json([
                        'error' => 'Produit non trouvé'
                    ], 404);
                }

                $orderProduct = new OrderProduct();
                $orderProduct->setOrder($order);
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
                    ], 400);
                }

                $em->persist($orderProduct);
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
                ], 400);
            }

            $em->persist($order);
            $em->flush();

            return $this->json([
                'message' => 'Commande créée avec succès',
                'orderId' => $order->getId()
            ], 201);
        } catch (\Exception $e) {
            return $this->json(['message' => 'Erreur lors de la création de la commande', 'error' => $e->getMessage()], 500);
        }
    }

    #[Route('/{id}', methods: ['GET'])]
    #[IsGranted('ORDER_VIEW', subject: 'order')]
    public function show(Order $order): JsonResponse
    {
        return $this->json([
            'id' => $order->getId(),
            'user' => [
                'id' => $order->getUser()->getId(),
                'email' => $order->getUser()->getEmail(),
            ],
            'status' => $order->getStatus(),
            'products' => array_map(function($orderProduct) {
                return [
                    'id' => $orderProduct->getProduct()->getId(),
                    'name' => $orderProduct->getProduct()->getName(),
                    'price' => $orderProduct->getProduct()->getPrice(),
                    'quantity' => $orderProduct->getQuantity(),
                ];
            }, $order->getOrderProducts()->toArray()),
        ]);
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(Order $order, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($order);
        $em->flush();

        return $this->json(null, 204);
    }
}
