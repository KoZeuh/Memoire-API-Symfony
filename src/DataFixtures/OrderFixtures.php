<?php

namespace App\DataFixtures;

use App\Entity\Order;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class OrderFixtures extends Fixture implements DependentFixtureInterface
{
    public const ORDER_REFERENCE = 'order_';

    public function load(ObjectManager $manager): void
    {
        $statuses = ['pending', 'processing', 'completed', 'cancelled'];

        for ($i = 0; $i < 20; $i++) {
            $order = new Order();
            $order->setUser($this->getReference(UserFixtures::USER_REFERENCE . rand(0, 9), User::class));
            $order->setStatus($statuses[array_rand($statuses)]);

            $manager->persist($order);
            $this->addReference(self::ORDER_REFERENCE . $i, $order);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
        ];
    }
}
