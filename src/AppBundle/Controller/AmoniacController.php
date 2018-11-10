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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class AmoniacController
 * @Route("amoniac")
 */
class AmoniacController extends Controller
{
    /**
     * @Route("/", name="amoniac")
     */
    public function showAction(Request $request)
    {
        $json = file_get_contents($this->getParameter('google_API_script_Amoniac'));
        $activityData = json_decode($json);

        $showFull = false;

        if($request->query->get('show') === 'all' || $request->cookies->get('show') === 'all') {
            $showFull = true;
        }


        return $this->render('Amoniac/index.twig', [
            'activityData' => $activityData,
            'showFull' => $showFull
        ]);
    }
}
