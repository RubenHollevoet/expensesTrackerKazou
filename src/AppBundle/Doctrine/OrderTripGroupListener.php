<?php
/**
 * Created by PhpStorm.
 * User: ruben.hollevoet
 * Date: 03/06/18
 * Time: 11:04
 */

namespace AppBundle\Doctrine;

use AppBundle\Entity\TripGroup;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class OrderTripGroupListener implements EventSubscriberInterface
{
    protected $em;

    private $orderCounter = 0;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function prePersist(LifecycleEventArgs $args)
    {
//        $this->orderTripGroups();
    }

    public function preUpdate(LifecycleEventArgs $args)
    {
//        $entity = $args->getEntity();
//        if($entity instanceof TripGroup) {
//            $this->orderTripGroups();
//        }
    }

    public function onFlush(OnFlushEventArgs  $args)
    {


        $uow = $args->getEntityManager()->getUnitOfWork();


        $ordered = false;
        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if ($entity instanceof TripGroup && !$ordered) {
                $ordered = true;

                $this->orderCounter = 0;

                $this->handleGroups($uow, null);
            }
        }
    }

    public static function getSubscribedEvents()
    {
        return ['prePersist', 'preUpdate', 'onFlush'];
    }

    private function handleGroups($uow, $parent)
    {
        $repo = $this->em->getRepository('AppBundle:TripGroup');
        $children = $repo->findBy(['parent' => $parent]);
        foreach ($children as $child) {
            $this->setOrder($uow, $child, $this->orderCounter);
            $this->orderCounter++;
            $this->handleGroups($uow, $child);

        }
//        $this->orderCounter++;
    }

    private function setOrder($uow, $entity, $order)
    {
        $entity->setOrder($order);
        $this->em->persist($entity);
        $classMetadata = $this->em->getClassMetadata(TripGroup::class);
        $uow->computeChangeSet($classMetadata, $entity);
    }
}
