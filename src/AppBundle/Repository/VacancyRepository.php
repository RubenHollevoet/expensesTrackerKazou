<?php
/**
 * Created by PhpStorm.
 * User: ruben.hollevoet
 * Date: 10/05/18
 * Time: 07:50
 */

namespace AppBundle\Repository;

use AppBundle\Entity\User;
use Doctrine\ORM\EntityRepository;

class VacancyRepository extends EntityRepository
{
    public function getAllPublishedVacancies() {

        return $this->createQueryBuilder('vacancy')
            ->getQuery()
            ->execute();
    }
}
