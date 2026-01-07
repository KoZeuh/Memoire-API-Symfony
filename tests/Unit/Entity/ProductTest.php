<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Category;
use App\Entity\OrderProduct;
use App\Entity\Product;
use PHPUnit\Framework\TestCase;

class ProductTest extends TestCase
{
    public function testApplyTax(): void
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
