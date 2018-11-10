<?php
/**
 * Created by PhpStorm.
 * User: ruben.hollevoet
 * Date: 22-12-2017
 * Time: 15:43
 */

namespace AppBundle\Controller;

use AppBundle\Entity\Region;
use AppBundle\Entity\Trip;
use AppBundle\Entity\TripActivity;
use AppBundle\Entity\TripGroup;
use AppBundle\Entity\User;
use AppBundle\Repository\TripRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class ExpensesController extends Controller
{
    /**
     * @Route("/onkosten", name="expenses")
     * @Route("{regionId}/onkosten", name="expenses_region")
     */
    public function showExpense($regionId = 0)
    {
        $em = $this->getDoctrine()->getManager();
        $user = $this->getUser();

        if (!$user) {
            $_SESSION['_sf2_attributes']['_security.main.target_path'] = $this->generateUrl('expenses_region', ['regionId' => $regionId]);
        }

        $trips = $em->getRepository(Trip::class)->findBy(['user' => $user]);

        return $this->render('expense/show.html.twig', [
            'regionId' => $regionId,
            'trips' => $trips,
            'fbLoginUrl' => $this->container->get('app.service.facebook_user_provider')->getLoginUrl()
        ]);
    }

    /**
     * @Route("/{regionId}/onkosten/add", name="expenses_add")
     */
    public function addExpense($regionId, Request $request)
    {
        $trip = new Trip();

        $form = $this->createFormBuilder($trip)
            ->add('to_', TextType::class)
            ->add('from_', TextType::class)
            ->add('date', DateType::class)
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            return $this->redirectToRoute('expense_add'); //todo route to expenses added
        }


        return $this->render('expense/add.html.twig', [
            'regionId' => $regionId,
            'form' => $form->createView(),
            'google_api_key' => $this->getParameter('google_api_key')
        ]);
    }

    /* --- API --- */
    /**
     * @Route("/expenses/api/getChildGroups")
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getChildGroups(Request $request)
    {
        $groups = [];
        $children = [];

        $groupId = $request->query->get('group') ?: null;

//        if ($groupId) {
//            $result = $this->getDoctrine()->getRepository(TripGroup::class)->findBy(['id' => $groupId]);
//            $groups = $this->getDoctrine()->getRepository(TripGroup::class)->findBy(['parent' => $result], ['startDate' => 'asc']);
//        } else {
//            $regionId = $request->query->get('region');
//            $region = $this->getDoctrine()->getRepository(Region::class)->findBy(['id' => $regionId]);
//            $groups = $this->getDoctrine()->getRepository(TripGroup::class)->findBy(['parent' => null, 'region' => $region], ['startDate' => 'asc']);
//        }

        $region = $this->getDoctrine()->getRepository(Region::class)->findBy(['id' => $request->query->get('region')]);
        $groups = $this->_getChildGroups($groupId, $region);

        if ($groups) {
            foreach ($groups as $child) {
                $startDate = $child->getStartDate();
                if ($startDate) $startDate = $startDate->format('j/n');
                $children[] = [
                    'id' => $child->getId(),
                    'name' => $child->getName(),
                    'type' => 'group',
                    'startDate' => $startDate,
                    'code' => $child->getCode()
                ];
            }
        }

        return $this->json([
            'status' => 'ok',
            'data' => $children
        ]);
    }

    /**
     * @Route("/expenses/api/getTripActivities")
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getTripActivities(Request $request)
    {
        $tripActivities = [];
        $error = '';

        $groupId = explode('-', $request->query->get('group'))[0];
        if ($groupId) {
//            $result = $this->getDoctrine()->getRepository(TripGroup::class)->find($groupId);
            $relatedGroupIds = $this->getDoctrine()->getRepository(TripGroup::class)->getParentGroupsById((int)$groupId);
            if ($relatedGroupIds) {
                $tripActivities = $this->getDoctrine()->getRepository(TripGroup::class)->getActivitiesByGroupArr($relatedGroupIds);
            }
        } else {
            $error = 'group doesn\'t exist';
        }

        if (count($tripActivities) < 1) {
            $error = 'no groups exist on this activity';
        }

        if ($tripActivities) {
            foreach ($tripActivities as $child) {
                $activities[] = [
                    'id' => $child->getId(),
                    'name' => $child->getName(),
                    'type' => 'activity',
                    'code' => $child->getCode()
                ];
            }
        }

        return $this->json([
            'status' => $error ? 'error' : 'ok',
            'data' => $error ?: $activities
        ]);
    }

    /**
     * @Route("/expenses/api/getExpenses")
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getTrips(Request $request)
    {
        $error = [];

        $em = $this->getDoctrine()->getManager();
        $group = $em->getRepository(TripGroup::class)->find((int)$request->query->get('group'));

        $region = $this->getDoctrine()->getRepository(Region::class)->findBy(['id' => $request->query->get('region')]);
        $children = $this->_getChildGroups($group, $region, true);

        $groups = array_merge($children, [$group]);

        $trips = $em->getRepository(Trip::class)->findBy(['group' => $groups]);

        $result = [];
        foreach ($trips as $trip) {
            if(!array_key_exists($trip->getGroup()->getId(), $result))
            {
                $result[$trip->getGroup()->getId()] = [
                    'name' => $trip->getGroup()->getName(),
                    'trips' => []
                ];
            }

            $result[$trip->getGroup()->getId()]['trips'][] = [
                'id' => $trip->getId(),
                'name' => $trip->getUser()->getFirstName().' '.$trip->getUser()->getLastName(),
                'from' => $trip->getFrom(),
                'date' => $trip->getDate(),
                'to' => $trip->getTo(),
                'activity' => $trip->getActivity()->getName(),
                'comment' => $trip->getComment(),
                'transportType' => $trip->getTransportType(),
                'tickets' => $trip->getTickets(),
                'distance' => $trip->getDistance(),
                'estimatedDistance' => $trip->getEstimateDistance(),
                'status' => $trip->getStatus(),
            ];
        }

        return $this->json([
            'status' => $error ? 'error' : 'ok',
            'data' => $error ?: $result
        ]);
    }

    /**
     * @Route("/expenses/api/createTrip")
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function addTrip(Request $request)
    {
        $formData = json_decode($request->getContent());
        $em = $this->getDoctrine()->getManager();
        $ticketsArr = [];
        $loopCount = 0;
        if ($formData->tripData->tickets && $formData->tripData->transportType === 'publicTransport') {
            $hash = hash('md5', microtime(true));
            foreach ($formData->tripData->tickets as $ticket) {
                $folder = '/uploads/tickets/' . $this->getUser()->getId() . '/';
                $uploadPath = $this->getParameter('upload_directory');
                if (!is_dir($uploadPath . $folder)) {
                    mkdir($uploadPath . $folder, 0777, true);
                }

                $fileName = $hash . '-' . $loopCount . '.' . explode('/', $ticket->mime)[1];
                file_put_contents($uploadPath . $folder . $fileName, fopen($ticket->content, 'r'));

                $ticketsArr[] = $folder . $fileName;
                $loopCount++;
            }
        }

        //update user
        $user = $this->getUser();
        if (!$user) {
//            $user = $this->getDoctrine()->getRepository(User::class)->findOneByEmail($formData->userData->email);
//            if ($user === null) {
//                //create new user
//                $user = new User();
//                $user->setRegion($em->getRepository(Region::class)->find(1));
//            }
            //TODO: throw exception

            return $this->json([
                'status' => '500',
                'data' => 'user not signed in'
            ]);
        }

        //update user info
        $nameArr = explode(' ', $formData->userData->name);
        $user->setFirstName($nameArr[0]);
        array_shift($nameArr);
        $user->setLastName(implode($nameArr, ' '));
        $user->setEmail($formData->userData->email);
        $user->setIban($formData->userData->iban);
        $user->setPersonId($formData->userData->personId);
        $user->setAddress($formData->userData->address);
        $user->setRegion($em->getRepository(Region::class)->find($formData->regionId)); //TO BE MOVED TO USER REGISTRATION


        //handle tripGroup
        $tripGroup = $this->getDoctrine()->getRepository(TripGroup::class)->find($formData->tripData->groupId);

        //handle tripActivity
        $tripActivity = $this->getDoctrine()->getRepository(TripActivity::class)->find($formData->tripData->activityId);

        $tripDate = new \DateTime($formData->tripData->date);

        $trip = new Trip();
        $trip->setRegion($em->getRepository(Region::class)->find($formData->regionId));
        $trip->setUser($user);
        $trip->setFrom($formData->tripData->from);
        $trip->setTo($formData->tripData->to);
        $trip->setDate($tripDate);
        $trip->setGroup($tripGroup);
        $trip->setActivity($tripActivity);
        $trip->setTransportType($formData->tripData->transportType);
        if ($formData->tripData->transportType === 'publicTransport') {
            $trip->setPrice($formData->tripData->price);
        } else {
            $trip->setPrice($formData->tripData->distance * 0.25);
        }
        if ($ticketsArr) $trip->setTickets($ticketsArr);
        if ($formData->tripData->company) $trip->setCompany($formData->tripData->company);
        if ($formData->tripData->distance) $trip->setDistance($formData->tripData->distance);
        if ($formData->tripData->estimateDistance) $trip->setEstimateDistance($formData->tripData->estimateDistance);
        if ($formData->tripData->comment) $trip->setComment($formData->tripData->comment);

        $em->persist($trip);

        $em->flush();

        return $this->json([
            'status' => 'ok',
        ]);
    }

    /**
     * @Route("/expenses/api/getTripDistance")
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    function getDistance(Request $request)
    {
        $formData = json_decode($request->getContent());
        $distanceMatrixCall = 'https://maps.googleapis.com/maps/api/distancematrix/json?origins=' . $formData->from . '&destinations=' . $formData->to . '&key=' . $this->getParameter('google_api_key');
        $distanceMatrixCall = str_replace(' ', '+', $distanceMatrixCall);

        $json = json_decode(file_get_contents($distanceMatrixCall));

        return $this->json([
            'status' => 'ok',
            'distance' => $json->rows[0]->elements[0]->distance->value
        ]);
    }

    private function _getChildGroups($group, $region, $recursive = false)
    {
        $result = $this->getDoctrine()->getRepository(TripGroup::class)->findBy(['parent' => $group, 'region' => $region], ['startDate' => 'asc']);
        if($recursive) {

        }
        return $result;
    }
}
