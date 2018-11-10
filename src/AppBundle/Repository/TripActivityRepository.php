<?php
/**
 * Created by PhpStorm.
 * User: ruben.hollevoet
 * Date: 19/05/18
 * Time: 16:46
 */

namespace AppBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class TripActivityRepository extends EntityRepository
{
    public $tokenStorage;

    /**
     * TripActivityRepository constructor.
     * @param $tokenStorage
     */
    public function __construct($tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }


    public function setTokenStorageInterface(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    public static function getMyRegion(EntityRepository $repository)
    {
        return $repository->createQueryBuilder('tripActivity')
            ->andWhere('trip.user = :user')
            ;
    }
}
