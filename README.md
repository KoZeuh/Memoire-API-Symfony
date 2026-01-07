Voici la correction de l'exercice sur la mise en place des gestions de fichiers. (Chapitre 3 - Partie 3)

## Déclarer un dossier d'upload

Avant de pouvoir stocker un fichier, Symfony doit savoir où le placer. Dans `config/services.yaml, on ajoute un paramètre global qui représente le chemin vers le dossier public des uploads.

```yaml
parameters:
    upload_directory: "%kernel.project_dir%/public/uploads"
```

### Créer le service FileUploader

Plutôt que d’écrire la logique dans le contrôleur, on crée un service. Nous allons utiliser le `SluggerInterface` de Symfony pour nettoyer le nom du fichier, c'est une sécurité indispensable.

Créez le fichier : `src/Service/FileUploader.php`

```php
<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class FileUploader
{
    // On injecte le dossier cible et le Slugger (outil de nettoyage de texte)
    public function __construct(
        private string $targetDirectory,
        private SluggerInterface $slugger
    ) {
    }

    public function upload(UploadedFile $file): string
    {
        // Récupérer le nom de fichier original (sans l'extension)
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

        // Sécuriser le nom (suppression des accents, espaces, caractères spéciaux)
        $safeFilename = $this->slugger->slug($originalFilename);

        // Générer un nom unique pour éviter d'écraser un fichier existant
        $fileName = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();

        // Déplacement du fichier
        try {
            $file->move($this->targetDirectory, $fileName);
        } catch (FileException $e) {
            // Si le dossier n'existe pas ou n'est pas accessible
            throw new \RuntimeException("Erreur lors de l'upload du fichier");
        }

        return $fileName; // On retourne juste le nom
    }
}
```

Ensuite, on configure ce service dans config/services.yaml pour lui passer le dossier d'upload défini à l'étape 1 :

```yaml
# config/services.yaml
services:
    # ...

    # Injection de l'argument $targetDirectory dans notre service
    App\Service\FileUploader:
        arguments:
            $targetDirectory: "%upload_directory%"
```

### Mettre à jour l'entité Product

```php
#[ORM\Column(length: 255, nullable: true)]
    #[Groups(['product:read', 'order:item'])] // Important pour que l'API renvoie le nom de l'image
    private ?string $image = null;

    // ... getters et setters ...

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;

        return $this;
    }
```

### Mettre à jour la base de données

```Bash
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```

### Créer un DTO pour l'upload

Puisque nous utilisons la validation automatique et le mapping de Symfony (MapRequestPayload), mais que notre entité ne peut pas recevoir directement un fichier (elle attend une string), nous allons créer un DTO.

Pré-requis : Pour que la validation des fichiers fonctionne (#[Assert\File]), vous devez installer le composant Mime :

```Bash
composer require symfony/mime
```

Créez le fichier `src/Dto/ProductCreateDto.php` :

```php
<?php
namespace App\Dto;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

class ProductCreateDto
{
    #[Assert\NotBlank(message: "Le nom est obligatoire")]
    #[Assert\Length(min: 3, minMessage: "Le nom doit faire au moins 3 caractères")]
    public ?string $name = null;

    #[Assert\NotNull(message: "Le prix est obligatoire")]
    #[Assert\Positive(message: "Le prix doit être positif")]
    public ?float $price = null;

    #[Assert\NotBlank(message: "La description est obligatoire")]
    public ?string $description = null;

    // C'est ici que la magie opère : on autorise un objet UploadedFile
    #[Assert\File(
        maxSize: "2M",
        mimeTypes: ["image/jpeg", "image/png", "image/webp"],
        mimeTypesMessage: "Veuillez uploader une image valide (JPG, PNG ou WEBP)"
    )]
    public ?UploadedFile $image = null;
}
```

### Mettre à jour le contrôleur

Maintenant, nous pouvons adapter le ProductController.

Attention technique : Le Serializer de Symfony est très fort pour le JSON, mais il ne sait pas toujours faire le lien automatique entre un champ de formulaire (fichier) et une propriété de DTO en `multipart/form-data`. Nous devons donc injecter l'objet Request pour récupérer le fichier manuellement et le donner au DTO.

Modifiez la méthode `create dans le contrôleur Product :

```php
 #[Route('', name: 'create', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function create(
        Request $request,
        EntityManagerInterface $entityManager,
        FileUploader $fileUploader,
        ValidatorInterface $validator
    ): JsonResponse {

        $dto = new ProductCreateDto();
        $dto->name = $request->request->get('name');
        $dto->price = (float) $request->request->get('price');
        $dto->description = $request->request->get('description');
        $dto->image = $request->files->get('image');

        $errors = $validator->validate($dto);

        if (count($errors) > 0) {
            return $this->json($errors, Response::HTTP_BAD_REQUEST);
        }

       $product = new Product();
        $product->setName($dto->name);
        $product->setPrice($dto->price);
        $product->setDescription($dto->description);

        if ($dto->image) {
            $filename = $fileUploader->upload($dto->image);
            $product->setImage($filename);
        }

        $entityManager->persist($product);
        $entityManager->flush();

        return $this->json([
            'message' => 'Produit créé avec succès',
            'id' => $product->getId(),
            'image' => $product->getImage()
        ], Response::HTTP_CREATED);
    }
```

### Tester l'upload avec Postman

Pour tester cet upload, une simple requête JSON ne suffit pas. Le protocole HTTP nécessite d'utiliser le format multipart/form-data pour envoyer des fichiers binaires.
Voici la configuration exacte à reproduire dans Postman :
Méthode : POST
URL : http://127.0.0.1:8000/product
Onglet : Allez dans Body.
Type : Sélectionnez form-data (et non raw ou x-www-form-urlencoded).

| Clé (Key)     | Valeur (Value)                 | Type (Menu déroulant) |
| :------------ | :----------------------------- | :-------------------- |
| `name`        | iPhone 15                      | Text                  |
| `price`       | 999                            | Text                  |
| `description` | Super téléphone                | Text                  |
| **`image`**   | _(Sélectionnez votre fichier)_ | **File**              |

Si tout est correct, l’API renvoie un statut 201 Created avec le JSON suivant :

```JSON
{
    "message": "Produit créé avec succès",
    "id": 23,
    "image": "iphone-15-65a4b2c1.jpg"
}
```

### Exercice, modification de la méthode update()

```php
    #[Route('/{id}', name: 'update', methods: ['PUT', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function update(
        Product $product,
        Request $request,
        SerializerInterface $serializer,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
        FileUploader $fileUploader
    ): JsonResponse {

        $imageFile = $request->files->get('image');
        $jsonData = $request->request->get('data');

        if ($jsonData) {
            $serializer->deserialize(
                $jsonData,
                Product::class,
                'json',
                ['object_to_populate' => $product]
            );
        }

        if ($imageFile) {
            $filename = $fileUploader->upload($imageFile);
            $product->setImage($filename);
        }

        $errors = $validator->validate($product);

        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }

            return $this->json([
                'error' => 'Validation échouée',
                'violations' => $errorMessages
            ], Response::HTTP_BAD_REQUEST);
        }

        $entityManager->flush();

        return $this->json(['message' => 'Produit mis à jour avec succès'], Response::HTTP_OK);
    }
```
