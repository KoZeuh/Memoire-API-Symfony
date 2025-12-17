<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;
use App\Dto\OrderItemDto; 

class OrderCreateDto
{
    #[Assert\NotNull]
    public ?int $userId = null;

    #[Assert\Valid]
    #[Assert\Count(min: 1)]
    /**
     * @var OrderItemDto[]
     */
    public array $products = [];
}