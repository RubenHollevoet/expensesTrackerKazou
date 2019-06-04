<?php
/**
 * Created by PhpStorm.
 * User: ruben.hollevoet
 * Date: 12/01/19
 * Time: 17:09
 */

namespace AppBundle\Controller;

use AppBundle\Entity\Region;
use AppBundle\Entity\Trip;
use Doctrine\ORM\EntityManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
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

//        echo '1';

        if($regionId === null) {
            throw new NotFoundHttpException('Region ID not found');
        }

//        echo '2';

        $em = $this->getDoctrine()->getManager();

//        echo '3';

        /** @var Region $region */
        $region = $em->getRepository(Region::class)->find($regionId);
        $sheetsId = $region->getGoogleSheetsKey();

//        $json = file_get_contents('https://script.google.com/macros/s/AKfycbzTrarQnhNLPAi_3Scr3cyEas1RSXbxvK4nMXzbf49kfHe7UVm5/exec?file='.$sheetsId);
        $json = file_get_contents('https://script.google.com/macros/s/AKfycbwoHHF7gDJfsxw7hINO9bWCdXeARGxTUO4IVx9PsZUKc4y4rgk/exec?file='.$sheetsId);
        $obj = json_decode($json);

        return new JsonResponse($obj);
    }

    /**
     * @Route("/getShareTripDetails")
     */
    public function getShareTripDetails(Request $request) {
        $tripId = $request->query->get('tripId');

        if(!$this->getUser()) {
            throw new NotFoundHttpException('User not signed in');
        }

        if($tripId === null) {
            throw new NotFoundHttpException('Trip ID not found');
        }

        $em = $this->getDoctrine()->getManager();

        /** @var Trip $trip */
        $trip = $em->getRepository(Trip::class)->find($tripId);

        return new JsonResponse([
            'tripId' => $trip->getId(),
            'regionId' => $trip->getRegion()->getId(),
            'regionName' => $trip->getRegion()->getName(),
            'to' => $trip->getTo(),
            'date' => $trip->getDate()->format('Y-m-d'),
            'groupCode' => $trip->getGroupCode(),
            'crumbTrace' => $trip->getGroupStack(),
            'activity' => $trip->getActivityName(),
            'code_vacation' => $trip->getCodeVacation(),
            'code_s2' => $trip->getCodeS2(),
            'code_s3' => $trip->getCodeS3(),
            'code_s5' => $trip->getCodeS5(),
            'shareFromTrip' => $trip->getId()
        ]);
    }

    /**
     * @Security("has_role('ROLE_ADMIN')")
     * @Route("/validate/{regionId}/getLevels", name="validate_fetch_levels")
     */
    public function validateGetGroups(Request $request, $regionId) {

        /**
         * @var $em EntityManager
         */
        $em = $this->getDoctrine()->getManager();
        $region = $em->getRepository(Region::class)->find($regionId);
        if(!$region) {
            return new JsonResponse(['error' => 'no region found for id '.$regionId.'.']);
        }

        $filter = [
            'status' => $request->query->get('status') ?: [],
            'group' => $request->query->get('search') ?: ''
        ];

        $trips = $em->getRepository(Trip::class)->findAllValidTripsSorted($regionId, $filter);

        $levels = [];


        /**
         * @var $trip Trip
         */
        foreach ($trips as $indexTrip=>$trip) {
            $groups = $trip->getGroupStack();
            $levelCount = [0,0,0];

            foreach ($groups as $indexGroup => $group) {
                if(!isset($levels[$indexGroup])) $index[$indexGroup] = [];

                $parent = '';
                for($i = 0; $i < $indexGroup; $i++) {
                    $parent .= $groups[$i].'-';
                }

                $parent = rtrim($parent, '-');

                if(!isset($levels[$indexGroup][$group])) {
                    $levels[$indexGroup][$group] = [
                        'parent' => $parent,
                        'name' => $group,
                        'level' => $indexGroup,
                        'count' => 0
                    ];

                }

                $levels[$indexGroup][$group]['count']++;
            }
        }

        $json = [
            'region' => $region->getName(),
            'levels' => $levels,
            'count' => count($trips)
        ];

        return new JsonResponse($json);
    }

    /**
     * @Security("has_role('ROLE_ADMIN')")
     * @Route("/validate/{regionId}/getTrips", name="validate_fetch_trips")
     */
    public function validateGetTrips(Request $request, $regionId) {

        /**
         * @var $em EntityManager
         */
        $em = $this->getDoctrine()->getManager();

        $filter = [
            'status' => $request->query->get('status') ?: [],
            'group' => $request->query->get('search') ?: []
        ];

        $sorting = $request->query->get('sorting');

        $trips = $em->getRepository(Trip::class)->findAllValidTripsSorted($regionId, $filter, $sorting);

        $result = [
//            'groupName' => $group->getName(),
            'trips' => []
        ];

        /**
         * @var $trip Trip
         */
        foreach ($trips as $trip) {

            $result['trips'][] = [
                'id' => $trip->getId(),
                'name' => $trip->getUser()->getFirstName().' '.$trip->getUser()->getLastName(),
                'from' => $trip->getFrom(),
                'date' => $trip->getDate()->format('d/m/y'),
                'to' => $trip->getTo(),
                'group' => implode($trip->getGroupStack(), ' -> '),
                'activity' => $trip->getActivityName(),
                'comment' => $trip->getComment(),
                'adminComment' => $trip->getCommentAdmin(),
                'transportType' => $trip->getTransportType(),
                'tickets' => $trip->getTickets(),
                'distance' => $trip->getDistance(),
                'estimatedDistance' => $trip->getEstimateDistance(),
                'price' => $trip->getPrice(),
                'status' => $trip->getStatus(),
            ];
        }

        return $this->json([
            'status' => 'ok',
//            'data' => $error ?: $result
            'data' => $result
        ]);

        return new JsonResponse($json);
    }
}
