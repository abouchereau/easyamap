<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Payment controller.
 *
 */
class PaymentAdminController extends AbstractController
{
    
        /**
     * List action
     *
     * @return Response
     *
     * @throws AccessDeniedException If access is not granted
     */
    public function list()
    {
        $user = $this->get('security.token_storage')->getToken()->getUser();       
        
        $isReferentPage = isset($_GET['referent']) && ($user->isReferent() || $user->getIsAdmin());
        
        $datagrid = $this->admin->getDatagrid($isReferentPage);
        $formView = $datagrid->getForm()->createView();

        // set the theme for the current Admin Form
        $this->get('twig')->getExtension('form')->renderer->setTheme($formView, $this->admin->getFilterTheme());

        return $this->render($this->admin->getTemplate('list'), array(
            'isReferentPage' => $isReferentPage,
            'action'     => 'list',
            'form'       => $formView,
            'datagrid'   => $datagrid,
            'csrf_token' => $this->getCsrfToken('sonata.batch'),
        ));
    }
    
  

}