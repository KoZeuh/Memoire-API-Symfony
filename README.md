Voici la correction de l'exercice sur la combinaison Validation et Sérialisation. (Chapitre 3 - Partie 2b)

## Etape 1 Modifications sur l'entité User

Pour sécuriser la création, nous avons défini un Groupe de Sérialisation spécifique (user:create). Cela permet de contrôler quels champs l'utilisateur a le droit d'envoyer lors de l'inscription (seulement email et password).

Fichier : `src/Entity/User.php`.
Nous avons ajouté le groupe user:create sur les propriétés $email et $password.

```php
#[ORM\Column(length: 180)]
#[Assert\NotBlank(message: "L'email est obligatoire")]
#[Assert\Email(message: "L'email {{ value }} n'est pas valide")]
#[Groups(['user:read', 'order:read', 'user:create'])] // <--- Ajout de user:create
private ?string $email = null;

#[ORM\Column]
#[Assert\NotBlank(message: "Le mot de passe est obligatoire")]
#[Assert\Length(min: 8)]
#[Groups(['user:create'])] // <--- Ajout de user:create
private ?string $password = null;
```

## Etape 2 Refactorisation du UserController (Create)
Dans le contrôleur, nous utilisons `#[MapRequestPayload]` en précisant le contexte de sérialisation. Cela dit à Symfony : "Prends le JSON, transforme-le en objet User, mais regarde uniquement les champs marqué `user:create`."

Fichier : `src/Controller/UserController.php`.
```php
#[Route('/register', name: 'create', methods: ['POST'])]
    public function create(
    #[MapRequestPayload(serializationContext: ['groups' => ['user:create']])] User $user, 
    EntityManagerInterface $entityManager, 
    UserPasswordHasherInterface $passwordHasher
): JsonResponse {
        
        // À ce stade, $user contient l'email et le mot de passe (en clair) envoyés.
        // Il ne reste plus qu'à hasher le mot de passe.
        $hashedPassword = $passwordHasher->hashPassword($user, $user->getPassword());
        $user->setPassword($hashedPassword);

        $entityManager->persist($user);
        $entityManager->flush();

        return $this->json(['message' => 'Utilisateur créé avec succès'], Response::HTTP_CREATED);
    }
```

## Etape 3 Refactorisation du ProductController (Create)

Pour le produit, la logique est encore plus simple car nous n'avons pas restreint les champs via un groupe spécifique dans cet exercice (on prend tout l'objet Product).

Fichier : `src/Controller/ProductController.php`

```php
#[Route('', name: 'create', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function create(
        // L'attribut MapRequestPayload fait tout le travail de mapping JSON -> Product
        #[MapRequestPayload] Product $product, 
        EntityManagerInterface $entityManager
    ): JsonResponse {

// Pas de logique métier complexe ici, on sauvegarde directement.
    $entityManager->persist($product);
    $entityManager->flush();

    return $this->json([
        'message' => 'Produit créé avec succès',
        'id' => $product->getId()
    ], Response::HTTP_CREATED);
    }
```

## Etape 4 Création avec DTOs (Order & OrderProduct)
Pour les entités comme Order (Commande), nous ne pouvons pas utiliser directement l'entité pour la création. Pourquoi ? Parce que le client (le frontend) nous envoie des IDs (ex: userId: 1, productId: 5) et non des objets complets. 
L'entité Order attend des objets User et Collection<OrderProduct>, ce qui rend le mapping automatique impossible sans intermédiaire.

Nous utilisons donc des DTOs pour valider la structure reçue avant de traiter les données.

##Création des Dtos

A. Le modèle d'un article (OrderItemDto)
Ce DTO représente une ligne dans la liste des produits d'une commande.

Fichier : `src/Dto/OrderItemDto.php`

```php
class OrderItemDto
{
    #[Assert\NotNull(message: "L'ID du produit est obligatoire")]
    public ?int $id = null;

    #[Assert\Positive(message: "La quantité doit être positive")]
    public int $quantity = 1;
}
```

B. Le modèle de création de commande (OrderCreateDto)
C'est l'objet principal reçu par le contrôleur. Il contient l'ID du client et une liste d'articles.

Fichier : `src/Dto/OrderCreateDto.php`

```php
class OrderCreateDto
{
    #[Assert\NotNull]
    public ?int $userId = null;

    #[Assert\Valid]
    #[Assert\Count(min: 1, minMessage: "Une commande doit contenir au moins 1 article")]
    /** @var OrderItemDto[] */
    public array $products = [];
}
```

