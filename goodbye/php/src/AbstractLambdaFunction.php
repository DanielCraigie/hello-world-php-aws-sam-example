<?php

namespace LambdaApp;

abstract class AbstractLambdaFunction
{
    /**
     * @param array $requestData data passed into function
     * @return string HTML body response
     */
    abstract public function run(array $requestData): string;
}
