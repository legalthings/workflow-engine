<?php declare(strict_types=1);

/**
 * Step through a process by handling an event from legalevents.
 * Will only handle a single event.
 * Must be invoked to execute the next trigger.
 */
class EventProcessStepper implements ProcessStepper
{
    /**
     * Execute the current trigger if possible
     * Sends the response to the event service.
     *
     * @param string      $chainId
     * @param string      $chainLastHash
     * @param LTO\Account $account
     * @return $this
     */
    public function executeCurrentTrigger($chainId, $chainLastHash, $account)
    {
        $action = $this->getCurrentAllowedAction();
        $triggerResponse = $this->invokeTrigger($action->getKey());

        if ($triggerResponse === null) {
            return $this;
        }

        $response = $action->responses[$triggerResponse->response ?: $action->default_response];

        $res = $response->asEventResponse($this, $action, $action->actor);
        $res->data = $triggerResponse->data;

        $chain = new LTO\EventChain($chainId, $chainLastHash);

        foreach ($triggerResponse->additionalEvents as $event) {
            $event->addTo($chain)->signWith($account);
        }

        if ($res->data instanceof \Closure) {
            $res->data = call_user_func($res->data);
        }

        $responseEvent = new LTO\Event($res);
        $chain->add($responseEvent)->signWith($account);

        $legalEvent = App::getContainer()->get(LegalEvent::class);

        $request = $legalEvent->createRequest($chain);
        $httpSignature = new \LTO\HTTPSignature($request, ['(request-target)', 'date']);
        $signedRequest = $httpSignature->signWith($account);

        $legalEvent->send($signedRequest);

        return $this;
    }

}