<?php

// we need to return error messages via the Runtime API
set_error_handler(function(int $errno, string $errstr, string $errfile, int $errline){
    echo "$errno:$errstr:$errfile:$errline";
    exit(1);
});

require 'vendor/autoload.php';

function response(string $body, int $statusCode = 200, array $headers = []): string
{
    return json_encode([
        'statusCode' => $statusCode,
        'headers' => array_merge([
            'Content-Type' => 'application/json',
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Headers' => 'Content-Type',
            'Access-Control-Allow-Methods' => 'OPTIONS,POST',
        ], $headers),
        'body' => $body,
    ]);
}

try {
    echo response((new \LambdaApp\LambdaFunction())->run(json_decode($argv[1], true)));
} catch (Throwable $exception) {
    echo response('Error: ' . $exception->getMessage(), 500);
}
