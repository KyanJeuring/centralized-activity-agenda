<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TestController
{
    #[Route('/test-endpoint', name: 'test', methods: ['GET'])]
    public function test(): Response
    {
        return new Response(
            '[200] The Symfony backend is up and running! This is a test endpoint to verify connectivity with the frontend.',
            Response::HTTP_OK,
            [
                'Content-Type' => 'text/plain',
                'Access-Control-Allow-Origin' => '*',
            ]
        );
    }
}
