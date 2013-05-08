<?php

namespace WTW\UserBundle\Controller;

use FOS\UserBundle\Controller\SecurityController as BaseController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as Extra;
use Symfony\Component\DependencyInjection\ContainerAware,
    Symfony\Component\HttpFoundation\Response,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\Security\Http\Logout\LogoutHandlerInterface,
    Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * Class SecurityController
 *
 * @Extra\Route(service="wtw.user.security_controller")
 * @package WTW\DashboardBundle\Controller
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class SecurityController extends BaseController implements LogoutHandlerInterface
{
    /**
     * @Extra\Route("/basic/logout", name="wtw_dashboard_logout_basic")
     */
    public function logout(Request $request, Response $response, TokenInterface $token)
    {
        $request->getSession()->set('requested_logout', true);
    }
}