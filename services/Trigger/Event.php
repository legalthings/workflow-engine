<?php

namespace Trigger;

use TriggerResponse;
use LTO\Event as LtoEvent;

/**
 * Description of Event
 *
 * @author arnold
 */
class Event
{
    /**
     * Global guzzle config
     * @var array
     */
    public static $guzzleConfig = [];
    
    /**
     * HTTP URL
     * @var \stdClass|\stdClass[]
     * @required
     */
    public $body;
    
    
    /**
     * Cast Http trigger entity
     * 
     * @return $this
     */
    public function cast()
    {
        if (!is_array($this->body)) {
            // Do nothing
        } elseif (count(array_filter(array_keys($this->body), 'is_string')) > 0) {
            $this->body = (object)$this->body;
        } else {
            $this->body = array_map(function($body) {
                return (object)$body;
            }, $this->body);
        }
        
        return parent::cast();
    }
    
    
    /**
     * Get the response by simulating the action
     * 
     * @return string
     */
    public function simulate()
    {
        return 'ok';
    }
    
    /**
     * Invoke for an action
     *
     * @param \Action $action
     * @return TriggerResponse
     */
    public function invoke(\Action $action)
    {
        $this->cast();

        return is_array($this->body) ? $this->invokeMultiple() : $this->invokeSingle();
    }
    
    /**
     * Invoke the trigger for a single event
     * 
     * @return TriggerResponse
     */
    protected function invokeSingle()
    {
        $event = new LtoEvent($this->body);
        
        $callback = function() use ($event) {
            return (object)['event' => (object)['body' => $this->body, 'hash' => $event->getHash()]];
        };
        
        return new TriggerResponse('ok', $callback, [$event]);
    }
    
    /**
     * Invoke the trigger for a multiple event
     * 
     * @return TriggerResponse
     */
    protected function invokeMultiple()
    {
        $bodies = $this->body;
        $events = [];
        
        foreach ($bodies as $body) {
            $events[] = new LtoEvent($body);
        }
        
        $callback = function() use ($bodies, $events) {
            return (object)['events' => array_map(function($body, $event) {
                return (object)['body' => $body, 'hash' => $event->getHash()];
            }, $bodies, $events)];
        };
        
        return new TriggerResponse('ok', $callback, $events);
    }
}
