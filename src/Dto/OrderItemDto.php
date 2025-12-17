<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class OrderItemDto
{
    #[Assert\NotNull(message: "L'ID du produit est obligatoire")]
    public ?int $id = null;

    #[Assert\Positive(message: "La quantité doit être positive")]
    public int $quantity = 1;
}