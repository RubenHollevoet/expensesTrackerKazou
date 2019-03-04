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

    public function findAllValidTripsSorted($regionId, $groupSearch = null, $group = null, $denied = true, $awaiting = true, $approved = true, $sorting = '') {

        $qb = $this->createQueryBuilder('trip')
            ->orderBy('trip.user', 'DESC')
            ->where('trip.region = :regionId')->setParameter(':regionId', $regionId)
            ->andWhere('trip.deletedAt IS NULL')
            ;

//        if($group) {
//            $qb->andWhere('trip.group = :group')->setParameter(':group', $group);
//        }

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

        $searchGroupArr = explode(',', $groupSearch);
        $groupFilters = [];
        foreach($searchGroupArr as $i=>$filter) {
            $groupFilters[] = 'trip.groupStack LIKE :filter'.$i;
            $qb->setParameter('filter'.$i, '%'.$filter.'%');
        }
        $orFilterStatement = implode(' OR ', $groupFilters);
        if($orFilterStatement) {
            $qb->andWhere($orFilterStatement);
        }
        else {
            return [];
        }

//        if($groupSearch) {
//            $qb->andWhere('trip.groupStack LIKE :groupSearch');
////            $qb->andWhere('trip.groupStack LIKE :groupSearch');
////            $qb->setParameter('groupSearch', "%{$groupSearch}%")
////            ;
//            ;
//        }

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
