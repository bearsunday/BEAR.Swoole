<?php

return new class implements TransferInterface {
    /**
     * @var Response
     */
    private $response;

    public function setResponse(Response $response)
    {
        $this->response = $response;
    }

    public function __invoke(ResourceObject $ro, array $server)
    {
        $ro->toString();
        foreach ($ro->headers as $key => $value) {
            $this->response->header($key, $value);
        }
        $this->response->status($ro->code);
        $this->response->end($ro->view);
    }
};
