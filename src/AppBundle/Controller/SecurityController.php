<?php
/**
 * Created by PhpStorm.
 * User: ruben.hollevoet
 * Date: 17/04/18
 * Time: 17:55
 */

namespace AppBundle\Controller;


use AppBundle\Form\LoginForm;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class SecurityController extends Controller
{
    /**
     * @Route("/login", name="security_login")
     */
    public function loginAction($regionId = 0)
    {
        $authenticationUtils = $this->get('security.authentication_utils');

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        $form = $this->createForm(LoginForm::class, [
            '_username' => $lastUsername
        ]);

        return $this->render('security/login.html.twig', [
            'regionId' => $regionId,
            'form' => $form->createView(),
            'fbLoginUrl' => $this->container->get('app.service.facebook_user_provider')->getLoginUrl(),
            'error' => $error,
        ]);
    }

    /**
     * @Route("/logout", name="security_logout")
     */
    public function logoutAction()
    {
        //TODO: remove Facebook session
        throw new \Exception('logout - this should not be reached');
    }
}
