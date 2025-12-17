<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Attribute\SerializedName;

class OrderProductDto
{
    #[Assert\NotNull]
    #[SerializedName('order_id')]
    public ?int $orderId = null;

    #[Assert\NotNull]
    #[SerializedName('product_id')]
    public ?int $productId = null;

    #[Assert\Positive]
    public int $quantity = 1;
}
