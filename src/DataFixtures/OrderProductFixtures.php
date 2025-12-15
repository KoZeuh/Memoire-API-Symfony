<?php

namespace App\DataFixtures;

use App\Entity\Order;
use App\Entity\OrderProduct;
use App\Entity\Product;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class OrderProductFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $faker = \Faker\Factory::create();

        // Ajoute 2-4 produits par commande
        for ($i = 0; $i < 20; $i++) {
            $productsCount = rand(2, 4);

            for ($j = 0; $j < $productsCount; $j++) {
                $orderProduct = new OrderProduct();
                $orderProduct->setOrders($this->getReference(OrderFixtures::ORDER_REFERENCE . $i, Order::class));
                $orderProduct->setProduct($this->getReference(ProductFixtures::PRODUCT_REFERENCE . rand(0, 9), Product::class));
                $orderProduct->setQuantity($faker->numberBetween(1, 10));

                $manager->persist($orderProduct);
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            OrderFixtures::class,
            ProductFixtures::class,
        ];
    }
}
