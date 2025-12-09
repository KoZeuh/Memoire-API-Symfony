Voici la correction de l'exercice sur la validation des données. (Chapitre 2 - Partie 2)

Etape 1 (Entité User) : 
```php

```

Etape 2 (Entité Order) : 
```php

```

Etape 3 (Entité OrderProduct) : 
```php

```

Etape 4 (Entité Product) : 
```php

```

Etape 5 (Modification des contrôleurs) : 

(OrderController - import)

```php

```

(OrderController - function 'create')

```php
```

(OrderController - function 'update')

```php
```

(OrderProductController - import)

```php

```

(OrderProductController - function 'create')

```php
```

(OrderProductController - function 'update')

```php
```


(ProductController - import)

```php

```

(ProductController - function 'create')

```php
```

(ProductController - function 'update')

```php
```


-----------------------------------------------

(UserController - import)

```php

```

(UserController - function 'create')

```php
```

(UserController - function 'update')

```php
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