C. Le modèle pour une ligne unique (OrderProductDto)
Ce DTO est utilisé spécifiquement par le OrderProductController pour ajouter un produit à une commande déjà existante. 
Nous avons utilisé l'attribut #[SerializedName] pour mapper les clés JSON en snake_case (ex: order_id) vers les propriétés PHP en camelCase ($orderId).

Fichier : `src/Dto/OrderProductDto.php`

```php
namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Attribute\SerializedName;

class OrderProductDto
{
    #[Assert\NotNull]
    #[SerializedName('order_id')] // Le JSON attend "order_id"
    public ?int $orderId = null;

    #[Assert\NotNull]
    #[SerializedName('product_id')] // Le JSON attend "product_id"
    public ?int $productId = null;

    #[Assert\Positive]
    public int $quantity = 1;
}
```

## Etape 5 Modification du OrderController
Dans le contrôleur, nous injectons le DTO (`OrderCreateDto`) au lieu de l'entité. Le contrôleur a ensuite la responsabilité de convertir ce DTO en véritables entités Doctrine (`Order, OrderProduct`) en allant chercher les références en base de données.

Fichier : `src/Controller/OrderController.php`

```php
#[Route('', name: 'create', methods: ['POST'])]
    public function create(
        #[MapRequestPayload] OrderCreateDto $orderDto, // Validation de la structure JSON
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
                return $this->json(['error' => "Produit (ID: {$productDto->id}) non trouvé"], Response::HTTP_NOT_FOUND);
            }

            $orderProduct = new OrderProduct();
            $orderProduct->setOrders($order);
            $orderProduct->setProduct($product);
            $orderProduct->setQuantity($productDto->quantity);

            $entityManager->persist($orderProduct);
            
            // On lie la ligne à la commande pour la cohérence objet
            $order->addOrderProduct($orderProduct);
        }

        $entityManager->persist($order);
        $entityManager->flush();

        return $this->json([
            'message' => 'Commande créée avec succès',
            'orderId' => $order->getId()
        ], Response::HTTP_CREATED);
    }
```

## Etape 6 Modification du OrderProductController
Nous avons également adapté la création d'une ligne de commande unique (ajout d'un produit dans une commande existante) en utilisant un DTO spécifique : `OrderProductDto`.

Fichier : `src/Controller/OrderProductController.php`

```php
#[Route('', name: 'create', methods: ['POST'])]
    public function create(
        #[MapRequestPayload] OrderProductDto $dto,
        EntityManagerInterface $entityManager,
        OrderRepository $orderRepository,
        ProductRepository $productRepository
    ): JsonResponse {
        
        // Même logique : on récupère les entités via les IDs du DTO
        $order = $orderRepository->find($dto->orderId);
        $product = $productRepository->find($dto->productId);

        if (!$order || !$product) {
            return $this->json(['error' => 'Commande ou Produit introuvable'], Response::HTTP_NOT_FOUND);
        }

        $orderProduct = new OrderProduct();
        $orderProduct->setOrders($order);
        $orderProduct->setProduct($product);
        $orderProduct->setQuantity($dto->quantity);

        $entityManager->persist($orderProduct);
        $entityManager->flush();

        return $this->json(['message' => 'Produit ajouté à la commande'], Response::HTTP_CREATED);
    }
```

## Etape 7 Mise à jour avec Serializer (Update)
Pour les méthodes de mise à jour (`update`), nous ne pouvons pas utiliser `#[MapRequestPayload]` aussi simplement, car nous ne voulons pas créer un nouvel objet, mais modifier un objet existant récupéré par Doctrine via l'URL `({id})`.

Nous utilisons donc le service `SerializerInterface` manuellement avec l'option clé `object_to_populate`.

## Modification des entités (Groupes de Valisation)

Nous avons ajouté des groupes de sérialisation spécifiques (:update) pour contrôler précisément quels champs peuvent être modifiés par l'API.

A. User (`src/Entity/User.php`)
On autorise la modification de l'email et du mot de passe.
```php
#[Groups(['user:read', 'order:read', 'user:create', 'user:update'])] // Ajout user:update
    private ?string $email = null;

    #[Groups(['user:create', 'user:update'])] // Ajout user:update
    private ?string $password = null;
```
B. Order (`src/Entity/Order.php`)
On autorise uniquement la modification du statut de la commande.
```php
#[ORM\Column(length: 255)]
    #[Groups(['order:read', 'order:update'])] // Ajout order:update
    private ?string $status = 'pending';
```
C. OrderProduct (`src/Entity/OrderProduct.php`)
On autorise uniquement la modification de la quantité.
```php
#[ORM\Column]
    #[Groups(['order:item', 'order:item:update'])] // Ajout order:item:update
    private ?int $quantity = null;
```

