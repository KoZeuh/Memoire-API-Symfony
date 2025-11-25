<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

use App\Entity\Product;
use Faker;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $faker = Faker\Factory::create();
        // Création d'une vingtaine de Produit
        for ($i = 0; $i < 20; $i++) {
            $product = new Product();
            
            $product->setName($faker->name());
            $product->setDescription($faker->text());
	        $product->setPrice($faker->randomFloat(2)); #Ici le 2 affiche le nombre de chiffre après la virgule

            $manager->persist($product);
        }

        $manager->flush();
    }
}
