Voici la correction de l'exercice sur la mise en place de groupes à chaque entité et la mise à jour des fonctions list() et show(). (Chapitre 3 - Partie 2a)

Etape 1 (Ajouter des Groupes sur les Entités) :

Nous avons ajouté l'attribut `#[Groups]` sur les propriétés des entités suivantes :

* **Product** (`src/Entity/Product.php`)
    * Groupe : `product:read`
    * Champs exposés : `id`, `name`, `price`, `description`, `image`.

* **User** (`src/Entity/User.php`)
    * Groupe : `user:read`
    * Champs exposés : `id`, `email`, `roles`.
    * **Sécurité :** La propriété `$password` n'a **pas** de groupe, elle est donc exclue du JSON.

* **Order** (`src/Entity/Order.php`)
    * Groupe : `order:read`
    * Champs exposés : `id`, `totalPrice`, `createdAt`, `status`.
    * **Relation :** La propriété `$orderProducts` possède aussi ce groupe pour afficher le contenu de la commande.

* **OrderProduct** (`src/Entity/OrderProduct.php`)
    * Groupe : `order:read` (pour être visible à l'intérieur d'une commande).

### 2. Mise à jour des Contrôleurs
Les méthodes `list()` et `show()` des contrôleurs ont été modifiées pour activer ces groupes via le paramètre context.

**Fichiers modifiés :**
* `src/Controller/ProductController.php`
* `src/Controller/UserController.php`
* `src/Controller/OrderController.php`

**Exemple de code (ProductController) :**

```php
#[Route('/', name: 'list', methods: ['GET'])]
public function list(ProductRepository $productRepository): JsonResponse
{
    return $this->json($productRepository->findAll(), 200, [], [
        'groups' => ['product:read']
    ]);
}


## Vérification et Tests

Pour valider que l'exercice est réussi, assurez-vous que votre serveur tourne (`symfony server:start`) et testez les routes suivantes avec Postman :

| Méthode | URL | Résultat attendu |
| :--- | :--- | :--- |
| **GET** | `http://127.0.0.1:8000/product` | Retourne la liste des produits avec `id`, `name`, `price`, `description`, `image`. |
| **GET** | `http://127.0.0.1:8000/user` | Retourne les utilisateurs. **Vérifiez impérativement que le champ `password` est absent.** |
| **GET** | `http://127.0.0.1:8000/order` | Retourne les commandes avec leurs items (`orderProducts`). Il ne doit plus y avoir d'erreur 500 (Circular Reference). |


