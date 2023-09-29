<?php
namespace App\EventSubscriber;

use App\Controller\TokenAuthenticatedController;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
class TokenSubscriber implements EventSubscriberInterface
{
   /* public function __construct(
        private array $tokens
    ) {
    }*/

    public function onKernelController(ControllerEvent $event): void
    {
        //die("onKernelController");
        $controller = $event->getController();

        // when a controller class defines multiple action methods, the controller
        // is returned as [$controllerInstance, 'methodName']
        if (is_array($controller)) {
            $controller = $controller[0];
        }
        //die(get_class($controller));
        if(method_exists($controller,'preExecute'))
        {
            $controller->preExecute($event->getRequest());
        }
        if ($controller instanceof TokenAuthenticatedController) {
          /*  $token = $event->getRequest()->query->get('token');
            if (!in_array($token, $this->tokens)) {
                throw new AccessDeniedHttpException('This action needs a valid token!');
            }*/
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => 'onKernelController',
        ];
    }
}