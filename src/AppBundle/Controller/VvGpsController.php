<?php
/**
 * Created by PhpStorm.
 * User: ruben.hollevoet
 * Date: 03-03-2018
 * Time: 12:24
 */

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class VvGpsController extends Controller
{
    /**
     * @Route("/vvgps", name="vvgps")
     */
    public function showAction()
    {
        return $this->render('VvGps/menu.html.twig');
    }

    /**
     * @Route("/vvgps/voor-het-vertrek", name="vvgps_beforeDeparture")
     */
    public function showBeforeDeparture()
    {
        return $this->render('VvGps/pages/beforeDeparture.html.twig');
    }

    /**
     * @Route("/vvgps/de-vakantie", name="vvgps_duringVacation")
     */
    public function showDuringVacation()
    {
        return $this->render('VvGps/pages/duringVacation.html.twig');
    }

    /**
     * @Route("/vvgps/extra", name="vvgps_extra")
     */
    public function showExtra()
    {
        return $this->render('VvGps/pages/extra.html.twig');
    }
}
