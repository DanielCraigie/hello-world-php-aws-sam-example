<?php

namespace LambdaApp;

class LambdaFunction extends AbstractLambdaFunction
{
    /**
     * @param array $requestData data passed into function
     * @return string HTML body response
     */
    public function run(array $requestData): string
    {
        return "Goodbye";
    }
}
