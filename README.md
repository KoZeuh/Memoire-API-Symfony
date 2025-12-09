Voici la correction de l'exercice sur la mise en place d'un système complet d'autorisation. (Chapitre 2 - Partie 5)

Etape 1 (Configurer la hiérarchie des rôles) :

```yml
# config/packages/security.yaml

security:
    role_hierarchy:
		ROLE_ADMIN: ROLE_USER
		ROLE_SUPER_ADMIN: [ROLE_ADMIN, ROLE_ALLOWED_TO_SWITCH]
```

Etape 2 (Création du Voter) :

```php
# src/Security/Voter/OrderVoter

<?php

namespace App\Security\Voter;

use App\Entity\Order;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class OrderVoter extends Voter
{
    public const LIST = 'ORDER_LIST';
    public const SHOW = 'ORDER_SHOW';
    public const EDIT = 'ORDER_EDIT';
    public const DELETE = 'ORDER_DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!in_array($attribute, [self::LIST, self::SHOW, self::EDIT, self::DELETE], true)) {
            return false;
        }

        if (!$subject instanceof Order) {
            return false;
        }

        return true;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        return match($attribute) {
            self::LIST => $this->canShowList($user),
            self::SHOW => $this->canShow($user, $subject),
            self::EDIT => $this->canEdit($user),
            self::DELETE => $this->canDelete($user),
            default => false,
        };
    }

    private function canShowList(User $user): bool
    {
        
        return in_array('ROLE_ADMIN', $user->getRoles());
    }

    private function canShow(User $user, Order $order): bool
    {
        return in_array('ROLE_ADMIN', $user->getRoles()) || $order->getUser() === $user;
    }

    private function canEdit(User $user): bool
    {
        return in_array('ROLE_ADMIN', $user->getRoles());
    }

    private function canDelete(User $user): bool
    {
        return in_array('ROLE_SUPER_ADMIN', $user->getRoles());
    }
}

```

Etape 3 (Implémenter le voter dans le contrôleur) :

```php
# src/Controller/OrderController.php

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
    #[IsGranted('ORDER_SHOW', subject: 'order')]
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

                return $this->json([
                    'error' => 'Validation échouée',
                    'violations' => $errorMessages], 
                Response::HTTP_BAD_REQUEST);
            }

            $entityManager->persist($order);
            $entityManager->flush();

            return $this->json([
                'message' => 'Commande créée avec succès',
                'orderId' => $order->getId()
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json([
                'message' => 'Erreur lors de la création de la commande', 
                'error' => $e->getMessage()], 
            Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    #[IsGranted('ORDER_EDIT', subject: 'order')]
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
    #[IsGranted('ORDER_DELETE', subject: 'order')]
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

```

Etape 4 (Implémenter les restrictions dans le contrôleur) :

```php
# src/Controller/ProductController.php

<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use Symfony\Component\Security\Http\Attribute\IsGranted;

use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/product', name: 'app_product_')]
final class ProductController extends AbstractController
{
    #[Route('/', name: 'list', methods: ['GET'])]
    public function list(ProductRepository $productRepository): JsonResponse
    {
        $products = $productRepository->findAll();

        $data = [];
        foreach ($products as $product) {
            $data[] = [
                'id' => $product->getId(),
                'name' => $product->getName(),
                'description' => $product->getDescription(),
                'price' => $product->getPrice(),
            ];
        };

        return $this->json($data, Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Product $product): JsonResponse
    {
        if (!$product) {
            return $this->json(['error' => 'Produit non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $data = [
            'id' => $product->getId(),
            'name' => $product->getName(),
            'description' => $product->getDescription(),
            'price' => $product->getPrice(),
        ];

        return $this->json($data, Response::HTTP_OK);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function create(Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator): JsonResponse
    {
        $payload = $request->getPayload();

        $product = new Product();
        $product->setName($payload->get('name'));
        $product->setDescription($payload->get('description'));
        $product->setPrice($payload->get('price'));
        $product->setCode($payload->get('code'));

        $errors = $validator->validate($product);

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

        $entityManager->persist($product);
        $entityManager->flush();

        return $this->json([
            'message' => 'Produit créé avec succès',
            'id' => $product->getId()
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN')]
    public function update(Product $product, Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator): JsonResponse
    {
        if (!$product) {
            return $this->json(['error' => 'Produit non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $payload = $request->getPayload();

        if ($payload->has('name')) {
            $product->setName($payload->getString('name'));
        }
        if ($payload->has('description')) {
            $product->setDescription($payload->getString('description'));
        }
        if ($payload->has('price')) {
            $product->setPrice($payload->get('price'));
        }
        if ($payload->has('code')) {
            $product->setCode($payload->getString('code'));
        }

        $errors = $validator->validate($product);

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

        return $this->json(['message' => 'Produit mis à jour avec succès'], Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function delete(Product $product, EntityManagerInterface $entityManager): JsonResponse
    {
        if (!$product) {
            return $this->json(['error' => 'Produit non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $entityManager->remove($product);
        $entityManager->flush();

        return $this->json(['message' => 'Produit supprimé avec succès'], Response::HTTP_OK);
    }
}

```

