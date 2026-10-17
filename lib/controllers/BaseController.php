<?php

namespace WHMCS\Module\Registrar\dynadot\controllers;

abstract class BaseController
{
    protected array $params;
    protected array $getData;
    protected array $postData;

    public function __construct(array $params = [], array $getData = [], array $postData = [])
    {
        $this->params = $params;
        $this->getData = $getData;
        $this->postData = $postData;
    }

    protected function response($action, $response): array
    {
        return [
            'action' => $action,
            'response' => $response
        ];
    }

    protected function error($action, $message): array
    {
        $response = new \stdClass();
        $response->code = 400;
        $response->error = new \stdClass();
        $response->error->description = $message;

        return [
            'action' => $action,
            'response' => $response
        ];
    }
}