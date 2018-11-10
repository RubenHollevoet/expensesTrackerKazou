<?php
/**
 * Created by PhpStorm.
 * User: ruben.hollevoet
 * Date: 10/05/18
 * Time: 08:48
 */

namespace AppBundle\Controller;

use AppBundle\Entity\Vacancy;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;

class VacancyController extends Controller
{
    /**
     * @Route("/open-plaatsen", name="vacancies")
     */
    public function showAction()
    {

        $vacancyLines = $this->getDoctrine()->getRepository(Vacancy::class)->getAllPublishedVacancies();

        $vacancies = [];

        foreach ($vacancyLines as $vacancyLine) {
            //create group
            $parentGroupId = $vacancyLine->getGroup()->getId();
            if (!array_key_exists($parentGroupId, $vacancies)) {
                $vacancies[$parentGroupId] = [
                    'name' => $vacancyLine->getGroup()->getName(),
                    'vacations' => [],
                ];
            }

            //create vacation
            $groupId = $vacancyLine->getGroup()->getId();
            if (!array_key_exists($groupId, $vacancies[$parentGroupId]['vacations'])) {
                $vacancies[$parentGroupId]['vacations'][$groupId] = [
                    'startDate' => $vacancyLine->getGroup()->getStartDate(),
                    'endDate' => $vacancyLine->getGroup()->getEndDate(),
                    'name' => $vacancyLine->getGroup()->getName(),
                    'location' => $vacancyLine->getGroup()->getLocation(),
                ];
            }

            //create details
            $vacancies[$parentGroupId]['vacations'][$groupId]['vacancies'][] = [
                'comment' => $vacancyLine->getComment(),
                'function' => $vacancyLine->getFunction(),
            ];
        }


        return $this->render('vacancy/detail.html.twig', [
//            'vacancies' => $vacancies
            'vacancies' => json_encode($vacancies)
        ]);
    }
}
