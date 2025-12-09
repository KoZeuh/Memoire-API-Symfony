Voici la correction de l'exercice sur la mise en place de JWT. (Chapitre 2 - Partie 4)

Etape 1 (Configuration du bundle 'LexikJWTAuthenticationBundle') : 

```yml
# config/packages/lexik_jwt_authentication.yaml

lexik_jwt_authentication:
    secret_key: '%env(resolve:JWT_SECRET_KEY)%'
    public_key: '%env(resolve:JWT_PUBLIC_KEY)%'
    pass_phrase: '%env(JWT_PASSPHRASE)%'
    token_ttl: 3600 # 1 heure
    user_id_claim: email

```

```yml
# .env

JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=votre_passphrase_générée_par_la_commande

```

Etape 2 (Configuration du bundle 'GesdinetJWTRefreshTokenBundle') : 


```yml
# config/packages/security.yaml

security:
    password_hashers:
        Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface: 'auto'
    providers:
        app_user_provider:
            entity:
                class: App\Entity\User
                property: email
    firewalls:
        dev:
            pattern: ^/(_(profiler|wdt)|css|images|js)/
            security: false
        
        login:
            pattern: ^/user/login
            stateless: true
            json_login:
                check_path: /user/login
                username_path: email
                password_path: password
                success_handler: lexik_jwt_authentication.handler.authentication_success
                failure_handler: lexik_jwt_authentication.handler.authentication_failure

        register:
            pattern: ^/user/register
            stateless: true
            security: false

        refresh:
            pattern: ^/token/refresh
            stateless: true
            refresh_jwt:
                check_path: /token/refresh

        main:
            lazy: true
            stateless: true
            provider: app_user_provider
            jwt: ~

    access_control:
        - { path: ^/user/login, roles: PUBLIC_ACCESS }
        - { path: ^/user/register, roles: PUBLIC_ACCESS }
        - { path: ^/token/refresh, roles: PUBLIC_ACCESS }
        - { path: ^/, roles: IS_AUTHENTICATED_FULLY }

when@test:
    security:
        password_hashers:
            # Password hashers are resource-intensive by design to ensure security.
            # In tests, it's safe to reduce their cost to improve performance.
            Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface:
                algorithm: auto
                cost: 4 # Lowest possible value for bcrypt
                time_cost: 3 # Lowest possible value for argon
                memory_cost: 10 # Lowest possible value for argon
```

```yml
# config/routes/gesdinet_jwt_refresh_token.yaml

gesdinet_jwt_refresh_token:
    path: /token/refresh

```

```yml
# config/routes.yaml

controllers:
    resource: routing.controllers

api_refresh_token:
    path: /token/refresh

```

```yml
# config/packages/gesdinet_jwt_refresh_token.yaml

gesdinet_jwt_refresh_token:
    refresh_token_class: App\Entity\RefreshToken

```

```php

// src/Controller/UserController.php

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

```

```php
// src/Entity/RefreshToken.php

<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gesdinet\JWTRefreshTokenBundle\Entity\RefreshToken as BaseRefreshToken;

#[ORM\Entity]
#[ORM\Table(name: 'refresh_tokens')]
class RefreshToken extends BaseRefreshToken
{
}
```

Etape 4 (Tester la mise en place) :

Test 1 - Connexion :
```
POST /user/login
{
    "email": "a@a.fr",
    "password": "Test1234"
}
```

Résultat :
```
{
    "token": "eyJ0eXAiOiJKV1QiLCJhbGci...",
    "refresh_token": "def50200a5c8e9b7..."
}
```

Test 2 - Accès à une ressource protégée sans JWT :
```
POST /order
```

Résultat : 
```
{
    "code": 401,
    "message": "JWT Token not found"
}
```

Test 3 - Accès à une ressource protégée avec JWT :
```
POST /order
Authorization: Bearer `token`
```

Résultat : 
```
{
    "message": "Erreur lors de la création de la commande",
    "error": "The identifier id is missing for a query of App\\Entity\\User"
}
```

Test 4 - Renouvellement du token :
```
POST /token/refresh
{
    "refresh_token": "2161407de......"
}
```
```
Résultat :
{
    "refresh_token": "def50200a5c8e9b7..."
}
```

