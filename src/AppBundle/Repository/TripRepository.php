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

    public function findAllValidTripsSorted($regionId, $group, $denied, $awaiting, $approved, $sorting) {


        $qb = $this->createQueryBuilder('trip')
            ->orderBy('trip.user', 'DESC')
            ->where('trip.region = :regionId')->setParameter(':regionId', $regionId);

        if($group) {
            $qb->andWhere('trip.group = :group')->setParameter(':group', $group);
        }

        //filter
        $filters = [];
        if($denied) $filters[] = 'trip.status = \'denied\'';
        if($awaiting) $filters[] = 'trip.status = \'awaiting\'';
        if($approved) $filters[] = 'trip.status = \'approved\'';
        $orStatement = implode(' OR ', $filters);

        if($orStatement) {
            $qb->andWhere($orStatement);
        }
        else {
            return [];
        }

        //sorting
        if($sorting === 'name') {
            $qb->orderBy('trip.user', 'ASC');
        }
        elseif($sorting === 'date') {
            $qb->orderBy('trip.date', 'ASC');
        }

        return $qb->getQuery()->execute();
    }
}
