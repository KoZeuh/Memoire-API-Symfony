Voici la correction de la mise en place du kernel event. (Chapitre 3 - Partie 2c)


## Installation du composant EventDispatcher

```
composer require symfony/event-dispatcher
```

## Création du subscriber

Créer le fichier src/EventSubscriber/ExceptionSubscriber.php

```php
<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Validator\Exception\ValidationFailedException;

class ExceptionSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        $statusCode = 500;
        $message = "Une erreur inattendue est survenue.";
        $errors = null;

        //Gestion des erreurs HTTP
        if ($exception instanceof HttpExceptionInterface) {
            $statusCode = $exception->getStatusCode();
            
            // Amélioration : Message propre pour les 404
            if ($statusCode === 404) {
                $message = "Ressource introuvable.";
            } else {
                $message = $exception->getMessage();
            }
        }

        // Cas particulier : Validation échouée (422)
        $previousException = $exception->getPrevious();
        if ($previousException instanceof ValidationFailedException) {
            $statusCode = 422;
            $message = "Validation échouée";
            
            $violations = $previousException->getViolations();
            $errors = [];
            foreach ($violations as $violation) {
                $errors[$violation->getPropertyPath()] = $violation->getMessage();
            }
        }

        // Construction de la réponse
        $data = [
            'status' => $statusCode,
            'message' => $message
        ];

        if ($errors) {
            $data['violations'] = $errors;
        }
         $event->setResponse(new JsonResponse($data, $statusCode));
    }
}
```

Tester ce qu'on vient de mettre en place

Pour vérifier que tout fonctionne, nous allons effectuer deux tests : une erreur serveur (500) et une erreur de page introuvable (404).

Simuler une erreur critique (500)
Ajoutez cette fonction dans le ProductController au dessus de la fonction list().
```php
#[Route('/test-error', methods: ['GET'])]
public function testError(): Response 
{
    // Simuler une erreur critique (500)
    throw new \RuntimeException("Ceci est une erreur de test !");
}
```

Test postman
`GET http://127.0.0.1:8000/product/test-error`

Le résultat attendu devrait être le suivant
```JSON
{
   "status": 500,
   "message": "Une erreur inattendue est survenue."
}
```

Simuler une ressource introuvable (404)

Essayez d'appeler une URL qui n'existe pas, par exemple:
`GET http://127.0.0.1:8000/route-imaginaire`


Le résultat attendu devrait être le suivant.
```JSON
{
  "status": 404",
  "message": "Ressource introuvable."
}
```






