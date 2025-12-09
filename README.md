Voici la correction de l'exercice sur la validation des données. (Chapitre 2 - Partie 2)

Etape 1 (Entité User) : 
```php

<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[UniqueEntity('email', message: 'Email déjà utilisé')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    #[Assert\NotBlank(message: "L'email est obligatoire")]
    #[Assert\Email(message: "L'email {{ value }} n'est pas valide")]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    #[Assert\NotBlank(message: "Le mot de passe est obligatoire")]
    #[Assert\Length(
        min: 8,
        minMessage: 'Le mot de passe doit contenir au moins {{ limit }} caractères',
    )]
    private ?string $password = null;

    #[ORM\OneToMany(targetEntity: Order::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $orders;

    public function __construct()
    {
        $this->orders = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Ensure the session doesn't contain actual password hashes by CRC32C-hashing them, as supported since Symfony 7.3.
     */
    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0".self::class."\0password"] = hash('crc32c', $this->password);

        return $data;
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
        // @deprecated, to be removed when upgrading to Symfony 8
    }

    /**
     * @return Collection<int, Order>
     */
    public function getOrders(): Collection
    {
        return $this->orders;
    }

    public function addOrder(Order $order): static
    {
        if (!$this->orders->contains($order)) {
            $this->orders->add($order);
            $order->setUser($this);
        }

        return $this;
    }

    public function removeOrder(Order $order): static
    {
        if ($this->orders->removeElement($order)) {
            // set the owning side to null (unless already changed)
            if ($order->getUser() === $this) {
                $order->setUser(null);
            }
        }

        return $this;
    }
}


```

Etape 2 (Entité Order) : 
```php

<?php

namespace App\Entity;

use App\Repository\OrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: '`order`')]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'orders')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: "L'utilisateur est obligatoire")]
    private ?User $user = null;

    #[ORM\Column(length: 255)]
    private ?string $status = 'pending';

    #[ORM\OneToMany(targetEntity: OrderProduct::class, mappedBy: 'order', orphanRemoval: true)]
    #[Assert\Count(
        min: 1,
        minMessage: "La commande doit contenir au moins {{ limit }} article"
    )]
    #[Assert\Valid]
    private Collection $orderProducts;

    public function __construct()
    {
        $this->orderProducts = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return Collection<int, OrderProduct>
     */
    public function getOrderProducts(): Collection
    {
        return $this->orderProducts;
    }

    public function addOrderProduct(OrderProduct $orderProduct): static
    {
        if (!$this->orderProducts->contains($orderProduct)) {
            $this->orderProducts->add($orderProduct);
            $orderProduct->setOrders($this);
        }

        return $this;
    }

    public function removeOrderProduct(OrderProduct $orderProduct): static
    {
        if ($this->orderProducts->removeElement($orderProduct)) {
            // set the owning side to null (unless already changed)
            if ($orderProduct->getOrders() === $this) {
                $orderProduct->setOrders(null);
            }
        }

        return $this;
    }
}


```

Etape 3 (Entité OrderProduct) : 
```php

<?php

namespace App\Entity;

use App\Repository\OrderProductRepository;
use Doctrine\ORM\Mapping as ORM;

use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: OrderProductRepository::class)]
class OrderProduct
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'orderProducts')]
    #[Assert\NotNull(message: "Une commande est obligatoire")]
    private ?Order $orders = null;

    #[ORM\ManyToOne(inversedBy: 'orderProducts')]
    #[Assert\NotNull(message: "Un produit est obligatoire")]
    private ?Product $product = null;

    #[ORM\Column]
    #[Assert\NotNull(message: "La quantité est obligatoire")]
    #[Assert\Positive(message: "La quantité doit être un nombre positif")]
    private ?int $quantity = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrders(): ?Order
    {
        return $this->orders;
    }

    public function setOrders(?Order $orders): static
    {
        $this->orders = $orders;

        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): static
    {
        $this->product = $product;

        return $this;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }
}


```

Etape 4 (Entité Product) : 
```php

<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[UniqueEntity('name', message: 'Nom déjà utilisé')]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le nom est obligatoire")]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column]
    #[Assert\NotNull(message: "Le prix est obligatoire")]
    #[Assert\Positive(message: "Le prix doit être un nombre positif")]
    private ?float $price = null;

    /**
     * @var Collection<int, OrderProduct>
     */
    #[ORM\OneToMany(targetEntity: OrderProduct::class, mappedBy: 'product')]
    private Collection $orderProducts;

    public function __construct()
    {
        $this->orderProducts = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): static
    {
        $this->price = $price;

        return $this;
    }

    /**
     * @return Collection<int, OrderProduct>
     */
    public function getOrderProducts(): Collection
    {
        return $this->orderProducts;
    }

    public function addOrderProduct(OrderProduct $orderProduct): static
    {
        if (!$this->orderProducts->contains($orderProduct)) {
            $this->orderProducts->add($orderProduct);
            $orderProduct->setProduct($this);
        }

        return $this;
    }

    public function removeOrderProduct(OrderProduct $orderProduct): static
    {
        if ($this->orderProducts->removeElement($orderProduct)) {
            // set the owning side to null (unless already changed)
            if ($orderProduct->getProduct() === $this) {
                $orderProduct->setProduct(null);
            }
        }

        return $this;
    }
}


```

