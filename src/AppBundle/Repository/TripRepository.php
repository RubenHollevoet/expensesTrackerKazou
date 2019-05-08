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
    public function findAllOpenTripsForUser(User $user)
    {
        return $this->createQueryBuilder('trip')
            ->andWhere('trip.user = :user')
            ->andWhere('trip.deletedAt IS NULL')
            ->setParameter('user', $user)
            ->orderBy('trip.createdAt', 'DESC')
            ->getQuery()
            ->execute();
    }

    public function findAllValidTripsSorted($regionId, $filters = [], $sorting = '') {

        $qb = $this->createQueryBuilder('trip')
            ->orderBy('trip.user', 'DESC')
            ->where('trip.region = :regionId')->setParameter(':regionId', $regionId)
            ->andWhere('trip.deletedAt IS NULL')
            ;

        //status filter
        if($filters['status']) {
            $qb->andWhere('trip.status IN (:statusOptions)')
                ->setParameter(':statusOptions',  explode(',', $filters['status']));
        }
        $qb->andWhere('trip.status != \'processed\'');

        //group filter
        $searchGroupArr = explode(',', $filters['group']);
        $groupFilters = [];
        foreach($searchGroupArr as $i=>$filter) {
            $groupFilters[] = 'trip.groupStack LIKE :filter'.$i;
            $qb->setParameter('filter'.$i, '%'.$filter.'%');
        }
        if($groupFilters) {
            $orFilterStatement = implode(' AND ', $groupFilters);
            $qb->andWhere($orFilterStatement);
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
