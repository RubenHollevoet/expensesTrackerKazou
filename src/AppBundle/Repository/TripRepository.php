<?php
/**
 * Created by PhpStorm.
 * User: ruben.hollevoet
 * Date: 22-12-2017
 * Time: 19:47
 */

namespace AppBundle\Repository;


use AppBundle\Entity\User;
use Doctrine\ORM\EntityRepository;

class TripRepository extends EntityRepository
{
    public function findAllRecentTripsForUser(User $user, $limit)
    {
        return $this->createQueryBuilder('trip')
            ->andWhere('trip.user = :user')
            ->setParameter('user', $user)
            ->orderBy('trip.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->execute();
    }
}
