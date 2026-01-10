Voici la correction de l'exercice sur la mise en place des tests automatisées. (Chapitre 3 - Partie 3)


## Ajout du bundle phpunit

```shell
composer require --dev phpunit/phpunit
```

### Ajout d'un test unitaire

Créez le fichier : `tests/Unit/Entity/ProductTest.php`

```php
<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Category;
use App\Entity\OrderProduct;
use App\Entity\Product;
use PHPUnit\Framework\TestCase;

class ProductTest extends TestCase
{
    public function testGettersAndSetters(): void
    {
        $product = new Product();
        $product->setName('name');
        $product->setDescription('description');
        $product->setPrice(100);
        $product->setCode('code');
        $product->setImage('image.jpg');

        $category = new Category();
        $product->setCategory($category);

        $orderProduct = new OrderProduct();
        $product->addOrderProduct($orderProduct);

        $this->assertEquals('name', $product->getName());
        $this->assertEquals('description', $product->getDescription());
        $this->assertEquals(100, $product->getPrice());
        $this->assertEquals('code', $product->getCode());
        $this->assertEquals('image.jpg', $product->getImage());
        $this->assertSame($category, $product->getCategory());
        $this->assertContains($orderProduct, $product->getOrderProducts());

        $product->removeOrderProduct($orderProduct);

        $this->assertNotContains($orderProduct, $product->getOrderProducts());
    }
}
```

### Ajout d'un test fonctionnel
Création d'un endpoint /ping à tester

```php
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
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Serializer\SerializerInterface;
use App\Dto\ProductCreateDto;
use App\Service\FileUploader;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

#[Route('/ping', name: 'app_ping_')]
final class PingController extends AbstractController
{
    #[Route('', name: 'ping', methods: ['GET'])]
    public function list(ProductRepository $productRepository): JsonResponse
    {
        $response = new JsonResponse();
        $response->setStatusCode(Response::HTTP_OK);
        $response->setContent(json_encode([
            'ping' => 'pong'
        ]));

        return $response;
    }
}
```

Créez le fichier : `tests/PingApiTest.php`

```php
<?php

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PingApiTest extends WebTestCase
{
    public function testGetProducts(): void
    {
        $client = static::createClient();
        $client->request('GET', '/ping');

        $this->assertResponseIsSuccessful();
        $this->assertJson($client->getResponse()->getContent());

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
    }
}
```

### Lancement des tests en local

```shell
php bin/phpunit
```

### Lancement des tests dans un CI/CD

Création du fichier .github/workflows/tests.yml

```yaml
name: Run API tests

on:
    push:
    pull_request:

jobs:
    tests:
        runs-on: ubuntu-latest

        steps:
            - uses: actions/checkout@v4

            - name: Set up PHP
              uses: shivammathur/setup-php@v2
              with:
                  php-version: '8.3'
                  extensions: mbstring, intl, pdo_mysql

            - name: Install dependencies
              run: composer install --prefer-dist

            - name: Run tests
              run: php bin/phpunit
```
à chaque push ou pull request, les tests seront exécutés
