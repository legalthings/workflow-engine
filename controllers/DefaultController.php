<?php

/**
 * Default controller
 */
class DefaultController extends BaseController
{    
    /**
     * Show API info
     */
    public function infoAction()
    {
        $info = [
            'name' => App::name(),
            'version' => App::version(),
            'description' => App::description(),
            'env' => App::env()
        ];

        $this->output($info);
    }
    
    /**
     * Redirect admin
     */
    public function adminAction()
    {
        $this->redirect('/admin/scenarios/');
    }
}
