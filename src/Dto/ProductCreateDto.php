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