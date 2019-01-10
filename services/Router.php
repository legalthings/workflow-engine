<?php

/**
 * Router with authorization
 *
 * @author arnold
 */
class Router extends Jasny\MVC\Router
{
    /**
     * @var Audit
     */
    public $audit;
    
    
    /**
     * Class constructor
     * 
     * @param array $routes  Array with route objects
     */
    public function __construct($routes=null)
    {
        parent::__construct($routes);
        
        $this->audit = new Audit();
    }
    
    
    /**
     * Execute the action of the given route.
     * 
     * @param object $route
     * @param object $overwrite
     * @return boolean|mixed  Whatever the controller returns or true on success
     */
    public function routeTo($route, $overwrite=[])
    {
        $result = parent::routeTo($route, $overwrite);

        $this->audit->logRouteAccessed($route, Auth::session());
        
        return $result;
    }
    
    /**
     * Get controller class for route
     * 
     * @param string|array $route
     * @return string
     */
    static protected function getControllerClass($route)
    {
        // temporary fix needed to support arrays in routes controller config, until jasny dependencies are updated
        // used for stuff like { controller: [admin, scenario] }
        return join('\\', array_map([self::class, 'studlyCase'], (array)$route)) . 'Controller';
    }
    
    /**
     * Turn kabab-case into StudlyCase.
     * 
     * @internal Jasny\studlycase isn't used because it's to tolerent, which might lead to security issues.
     * 
     * @param string $string
     * @return string
     */
    protected static function studlyCase($string)
    {
        return preg_replace_callback('/(?:^|(\w)-)(\w)/', function($match) {
            return $match[1] . strtoupper($match[2]);
        }, strtolower(addcslashes($string, '\\')));
    }
}
