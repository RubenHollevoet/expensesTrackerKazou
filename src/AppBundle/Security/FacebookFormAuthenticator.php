<?php
/**
 * Created by PhpStorm.
 * User: ruben.hollevoet
 * Date: 17/04/18
 * Time: 18:24
 */

namespace AppBundle\Security;


use AppBundle\Form\LoginForm;
use AppBundle\Service\FacebookUserProvider;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoder;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\Authenticator\AbstractFormLoginAuthenticator;
use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;
use Facebook\Facebook;
use Symfony\Component\HttpFoundation\Session\Session;

class FacebookFormAuthenticator extends AbstractFormLoginAuthenticator
{
    use TargetPathTrait;

    private $em;
    private $router;
    private $facebookUserProvider;
    private $session;
    /**
     * @var TokenStorage
     */
    private $tokenStorage;

    public function __construct(EntityManager $em, RouterInterface $router, TokenStorage $tokenStorage, FacebookUserProvider $facebookUserProvider, Session $session)
    {
        $this->em = $em;
        $this->router = $router;
        $this->facebookUserProvider = $facebookUserProvider;
        $this->tokenStorage = $tokenStorage;
        $this->session = $session;
    }

    public function getCredentials(Request $request)
    {
        if($this->tokenStorage->getToken())
        {
            return;
        }

        $fbUser = $this->facebookUserProvider->getCurrentUser();

        return $fbUser;
    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        $fbUserId = $credentials['id'];

        if(!$this->facebookUserProvider->createOrUpdateUser($credentials)) {
            //error when creating user
            $this->facebookUserProvider->resetFacebookUserSession();
            $this->session->getFlashBag()->add('error', 'Er liep iets mis, je Facebook profiel kon helaas niet geregistreerd worden. Neem contact op met Kazou indien het probleem zich blijft voordoen.');
        }

        return $this->em->getRepository('AppBundle:User')
            ->findOneBy(['fb_userId' => $fbUserId]);
    }

    public function checkCredentials($credentials, UserInterface $user)
    {
        return true;
    }

    protected function getLoginUrl()
    {
        return $this->router->generate('security_login');
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        // if the user hits a secure page and start() was called, this was
        // the URL they were on, and probably where you want to redirect to
        $targetPath = $this->getTargetPath($request->getSession(), $providerKey);

        if (!$targetPath) {
            $targetPath = $this->router->generate('homepage');
        }

        return new RedirectResponse($targetPath);
    }
}
