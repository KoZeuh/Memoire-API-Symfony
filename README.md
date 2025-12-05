Voici la liste des commandes afin de créer les entités pour l'exercice final de la partie 1 
```bash
composer require symfony/security-bundle
php bin/console make:user
 - User
 - yes
 - email
 - yes

php bin/console make:entity Order
  - user (ManyToOne -> User)
  - status (string)

php bin/console make:orderProduct
  - order (ManyToOne -> Order)
  - product (ManyToOne -> Product)
  - quantity (integer)

php bin/console make:migration
php bin/console doctrine:migrations:migrate
```

On va ensuite créer les controller : 
```bash
php bin/console make:controller OrderController
php bin/console make:controller OrderProductController
php bin/console make:controller UserController
``` 

Et ensuite les fixtures pour ces entités : 
```bash
php bin/console make:fixtures OrderFixtures
php bin/console make:fixtures OrderProductFixtures
php bin/console make:fixtures UserFixtures
```
N'oubliez pas de lancer les fixtures après les avoir créées : 
```bash
php bin/console doctrine:fixtures:load
```