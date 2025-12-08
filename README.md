Voici la correction de l'exercice sur la mise en place d'un système complet d'autorisation. (Chapitre 2 - Partie 5)

Etape 1 (Configurer la hiérarchie des rôles) :

```yml
# config/packages/security.yaml

security:
    role_hierarchy:
		ROLE_ADMIN: ROLE_USER
		ROLE_SUPER_ADMIN: [ROLE_ADMIN, ROLE_ALLOWED_TO_SWITCH]
```

Etape 2 (Création du Voter pour les produits) :
```php
//  src/Security/Voter/ProductVoter.php

<?php

namespace App\Security\Voter;

use App\Entity\Product;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ProductVoter extends Voter
{
    public const CREATE = 'PRODUCT_CREATE';
    public const EDIT = 'PRODUCT_EDIT';
    public const DELETE = 'PRODUCT_DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!in_array($attribute, [self::CREATE, self::EDIT, self::DELETE])) {
            return false;
        }

        if (!$subject instanceof Product) {
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
            self::CREATE => $this->canCreate($user),
            self::EDIT => $this->canEdit($user),
            self::DELETE => $this->canDelete($user),
            default => false,
        };
    }

    private function canCreate(User $user): bool
    {
        // Un admin peut créer des produits
        return in_array('ROLE_ADMIN', $user->getRoles());
    }

    private function canEdit(User $user): bool
    {
        // Un admin peut éditer des produits
        return in_array('ROLE_ADMIN', $user->getRoles());
    }

    private function canDelete(User $user): bool
    {
        // Un super admin peut supprimer des produits
        return in_array('ROLE_SUPER_ADMIN', $user->getRoles());
    }
}
```

Etape 3 (Implémenter le voter dans le contrôleur) : 

```php
//  src/Controller/ProductController.php

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
use App\Security\Voter\ProductVoter;

#[Route('/product', name: 'app_product_')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class ProductController extends AbstractController
{
    #[Route('/', name: 'list', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
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

        return $this->json($data);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Product $product): JsonResponse
    {
        if (!$product) {
            return $this->json(['error' => 'Product not found'], Response::HTTP_NOT_FOUND);
        }

        $data = [
            'id' => $product->getId(),
            'name' => $product->getName(),
            'description' => $product->getDescription(),
            'price' => $product->getPrice(),
        ];

        return $this->json($data);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    #[IsGranted('PRODUCT_CREATE', subject: 'product')]
    public function create(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['name']) || !isset($data['price'])) {
            return $this->json(['error' => 'Missing required fields'], Response::HTTP_BAD_REQUEST);
        }

        $product = new Product();
        $product->setName($data['name']);
        $product->setDescription($data['description'] ?? null);
        $product->setPrice($data['price']);

        $entityManager->persist($product);
        $entityManager->flush();

        return $this->json([
            'message' => 'Product created successfully',
            'id' => $product->getId()
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    #[IsGranted('PRODUCT_EDIT', subject: 'product')]
    public function update(Product $product, Request $request, EntityManagerInterface $entityManager): JsonResponse
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

        $entityManager->flush();

        return $this->json(['message' => 'Product updated successfully']);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    #[IsGranted('PRODUCT_DELETE', subject: 'product')]
    public function delete(Product $product, EntityManagerInterface $entityManager): JsonResponse
    {
        if (!$product) {
            return $this->json(['error' => 'Product not found'], Response::HTTP_NOT_FOUND);
        }

        $entityManager->remove($product);
        $entityManager->flush();

        return $this->json(['message' => 'Product deleted successfully'], Response::HTTP_OK);
    }
}
```

Etape 4 (Tester la mise en place) : 


Test 1 - Liste des produits sans authentification :
```
GET /product
```

Résultat : 
```
{
    "code": 401,
    "message": "JWT Token not found"
}
```

Test 2 - Liste des produits avec authentification :
```
GET /product
Authorization : Bearer `token`
```

Résultat : 
```
[
    {
        "id": 1,
        "name": "Halie Treutel",
        "description": "Accusantium aut accusamus officiis non. Vel cum vero velit harum. Rerum ad laborum voluptas officia ea quia blanditiis. Ut necessitatibus architecto corporis ut est ipsum cupiditate.",
        "price": 58760.08
    },
]
```

Test 3 - Modification d’un produit sans autorisation :
```
PUT /product/1
Authorization : Bearer `token`
```

Résultat :
```
Access Denied. (403 Forbidden)
```

Test 4 - Modification d’un produit avec autorisation :
```
PUT /product/1
Authorization : Bearer `token`
```

Résultat : 
```
{
    "message": "Produit mis à jour avec succès"
}
```

Test 5 - Suppression d’un produit sans autorisation :
```
DELETE /product/1
Authorization : Bearer `token`
```

Résultat : 
```
Access Denied. (403 Forbidden)
```

Test 6 - Suppression d’un produit avec autorisation :
```
DELETE /product/1
Authorization : Bearer `token`
```

Résultat : 
```
{
    "message": "Produit supprimé avec succès"
}
```
