<?php
/**
 * Created by PhpStorm.
 * User: ruben.hollevoet
 * Date: 19/05/18
 * Time: 16:46
 */

namespace AppBundle\Repository;


use Doctrine\ORM\EntityRepository;

class TripGroupRepository extends EntityRepository
{
    public function getParentGroupsById($id)
    {

        $parentStack = [$this->find($id)];

        while (end($parentStack)) {
//            $parent = $this->find(end($parentStack))->getParent();
//            $parentStack[] = $parent ?  $parent->getId() : null;

            $parentStack[] = end($parentStack)->getParent();

//            $parentStack[] = $parent ?  $parent->getId() : null;
        }
        array_pop($parentStack);
        return $parentStack;
    }

    public function getActivitiesByGroupArr($groups)
    {
        $activities = [];
        foreach ($groups as $group) {
            $activities = array_merge($activities, $group->getTripActivity()->toArray());
        }

        return array_unique($activities);
    }
}
