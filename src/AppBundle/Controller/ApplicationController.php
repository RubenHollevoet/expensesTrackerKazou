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
use AppBundle\Repository\TripGroupRepository;
use AppBundle\Repository\TripRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Validator\Tests\Fixtures\ToString;

class ApplicationController extends Controller
{
    /**
     * @Route("/", name="expenses")
     */
    public function showExpense($regionId = 0)
    {
        $em = $this->getDoctrine()->getManager();
        $user = $this->getUser();

        if (!$user) {
            $_SESSION['_sf2_attributes']['_security.main.target_path'] = $this->generateUrl('expenses');
        }

        $trips = $em->getRepository(Trip::class)->findBy(['user' => $user]);

        return $this->render('expense/show.html.twig', [
            'regionId' => $regionId,
            'trips' => $trips,
            'fbLoginUrl' => $this->container->get('app.service.facebook_user_provider')->getLoginUrl()
        ]);
    }

    /**
     * @Route("/add", name="expenses_add")
     */
    public function addExpense(Request $request)
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
            //todo: set region ID
            'regionId' => 0,
            'form' => $form->createView(),
            'google_api_key' => $this->getParameter('google_api_key')
        ]);
    }

    /* --- API --- */
    /**
     * @Route("/api/getChildGroups")
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getChildGroups(Request $request)
    {
        $children = [];

        $groupId = $request->query->get('group') ?: null;

        $region = $this->getDoctrine()->getRepository(Region::class)->findBy(['id' => $request->query->get('region')]);
        $groups = $this->_getChildGroups($groupId, $region);

        if ($groups) {
            $trips = $this->getDoctrine()->getRepository(Trip::class)->findBy(['group' => $groups]);
//                dump($groups);
//            dump($trips);
//            die();

            foreach ($groups as $child) {
                $startDate = $child->getStartDate();
                if ($startDate) $startDate = $startDate->format('j/n');

//                $tripGroupIds = $this->getDoctrine()->getRepository(TripGroup::class)->getParentGroupsById($child);
                $tripGroupIds = array_keys($this->_getChildGroups($child->getId(), $region));
                $tripGroupIds[] = $child->getId();
//                dump(array_keys($tripGroupIds));
//                die;

                $tripsAwaitingConfirmation = 0;
                foreach ($trips as $trip) {
                    //todo: check if trip id is in array of connected group ID's
                    if(in_array($trip->getActivity()->getId(), $tripGroupIds)) {
//                    if ($trip-> === 'photo') {
                        $tripsAwaitingConfirmation++;
                    }
                }

                $children[] = [
                    'id' => $child->getId(),
                    'name' => $child->getName(),
                    'type' => 'group',
                    'startDate' => $startDate,
                    'code' => $child->getCode(),
                    'tripsAwaitingConfirmation' => $tripsAwaitingConfirmation
                ];
            }
        }

        return $this->json([
            'status' => 'ok',
            'data' => $children
        ]);
    }

    /**
     * @Route("/api/getTripActivities")
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getTripActivities(Request $request)
    {
        $tripActivities = [];
        $error = '';

        $groupId = explode('-', $request->query->get('group'))[0];
        if ($groupId) {
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
     * @Route("/api/getExpenses")
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getTrips(Request $request)
    {
        $error = [];

        $em = $this->getDoctrine()->getManager();
        $group = $em->getRepository(TripGroup::class)->find((int)$request->query->get('group'));
        $denied = $request->query->get('denied') !== 'false';
        $awaiting = $request->query->get('awaiting') !== 'false';
        $approved = $request->query->get('approved') !== 'false';
        $sorting = $request->query->get('sorting') ?: '';

        $trips = $this->getDoctrine()->getRepository(Trip::class)->findAllValidTripsSorted($group->getRegion()->getId(), $group, $denied, $awaiting, $approved, $sorting);

        $result = [
            'groupName' => $group->getName(),
            'trips' => []
        ];
        foreach ($trips as $trip) {

            $result['trips'][] = [
                'id' => $trip->getId(),
                'name' => $trip->getUser()->getFirstName().' '.$trip->getUser()->getLastName(),
                'from' => $trip->getFrom(),
                'date' => $trip->getDate()->format('d/m/y'),
                'to' => $trip->getTo(),
                'activity' => $trip->getActivity()->getName(),
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
            'status' => $error ? 'error' : 'ok',
            'data' => $error ?: $result
        ]);
    }

    /**
     * @Route("/api/updateExpense")
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function updateExpense(Request $request) {

        $error = [];

        $tripData = json_decode($request->getContent());

        $trip = $this->getDoctrine()->getRepository(Trip::class)->find($tripData->id);

        if(count($error) === 0) {
            $trip->setStatus($tripData->status);
            $trip->setCommentAdmin($tripData->adminComment);
            $trip->setHandledBy($this->getUser());
            $trip->setHandledAt(new \DateTime());
            if($trip->getTransportType() === 'car') {
                $trip->setDistance($tripData->distance);
                $trip->setPrice($tripData->distance * 0.25);
            }
            $this->getDoctrine()->getManager()->persist($trip);
            $this->getDoctrine()->getManager()->flush();
        }

        return $this->json([
            'status' => $error ? 'error' : 'ok',
            'data' => [
                'tripStatus' => $trip->getStatus()
            ],
            'request' => $tripData,
        ]);
    }

    /**
     * @Route("/api/getValidateTree")
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getValidateTree(Request $request)
    {

        $error = [];
        $regionId = $request->query->get('region');
        if($regionId === null) $error[] = 'GET parameter \'region\' is missing.';
        $denied = $request->query->get('denied') !== 'false';
        $awaiting = $request->query->get('awaiting') !== 'false';
        $approved = $request->query->get('approved') !== 'false';
        $sorting = $request->query->get('sorting') ?: '';

        $tree = [];

        $region = $this->getDoctrine()->getRepository(Region::class)->findBy(['id' => $regionId]);
        $groups = $this->getDoctrine()->getRepository(TripGroup::class)->findBy(['region' => $region]);

        $trips = $this->getDoctrine()->getRepository(Trip::class)->findAllValidTripsSorted($region, null, $denied, $awaiting, $approved, $sorting);

        foreach($groups as $group) {
            $group->tripCount = 0;
            foreach($trips as $trip) {
                if($trip->getGroup() === $group) {
                    $group->tripCount++;
                }
            }
        }

        foreach($groups as $group) {

            if($group->getParent() === null) {
                $children2 = [];
                foreach ($groups as $group2) {
                    $children3 = [];
                    foreach ($groups as $group3) {
                        if($group3->getParent() === $group2)
                        {
                            $tripCount3 = $group3->tripCount;
                            $children3[$group3->getId()] = [
                                'id' => $group3->getId(),
                                'name' => $group3->getName(),
                                'tripCount' => $tripCount3
                            ];
                        }
                    }

                    if($group2->getParent() === $group) {
                        $tripCount2 = $group2->tripCount;
                        $children2[$group2->getId()] = [
                            'id' => $group2->getId(),
                            'name' => $group2->getName(),
                            'tripCount' => $tripCount2,
                        ];
                        if($children3) $children2[$group2->getId()]['children'] = $children3;
                    }
                }

                $tripCount = $group->tripCount;
                $tree[$group->getId()] = [
                    'id' => $group->getId(),
                    'name' => $group->getName(),
                    'tripCount' => $tripCount,
                ];
                if($children2) $tree[$group->getId()]['children'] = $children2;
            }
        }

        return $this->json([
            'status' => $error ? 'error' : 'ok',
            'data' => $error ?: $tree,
            'count' => count($trips)
        ]);
    }

    /**
     * @Route("/api/createTrip")
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
     * @Route("/api/getRegionsForExport")
     */
    public function getRegionsForExport() {

        $em = $this->getDoctrine()->getManager();
        $regions = $em->getRepository(Region::class)->findAll();
        $trips = $em->getRepository(Trip::class)->findBy(['status' => 'approved']);

        $data = [];

        foreach ($regions as $region) {
            $data[$region->getId()] = [
                'id' => $region->getId(),
                'data' => [
                    'id' => $region->getId(),
                    'name' => $region->getName(),
                    'start' => null,
                    'end' => null,
                    'price' => 0,
                    'count' => 0
                ]
            ];
        }


        foreach ($trips as $trip) {
            $id = $trip->getRegion()->getId();
            $data[$id]['data']['count']++;
            $data[$id]['data']['price'] += $trip->getPrice();
            if($data[$id]['data']['start'] === null || $data[$id]['data']['start'] > strtotime($trip->getDate()->format('Y-m-d')) ) $data[$id]['data']['start'] = strtotime($trip->getDate()->format('Y-m-d'));
            if($data[$id]['data']['end'] === null || $data[$id]['data']['end'] < strtotime($trip->getDate()->format('Y-m-d')) ) $data[$id]['data']['end'] = strtotime($trip->getDate()->format('Y-m-d'));
        }

        //format date time
        foreach ($regions as $region) {
            $data[$region->getId()]['data']['start'] = date('d-m-Y', $data[$region->getId()]['data']['start']);
            $data[$region->getId()]['data']['end'] = date('d-m-Y', $data[$region->getId()]['data']['end']);


            $exports = [];
            $exportDir = $this->getParameter('export_path').'/'.$region->getId().'/';
            if(file_exists($exportDir)) {
                $exports = scandir($exportDir, 1);
            }

            //remove . and .. form export arr
            $filteredExports = [];
            foreach($exports as $export)
            {
                if(is_file($exportDir.$export)) {
                    $filteredExports[] = $export;
                }
            }

            $data[$region->getId()]['data']['exports'] = $filteredExports;
        }

        return $this->json([
            'status' => 'ok',
            'data' => $data
        ]);
    }

    /**
     * @Route("/admin/generateExportFile/{regionId}/{type}", name="exportExpenses")
     */
    public function generateExportFile(Request $request, $regionId, $type) {

        $em = $this->getDoctrine()->getManager();
        $region = $em->getRepository(Region::class)->find($regionId);

        if($region === null) {
            throw new NotFoundHttpException('Unable to find region. Region is equal to null');
        }

        $trips = $em->getRepository(Trip::class)->findBy(['status' => 'approved', 'region' => $region]);

        $tripGroups = [];

        foreach ($trips as $trip) {

            $activityStack = [$trip->getGroup()->getName()];
            if($trip->getGroup()->getParent()) {
                array_unshift($activityStack, $trip->getGroup()->getParent()->getName());
                if($trip->getGroup()->getParent()->getParent()) {
                    array_unshift($activityStack, $trip->getGroup()->getParent()->getParent()->getName());
                }
            }


            $groupCode = $trip->getGroup()->getCode();
            if(!array_key_exists($groupCode, $tripGroups)) {
                $tripGroups[$groupCode] = [
                    'code' => $trip->getGroup()->getCode(),
                    'group' => $trip->getGroup()->getCode() ? implode(' > ', $activityStack) : 'onbekend',
                    'users' => [],
                ];
            }

            $userId = $trip->getUser()->getId();
            if(!array_key_exists($userId, $tripGroups[$groupCode]['users'])) {
                $tripGroups[$groupCode]['users'][$userId] = [
                    'price' => 0,
                    'name' => $trip->getUser()->getFirstName().' '.$trip->getUser()->getLastName(),
                    'personId' => $trip->getUser()->getPersonId(),
                    'iban' => $trip->getUser()->getIban(),
                    'address' => $trip->getUser()->getAddress(),
                    'trips' => []
                ];
            }

            $tripGroups[$groupCode]['users'][$userId]['price'] += $trip->getPrice();
            $tripGroups[$groupCode]['users'][$userId]['trips'][] = [
                'group' => implode(' > ', $activityStack),
                'activity' => $trip->getActivity(),
                'comment' => $trip->getComment(),
                'adminComment' => $trip->getCommentAdmin(),
                'distance' => $trip->getDistance(),
                'price' => $trip->getPrice(),
                'from' => $trip->getFrom(),
                'to' => $trip->getTo()
            ];

//            if($type === 'final') {
//                $trip->setStatus('processed');
//                $em->persist($trip);
//            }
        }

        ksort($tripGroups);

        $export = $this->render('exportTemplates/expensesExport.html.twig', [
            'trips' => $tripGroups,
            'isFinal' => $type === 'final'
        ]);

        if($type === 'final') {
//            $em->flush();

            $fileName = 'export_'.date("i-s-j-n-Y").'_'.count($trips).'_'.$this->getUser()->getFirstName().'.html';
            $dir = $this->getParameter('export_path').'/'.$region->getId();
            if (!file_exists($dir)) {
                mkdir($dir, 0777, true);
            }

            file_put_contents(
                $dir.'/'.$fileName,
                $export
            );

            $this->addFlash('success', 'Een nieuwe export is toegevoegd aan de finale export lijst.');

            return $this->redirect('/admin?action=list&entity=TripExport');
        }
        else {
            return $export;
        }
    }

    /**
     * @Route("/admin/downloadExport/{regionId}/{fileName}", name="downloadExport")
     *
     * @var string $fileName
     * @var string $regionId
     *
     * @return BinaryFileResponse
     */
    public function downloadExportFile($fileName, $regionId) {
        $dir = $this->getParameter('export_path').'/'.$regionId;

        $response = new BinaryFileResponse($dir.'/'.$fileName);

        $response->headers->set('Content-Type', 'text/html');
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$fileName.'"');

        return $response;
    }

    /**
     * @Route("/privacy")
     */
    public function showPrivacy()
    {
        return $this->render('privacy.html.twig', []);
    }

    /**
     * @Route("/api/getTripDistance")
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

    private function _getChildGroups($group, $region = null, $recursive = false)
    {
        if($region) {
            $result = $this->getDoctrine()->getRepository(TripGroup::class)->findBy(['parent' => $group, 'region' => $region], ['startDate' => 'asc']);
        }
        else {
            $result = $this->getDoctrine()->getRepository(TripGroup::class)->findBy(['parent' => $group], ['startDate' => 'asc']);
        }
        if($recursive) {

        }
        return $result;
    }
}
