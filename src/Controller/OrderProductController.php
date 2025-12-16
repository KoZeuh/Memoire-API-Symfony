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
    public function create(Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator, OrderRepository $orderRepository, ProductRepository $productRepository): JsonResponse
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
            'message' => 'Produit de commande créée avec succès',
            'id' => $orderProduct->getId()
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(OrderProduct $orderProduct, Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator,OrderRepository $orderRepository, ProductRepository $productRepository): JsonResponse
    {
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

        return $this->json(['message' => 'Produit de commande mis à jour avec succès'], Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(OrderProduct $orderProduct, EntityManagerInterface $entityManager): JsonResponse
    {
        $entityManager->remove($orderProduct);
        $entityManager->flush();

        return $this->json(['message' => 'Produit de commande supprimé avec succès'], Response::HTTP_OK);
    }
}