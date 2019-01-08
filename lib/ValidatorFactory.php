<?php

use Respect\Validation\Exceptions\ComponentException;

/**
 * Factory for validator
 */
class ValidatorFactory extends Respect\Validation\Factory
{
    /**
     * Get singleton instance.
     * @deprecated
     * 
     * @return static
     */
    public static function instance()
    {
        return App::getContainer()->get(ValidatorFactory::class);
    }
    
    /**
     * Get the reflection class for a rule
     * 
     * @param string $ruleName
     * @return \ReflectionClass
     * @throws ComponentException
     */
    protected function getReflectionForRule($ruleName)
    {
        foreach ($this->getRulePrefixes() as $prefix) {
            $className = $prefix . ucfirst($ruleName);
            if (!class_exists($className)) {
                continue;
            }

            $reflection = new ReflectionClass($className);
            if (!$reflection->isSubclassOf('Respect\\Validation\\Validatable')) {
                throw new ComponentException(sprintf('"%s" is not a valid respect rule', $className));
            }

            return $reflection;
        }
        
        throw new ComponentException(sprintf('"%s" is not a known rule', $ruleName));
    }
    
    /**
     * Get a list of parameters for a rule
     * 
     * @param string $rulename
     * @return array
     */
    public function getArgumentsForRule($rulename)
    {
        $reflClass = $this->getReflectionForRule($rulename);
        if (!$reflClass->hasMethod('__construct')) return [];
        
        $reflConstruct = $reflClass->getMethod('__construct');
        $reflArgs = $reflConstruct->getParameters();
        
        $args = [];
        foreach ($reflArgs as $reflArg) {
            $key = $reflArg->getName();
            $args[$key] = ($reflArg->isOptional() ? $reflArg->getDefaultValue() : null);            
        }
        
        return $args;
    }
}