Etape 5 (Modification des contrôleurs) : 

(OrderController - import)

```php

use Symfony\Component\Validator\Validator\ValidatorInterface;

```

(OrderController - function 'create')

```php

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

```

(OrderController - function 'update')

```php

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

```

(OrderProductController - import)

```php

use Symfony\Component\Validator\Validator\ValidatorInterface;

```

(OrderProductController - function 'create')

```php

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

```

(OrderProductController - function 'update')

```php

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

```


(ProductController - import)

```php

use Symfony\Component\Validator\Validator\ValidatorInterface;

```

(ProductController - function 'create')

```php

#[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $product = new Product();
        $product->setName($data['name'] ?? null);
        $product->setDescription($data['description'] ?? null);
        $product->setPrice($data['price'] ?? null);

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
            'message' => 'Product created successfully',
            'id' => $product->getId()
        ], Response::HTTP_CREATED);
    }

```

(ProductController - function 'update')

```php

#[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(Product $product, Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator): JsonResponse
    {
        if (!$product) {
            return $this->json(['error' => 'Product not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['name'])) {
            $product->setName($data['name']);
        }
        if (isset($data['description'])) {
            $product->setDescription($data['description']);
        }
        if (isset($data['price'])) {
            $product->setPrice($data['price']);
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

        return $this->json(['message' => 'Product updated successfully']);
    }
```

(UserController - import)

```php

use Symfony\Component\Validator\Validator\ValidatorInterface;

```

(UserController - function 'create')

```php

#[Route('/register', name: 'create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            $user = new User();
            $user->setEmail($data['email'] ?? null);

            // Validation AVANT le hashage du mot de passe
            $plainPassword = $data['password'] ?? null;
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

            return $this->json([
                'message' => 'Utilisateur créé avec succès'
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json([
                'message' => 'Erreur lors de la création de l\'utilisateur',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

```

(UserController - function 'update')

```php

#[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(User $user, Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher, ValidatorInterface $validator): JsonResponse
    {
        if (!$user) {
            return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['email'])) {
            $user->setEmail($data['email']);
        }
        if (isset($data['roles'])) {
            $user->setRoles($data['roles']);
        }
        if (isset($data['password'])) {
            $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);
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

        return $this->json(['message' => 'User updated successfully']);
    }
```


Etape 4 (Tester la validation) :

Test 1 - Email invalide :
```
POST /user/register
{
    "email": "pas-un-email",
    "password": "Test1234"
}
```

Résultat :
```
{
    "message": "Validation échouée",
    "violations": {
        "email": "L'email \\"pas-un-email\\" n'est pas valide"
    }
}
```

Test 2 - Mot de passe inférieur à 8 caractères :
```
POST /user/register
{
    "email": "a@a.fr",
    "password": "Test"
}
```

Résultat :
```
{
    "message": "Validation échouée",
    "violations": {
        "password": "Le mot de passe doit contenir au moins 8 caractères"
    }
}
```

Test 3 - Inscription réussie :
```
POST /user/register
{
    "email": "a@a.fr",
    "password": "Test1234"
}
```

Résultat : 
```
{
    "message": "Utilisateur créé avec succès"
}
```

Test 4 - Email déjà utilisé :
```
POST /user/register
{
    "email": "a@a.fr",
    "password": "Test1234"
}
```

Résultat :
```
{
    "message": "Validation échouée",
    "violations": {
        "email": "Email déjà utilisé"
    }
}
```

Test 5 - Commande sans articles :
```
POST /order
{
    "userId": 1,
    "products": []
}
```

Résultat : 
```
{
    "error": "Validation échouée",
    "violations": {
        "orderProducts": "La commande doit contenir au moins 1 article"
    }
}
```

Test 6 - Commande avec une quantité inférieure ou égale à 0 :
```
POST /order
{
    "userId" : 1,
    "products": [
        {
            "id": 1,
            "quantity": 0
        }
    ]
}
```

Résultat : 
```
{
    "error": "Validation échouée",
    "violations": {
        "orderProducts": "La quantité doit être un nombre positif"
    }
}
```

Test 7 - Commande valide
```
POST /order
{
    "userId" : 1,
    "products": [
        {
            "id": 1,
            "quantity": 1
        }
    ]
}
```

Résultat : 
```
{
    "message": "Commande créée avec succès",
    "orderId": 5
}
```

Test 8 - Produit sans nom :
```
POST /product
{
    "price": 19.99,
    "description": "Un produit sans nom"
}
```

Résultat : 
```
{
    "error": "Validation échouée",
    "violations": {
        "orderProducts": "Le nom est obligatoire"
    }
}
```

Test 9 - Produit avec un nom mais sans prix :
```
POST /product
{
    "name": "Produit sans prix",
    "description": "Un produit sans prix"
}
```

Résultat : 
```
{
    "error": "Validation échouée",
    "violations": {
        "orderProducts": "Le prix est obligatoire"
    }
}
```

Test 10 - Produit valide :
```
POST /product
{
    "name": "Produit Test",
    "description": "Un produit de test valide",
    "price": 19.99
}
```

Résultat : 
```
{
    "message" => "Product created successfully",
    "id" => 1
}
```