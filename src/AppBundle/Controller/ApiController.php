<?php
/**
 * Created by PhpStorm.
 * User: ruben.hollevoet
 * Date: 12/01/19
 * Time: 17:09
 */

namespace AppBundle\Controller;

use AppBundle\Entity\Region;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class ApiController
 * @Route("/api")
 */
class ApiController extends Controller
{
    /**
     * @Route("/getRegions", name="fetch_regions")
     */
    public function getRegions(Request $request) {
        $em = $this->getDoctrine()->getManager();

        $regions = $em->getRepository(Region::class)->findAll();

        $regionResponse = [];
        /** @var Region $region */
        foreach ($regions as $region) {
            $regionResponse[] = [
                'id' => $region->getId(),
                'name' =>$region->getName()
            ];
        }

        return new JsonResponse($regionResponse);
    }

    /**
     * @Route("/getActivityTree", name="fetch_activity_tree")
     */
    public function getActivityTree(Request $request) {
        $regionId = $request->get('regionId');

        if($regionId === null) {
            throw new NotFoundHttpException('Region ID not found');
        }

        $em = $this->getDoctrine()->getManager();

        /** @var Region $region */
        $region = $em->getRepository(Region::class)->find($regionId);
        $sheetsId = $region->getGoogleSheetsKey();

        $json = file_get_contents('https://script.google.com/macros/s/AKfycbwoHHF7gDJfsxw7hINO9bWCdXeARGxTUO4IVx9PsZUKc4y4rgk/exec?file='.$sheetsId);
        $obj = json_decode($json);

        return new JsonResponse($obj);
    }
}
