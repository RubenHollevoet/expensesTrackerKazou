<?php
/**
 * Created by PhpStorm.
 * User: ruben.hollevoet
 * Date: 26/05/18
 * Time: 09:32
 */

namespace AppBundle\Doctrine;


use AppBundle\Entity\User;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

class SetRegionListener implements EventSubscriberInterface
{
    protected $tokenStorage;

    public function __construct(TokenStorage $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    public function prePersist(LifecycleEventArgs $args)
    {
//        $entity = $args->getEntity();
//        if(method_exists($entity, 'setRegion') && !($entity instanceof User)) {
//
//            if($entity instanceof User)
//            {
//                $region = $args->getEntityManager()->getRepository('AppBundle:Region')->find(0);
//            }
//            else if( $this->tokenStorage->getToken())
//            {
////                $user = $this->tokenStorage->getToken()->getUser();
////                $region = $user->getRegion();
//            }
//            else
//            {
//                //TODO
//                $region = null;
//            }
//
////            $entity->setRegion($region);
//        }
    }

    public static function getSubscribedEvents()
    {
        return ['prePersist'];
    }
}
