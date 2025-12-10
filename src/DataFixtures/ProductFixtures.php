<?php

namespace App\DataFixtures;

use App\Entity\Product;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ProductFixtures extends Fixture
{
    public const PRODUCT_REFERENCE = 'product_';

    public function load(ObjectManager $manager): void
    {
        $faker = \Faker\Factory::create();

        for ($i = 0; $i < 10; $i++) {
            $product = new Product();
            $product->setName($faker->word());
            $product->setDescription($faker->sentence());
            $product->setPrice($faker->randomFloat(2, 1, 100));

            $manager->persist($product);
            $this->addReference(self::PRODUCT_REFERENCE . $i, $product);
        }

        $manager->flush();
    }
}
