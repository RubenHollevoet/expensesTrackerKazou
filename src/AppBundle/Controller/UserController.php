<?php
/**
 * Created by PhpStorm.
 * User: ruben.hollevoet
 * Date: 22-12-2017
 * Time: 14:30
 */

namespace AppBundle\Controller;


use AppBundle\Entity\Region;
use AppBundle\Entity\User;
use AppBundle\Form\UserProfileForm;
use AppBundle\Form\UserRegistrationForm;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UserController extends Controller
{
    /**
     * @Route("/profile", name="user_profile")
     */
    public function showUser(Request $request)
    {
        $form = $this->createForm(UserProfileForm::class, $this->getUser());

        $form->handleRequest($request);
        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $user = $form->getData();
            $userWithEmail = $em->getRepository(User::class)->findOneBy([ 'email' => $user->getEmail()]);
            if($userWithEmail !== null && $userWithEmail->getId() !== $user->getId())
            {
                $this->addFlash('error', 'Dit email adres is al in gebruik door een andere account. Je gegevens zijn niet aangepast.');
            }
            else {
                $em->persist($user);
                $em->flush();
                $this->addFlash('success', 'Je account wijzigingen zijn opgeslagen.');
                return $this->redirectToRoute('expenses');
            }
        }

        return $this->render('user/show.html.twig', [
            'form' => $form->createView(),
            'google_api_key' => $this->getParameter('google_api_key')
        ]);
    }

    /**
     * @Route("/user/facebookResponse")
     */
    public function loginResponse() {

        $fbUserProvider = $this->container->get('app.service.facebook_user_provider');
        return $fbUserProvider->handleResponse();
    }

    /**
     * @Route("/user/register", name="user_register")
     * @Route("{regionId}/user/register", name="user_register_region")
     */
    public function registerUser(Request $request, $regionId = 0)
    {
        // form stuff
        $form = $this->createForm(UserRegistrationForm::class);

        $form->handleRequest($request);
        if ($form->isValid()) {

            if($this->getDoctrine()->getRepository(User::class)->findBy(['email' => $form->getData()->getEmail()]))
            {
                $this->addFlash('error', 'Dit email adres is al in gebruik. Indien je Facebook account dit mail adres gebruikt, kan je je via Facebook met deze account aanmelden.');
            }
            else {
                $user = $form->getData();
                $user->setRegion($this->getDoctrine()->getRepository(Region::class)->find($regionId));
                $user->setRoles(['ROLE_USER']);
                $em = $this->getDoctrine()->getManager();
                $em->persist($user);
                $em->flush();
                $this->addFlash('success', 'Welkom '.$user->getEmail().'. Je kan vanaf nu inloggen.');

                if($regionId > 0) return $this->redirectToRoute('expenses_region', ['regionId' => $regionId]);
                return $this->redirectToRoute('expenses');
            }
        }

        return $this->render('user/register.html.twig', [
            'regionId' => $regionId,
            'form' => $form->createView(),
            'fbLoginUrl' => $this->container->get('app.service.facebook_user_provider')->getLoginUrl()
        ]);
    }

//    /**
//     * @Route("/user/login", name="user_login")
//     */
//    public function loginUser()
//    {
//        return $this->render('user/login.html.twig', [
//
//            ]);
//    }
}
