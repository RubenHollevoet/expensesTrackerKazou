<?php
/**
 * Created by PhpStorm.
 * User: ruben.hollevoet
 * Date: 22-12-2017
 * Time: 14:30
 */

namespace AppBundle\Controller;

use AppBundle\Entity\Link;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class HomeController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function home()
    {
        //hacky
        if($this->getUser()) {
            if($this->getUser()->getRegion()->getId() > 0) {
                return $this->redirectToRoute('expenses_region', ['regionId' => $this->getUser()->getRegion()->getId()]);
            }
        }

        $links = $this->getDoctrine()->getRepository(Link::class)->findBy(['enabled' => true]);
        return $this->render('home.html.twig', ['links' => $links]);
    }

    /**
     * @Route("/privacy")
     */
    public function showPrivacy()
    {
        return $this->render('privacy.html.twig', []);
    }
}
