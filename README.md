Voici la correction de l'exercice sur la mise en place de monolog.

## Ajouter un channel dans monolog.yaml

```yaml
monolog:
  channels: ['security']
  handlers:
    security:
      type: stream
      path: '%kernel.logs_dir%/security.log'
      channels: ['security']
      level: info
```

## Injecter et utiliser le logger dans un contrôleur protégé

```php
#[Route('/admin/panel')]
public function admin(
    #[Autowire(service: 'monolog.logger.security')]
    LoggerInterface $securityLogger
): Response {
    $securityLogger->info('Accès au panneau admin', [
        'user' => $this->getUser()->getEmail(),
        'time' => date('c')
    ]);

    return new JsonResponse(['message' => 'Bienvenue admin']);
}
```
Vérifier dans : var/log/security.log

