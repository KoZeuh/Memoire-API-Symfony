<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ProductFixture extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $faker = \Faker\Factory::create();

        for ($i = 0; $i < 10; $i++) {
            $product = new \App\Entity\Product();
            $product->setName($faker->word());
            $product->setDescription($faker->sentence());
            $product->setPrice($faker->randomFloat(2, 1, 100));

            $manager->persist($product);
        }

        $manager->flush();
    }
}
