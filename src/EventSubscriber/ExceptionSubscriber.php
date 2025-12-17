<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\KernelInterface;
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

        // 404, 403, etc
       if ($exception instanceof HttpExceptionInterface) {
            $statusCode = $exception->getStatusCode();
            
            if ($statusCode === 404) {
                $message = "Ressource introuvable.";
            } else {
                $message = $exception->getMessage();
            }
        }

        // 422, récupération de l'exception précédente
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