Etape 5 (Implémenter les restrictions dans le contrôleur) :

```php
# src/Controller/UserController.php

<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/user', name: 'app_user_')]
final class UserController extends AbstractController
{
    #[Route('/', name: 'list', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function list(UserRepository $userRepository): JsonResponse
    {
        $users = $userRepository->findAll();

        $data = [];
        foreach ($users as $user) {
            $data[] = [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
            ];
        };

        return $this->json($data, Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function show(User $user): JsonResponse
    {
        if (!$user) {
            return $this->json(['error' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $data = [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
        ];

        return $this->json($data, Response::HTTP_OK);
    }

    #[Route('/login', methods: ['POST'])]
    public function login(): JsonResponse
        {
        // Cette méthode ne sera jamais appelée car l'authentification est gérée par le firewall
        // Elle sert uniquement de point d'entrée pour la route /user/login
        // Le firewall 'login' dans security.yaml intercepte cette route et gère l'authentification
        // En cas de succès, lexik_jwt_authentication.handler.authentication_success retourne le token
        return $this->json([
            'message' => 'Cette route est gérée par le firewall pour l\'authentification.'
        ]);
    }

    #[Route('/register', name: 'create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        try {
            $payload = $request->getPayload();

            $user = new User();
            $user->setEmail($payload->get('email'));

            // Validation AVANT le hashage du mot de passe
            $plainPassword = $payload->get('password');
            $user->setPassword($plainPassword); // Temporairement pour la validation

            $errors = $validator->validate($user);

            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[$error->getPropertyPath()] = $error->getMessage();
                }

                return $this->json([
                    'message' => 'Validation échouée',
                    'violations' => $errorMessages
                ], Response::HTTP_BAD_REQUEST);
            }

            // Hash le mot de passe après validation
            $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
            $user->setPassword($hashedPassword);

            $entityManager->persist($user);
            $entityManager->flush();

            return $this->json(['message' => 'Utilisateur créé avec succès'], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json([
                'message' => 'Erreur lors de la création de l\'utilisateur',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN')]
    public function update(User $user, Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher, ValidatorInterface $validator): JsonResponse
    {
        if (!$user) {
            return $this->json(['error' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $payload = $request->getPayload();

        if ($payload->has('email')) {
            $user->setEmail($payload->getString('email'));
        }
        if ($payload->has('roles')) {
            $user->setRoles($payload->get('roles'));
        }
        if ($payload->has('password')) {
            $hashedPassword = $passwordHasher->hashPassword($user, $payload->getString('password'));
            $user->setPassword($hashedPassword);
        }

        $errors = $validator->validate($user);

        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }

            return $this->json([
                'message' => 'Validation échouée',
                'violations' => $errorMessages
            ], Response::HTTP_BAD_REQUEST);
        }

        $entityManager->flush();

        return $this->json(['message' => 'Utilisateur mis à jour avec succès'], Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function delete(User $user, EntityManagerInterface $entityManager): JsonResponse
    {
        if (!$user) {
            return $this->json(['error' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $entityManager->remove($user);
        $entityManager->flush();

        return $this->json(['message' => 'Utilisateur supprimé avec succès'], Response::HTTP_OK);
    }
}

```


