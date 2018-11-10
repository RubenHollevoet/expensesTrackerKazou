<?php
/**
 * Created by PhpStorm.
 * User: ruben.hollevoet
 * Date: 03/06/18
 * Time: 13:59
 */

namespace AppBundle\Controller;

use AppBundle\Form\LoginForm;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class RedirectController extends Controller
{
    /**
     * @Route("/*")
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function addTrip(Request $request)
    {

    }
}
