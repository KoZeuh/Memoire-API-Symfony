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

Etape 3 (Modification des contrôleurs) : 

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

(OrderController - import)

```php

use App\Entity\OrderProduct;
use App\Repository\ProductRepository;
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

Test 2 - Inscription réussie :
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

Test 3 - Email déjà utilisé :
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

Test 4 - Commande sans articles :
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

Test 5 - Commande valide
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
