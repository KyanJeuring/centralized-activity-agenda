<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.1.0',
    title: 'Centralized Activity Agenda API',
    description: 'OpenAPI documentation for the CAA backend.'
)]
#[OA\Server(
    url: '/api',
    description: 'Laravel API base path'
)]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'Token',
    description: 'Paste the access token as a Bearer token.'
)]
class OpenApiSpec
{
}
