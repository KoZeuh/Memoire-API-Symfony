Voici la correction de l'exercice sur la mise en place de groupes à chaque entité et la mise à jour des fonctions list() et show(). (Chapitre 3 - Partie 2a)

Etape 1 (Ajouter des Groupes sur les Entités) :

Nous avons ajouté l'attribut `#[Groups]` sur les propriétés des entités suivantes :

### 1. Product (`src/Entity/Product.php`)
* **Groupe principal :** `product:read`
* **Particularité :** Les champs `name`, `price` et `id` possèdent aussi les groupes `order:read` et `order:item`. Cela permet d'afficher les infos du produit lorsqu'on consulte une commande.

### 2. User (`src/Entity/User.php`)
* **Groupe principal :** `user:read`
* **Particularité :** `id` et `email` possèdent aussi `order:read` pour identifier l'acheteur dans une commande.
* **Sécurité :** La propriété `$password` n'a **aucun groupe**, elle est donc exclue de tous les retours JSON.

### 3. Order (`src/Entity/Order.php`)
* **Groupe principal :** `order:read`
* **Relation :** La collection `$orderProducts` est exposée avec `order:read` et `order:item`.

### 4. OrderProduct (`src/Entity/OrderProduct.php`)
* **Groupe principal :** `order:item`
* **Rôle :** Sert de lien. Il expose la quantité et l'objet `product` associé.
### 2. Mise à jour des Contrôleurs
Les méthodes `list()` et `show()` des contrôleurs ont été modifiées pour activer ces groupes via le paramètre context.

## Mise à jour des Contrôleurs

Les méthodes `list()` et `show()` ont été modifiées pour utiliser la méthode `json()` avec le paramètre `groups`.

### ProductController & UserController
Ils utilisent un seul groupe simple.

```php
// ProductController
return $this->json($products, Response::HTTP_OK, [], ['groups' => ['product:read']]);

// UserController
return $this->json($users, Response::HTTP_OK, [], ['groups' => ['user:read']]);
``````

### OrderController (Cas particulier)
Pour afficher une commande complète (avec ses lignes et les produits associés), nous combinons deux groupes. C'est nécessaire car la commande utilise order:read et les lignes de produits utilisent order:item.

```php
// OrderController.php
return $this->json($orders, Response::HTTP_OK, [], [
    'groups' => ['order:read', 'order:item']
]);
``````

## Vérification et Tests

Pour valider que l'exercice est réussi, assurez-vous que votre serveur tourne (`symfony server:start`) et testez les routes suivantes avec Postman :

| Méthode | URL | Résultat attendu |
| :--- | :--- | :--- |
| **GET** | `http://127.0.0.1:8000/product` | Retourne la liste des produits avec `id`, `name`, `price`, `description`, `image`. |
| **GET** | `http://127.0.0.1:8000/user` | Retourne les utilisateurs. **Vérifiez que le champ `password` est absent.** |
| **GET** | `http://127.0.0.1:8000/order` | Retourne les commandes. Grâce à la combinaison des groupes, vous devriez voir : Order -> OrderProduct -> Product (Nom/Prix) sans boucle infinie.|

