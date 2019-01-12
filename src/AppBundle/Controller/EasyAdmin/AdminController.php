<?php
/**
 * Created by PhpStorm.
 * User: ruben.hollevoet
 * Date: 09/06/18
 * Time: 09:52
 */

namespace AppBundle\Controller\EasyAdmin;

use JavierEguiluz\Bundle\EasyAdminBundle\Controller\AdminController as BaseAdminController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

class AdminController extends BaseAdminController
{
    /** @Route("/admin", name="easyadmin") */
    public function indexAction(Request $request) {
        return parent::indexAction($request);
    }

    protected function listAction() {
        if(array_key_exists('region', $this->entity['properties'])) {
            if($this->entity['list']['dql_filter']) {
                $this->entity['list']['dql_filter'] .= ' AND ';
            }

            $this->entity['list']['dql_filter'] .= 'entity.region = '.(string)$this->getUser()->getRegion()->getId();
        }

        return parent::listAction();
    }

    protected function prePersistEntity($entity)
    {
        $user = $this->getUser();

        if(method_exists($entity, 'setRegion')) {
            $entity->setRegion($user->getRegion());
        }

        if(method_exists($entity, 'setCreatedBy')) {
            $entity->setCreatedBy($user);
        }

        if(method_exists($entity, 'setUpdatedBy')) {
            $entity->setUpdatedBy($user);
        }

        if(method_exists($entity, 'setCreatedAt')) {
            $entity->setCreatedAt(new \DateTime());
        }

        if(method_exists($entity, 'setUpdatedAt')) {
            $entity->setUpdatedAt(new \DateTime());
        }

        parent::prePersistEntity($entity);
    }

    protected function preUpdateEntity($entity)
    {
        $user = $this->getUser();

        if(method_exists($entity, 'setUpdatedBy')) {
            $entity->setUpdatedBy($user);
        }

        if(method_exists($entity, 'setUpdatedAt')) {
            $entity->setUpdatedAt(new \DateTime());
        }

        parent::preUpdateEntity($entity);
    }
}