## Modification des contrôleurs (Update)

La logique est similaire pour tous les contrôleurs :

Désérialiser le JSON dans l'entité existante (object_to_populate).
Valider l'entité modifiée (validator->validate).
Sauvegarder (flush).

A. ProductController (Mise à jour standard)
Ici, nous n'avons pas défini de groupe spécifique dans le code fourni, nous mettons à jour l'objet complet.

Fichier : `src/Controller/ProductController.php`
```php
#[Route('/{id}', name: 'update', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN')]
    public function update(
        Product $product, 
        Request $request, 
        SerializerInterface $serializer, 
        EntityManagerInterface $entityManager, 
        ValidatorInterface $validator
    ): JsonResponse {
        
        // 1. Injection des données JSON dans l'objet $product existant
        $serializer->deserialize(
            $request->getContent(),
            Product::class,
            'json',
            ['object_to_populate' => $product]
        );

        // 2. Validation manuelle
        $errors = $validator->validate($product);
        if (count($errors) > 0) {
            // ... Logique de retour d'erreur (voir code complet)
             return $this->json($errors, Response::HTTP_BAD_REQUEST);
        }

        // 3. Sauvegarde
        $entityManager->flush();

        return $this->json(['message' => 'Produit mis à jour avec succès'], Response::HTTP_OK);
    }
```

B. UserController (Gestion du Mot de passe)
Cas particulier : si l'utilisateur envoie un nouveau mot de passe, il faut le hasher avant de sauvegarder. Le Serializer remplit l'entité avec le mot de passe en clair, nous devons le détecter et le chiffrer.

Fichier : `src/Controller/UserController.php`
```php
#[Route('/{id}', name: 'update', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN')]
    public function update(
        User $user,
        Request $request,
        SerializerInterface $serializer,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher, // Nécessaire pour le hash
        ValidatorInterface $validator
    ): JsonResponse {
        
        // On restreint la mise à jour aux champs du groupe 'user:update'
        $serializer->deserialize(
            $request->getContent(),
            User::class,
            'json',
            [
                'object_to_populate' => $user,
                'groups' => ['user:update'] // Sécurité : on ignore les autres champs
            ]
        );

        // Validation
        $errors = $validator->validate($user);
        if (count($errors) > 0) {
            return $this->json($errors, Response::HTTP_BAD_REQUEST);
        }

        // Vérification manuelle de la présence d'un mot de passe dans le JSON envoyé
        $payload = $request->toArray();
        if (isset($payload['password'])) {
            // Hachage du nouveau mot de passe
            $user->setPassword(
                $passwordHasher->hashPassword($user, $user->getPassword())
            );
        }

        $entityManager->flush();

        return $this->json(['message' => 'Utilisateur mis à jour avec succès'], Response::HTTP_OK);
    }
```

C. OrderController (Mise à jour du statut)
On utilise le groupe order:update pour s'assurer que seul le statut est modifiable (on ne change pas le User ou les produits d'une commande via cette route).

Fichier : `src/Controller/OrderController.php`
```php
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
                'groups' => ['order:update'] // Seul le status a ce groupe
            ]
        );

        $errors = $validator->validate($order);
        if (count($errors) > 0) {
             return $this->json($errors, Response::HTTP_BAD_REQUEST);
        }

        $entityManager->flush();

        return $this->json(['message' => 'Commande mise à jour avec succès'], Response::HTTP_OK);
    }
```

D. OrderProductController (Mise à jour de la quantité)
On utilise le groupe 'order:item:update' pour ne permettre que la modification de la quantité.

Fichier : 'src/Controller/OrderProductController.php'
```php
#[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(
        OrderProduct $orderProduct,
        Request $request,
        SerializerInterface $serializer,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator
    ): JsonResponse {

        $serializer->deserialize(
            $request->getContent(),
            OrderProduct::class,
            'json',
            [
                'object_to_populate' => $orderProduct,
                'groups' => ['order:item:update'] // Seule la quantité a ce groupe
            ]
        );

        $errors = $validator->validate($orderProduct);
        if (count($errors) > 0) {
            return $this->json($errors, Response::HTTP_BAD_REQUEST);
        }

        $entityManager->flush();

        return $this->json(['message' => 'Ligne de commande mise à jour'], Response::HTTP_OK);
    }
```
