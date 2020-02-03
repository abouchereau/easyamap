<?php
// from http://www.tribulations.eu/articles/preexecute-dans-les-controller-symfony2.html
namespace  App\Listener; 
use Symfony\Component\HttpKernel\HttpKernelInterface; 
use Symfony\Component\HttpKernel\Event\ControllerEvent; 

class ControllerListener  {
    
    public function onCoreController(ControllerEvent $event) 	
    {
        // Récupération de l'event 	
        if(HttpKernelInterface::MASTER_REQUEST === $event->getRequestType()) 
        {
            // Récupération du controller    
            $_controller = $event->getController();
            if (isset($_controller[0])) 
            {
                $controller = $_controller[0];
                // On vérifie que le controller implémente la méthode preExecute
                if(method_exists($controller,'preExecute'))
                {
                    $controller->preExecute($event->getRequest());
                }
            }
        }
 
    }
}