Etape 6 (Tester la mise en place) : 

Test 1 - Liste des commandes sans authentification :

```
GET /order
```

Résultat : 
```
{
    "code": 401,
    "message": "JWT Token not found"
}
```

Test 2 - Liste des commandes sans autorisation :

```
GET /order
Authorization : Bearer `token`
```

Résultat : 
```
Access Denied. (403 Forbidden)
```

Test 3 - Liste des commandes avec autorisation :

```
GET /order
Authorization : Bearer `token`
```

Résultat : 
```

```

Test 4 - Récupération d’une commande sans être l’auteur :

```
GET /order/1
Authorization : Bearer `token`
```

Résultat : 
```

```

Test 5 - Récupération d’une commande en étant l’auteur :

```
GET /order/2
Authorization : Bearer `token`
```

Résultat : 
```

```

Test 6 - Récupération d’une commande sans autorisation : 

```
GET /order/1
Authorization : Bearer `token`
```

Résultat : 
```

```

Test 7 - Récupération d’une commande avec autorisation :

```
GET /order/1
Authorization : Bearer `token`
```

Résultat : 
```

```

Test 8 - Modification d’une commande sans autorisation :

```
PUT /order/1
Authorization : Bearer `token`
```

Résultat : 
```

```

Test 9 - Modification d’une commande avec autorisation :

```
PUT /order/1
Authorization : Bearer `token`
```

Résultat : 
```

```

Test 10 - Suppression d’une commande sans autorisation :

```
DELETE /order/1
Authorization : Bearer `token`
```

Résultat : 
```

```

Test 11 - Suppression d’une commande avec autorisation :

```
DELETE /order/1
Authorization : Bearer `token`
```

Résultat : 
```

```

Test 12 - Création d’un produit sans autorisation :

```
POST /product
Authorization : Bearer `token`
```

Résultat : 
```

```

Test 13 - Création d’un produit avec autorisation :

```
POST /product
Authorization : Bearer `token`
```

Résultat : 
```

```

Test 14 - Modification d’un produit sans autorisation :

```
PUT /product/1
Authorization : Bearer `token`
```

Résultat : 
```

```

Test 15 - Modification d’un produit avec autorisation :

```
PUT /product/1
Authorization : Bearer `token`
```

Résultat : 
```

```

Test 16 - Suppression d’un produit sans autorisation :

```
DELETE /product/1
Authorization : Bearer `token`
```

Résultat : 
```

```

Test 17 - Suppression d’un produit avec autorisation :

```
DELETE /product/1
Authorization : Bearer `token`
```

Résultat : 
```

```

Test 18 - Liste des utilisateurs sans autorisation :

```
GET /user
Authorization : Bearer `token`
```

Résultat : 
```

```

Test 19 - Liste des utilisateurs avec autorisation :

```
GET /user
Authorization : Bearer `token`
```

Résultat : 
```

```

Test 20 - Récupération d’un utilisateur sans autorisation :

```
GET /user/1
Authorization : Bearer `token`
```

Résultat : 
```

```

Test 21 - Récupération d’un utilisateur avec autorisation :

```
GET /user/1
Authorization : Bearer `token`
```

Résultat : 
```

```

Test 22 - Modification d’un utilisateur sans autorisation :

```
PUT /user/1
Authorization : Bearer `token`
```

Résultat : 
```

```

Test 23 - Modification d’un utilisateur avec autorisation :

```
PUT /user/1
Authorization : Bearer `token`
```

Résultat : 
```

```

Test 24 - Suppression d’un utilisateur sans autorisation :

```
DELETE /user/1
Authorization : Bearer `token`
```

Résultat : 
```

```

Test 25 - Suppression d’un utilisateur avec autorisation :

```
DELETE /user/1
Authorization : Bearer `token`
```

Résultat : 
```

```



