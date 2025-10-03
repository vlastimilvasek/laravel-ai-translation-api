<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="Laravel AI Translation API",
 *     description="REST API pro překlad HTML textů pomocí Claude AI a ChatGPT. Podporuje 10 jazyků a zachovává HTML strukturu.",
 *     @OA\Contact(
 *         email="support@example.com"
 *     ),
 *     @OA\License(
 *         name="MIT",
 *         url="https://opensource.org/licenses/MIT"
 *     )
 * )
 *
 * @OA\Server(
 *     url="http://localhost:8000",
 *     description="Local Development Server"
 * )
 *
 * @OA\Server(
 *     url="https://api.example.com",
 *     description="Production Server"
 * )
 *
 * @OA\SecurityScheme(
 *     type="http",
 *     description="Laravel Sanctum Bearer Token",
 *     name="Authorization",
 *     in="header",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     securityScheme="bearerAuth"
 * )
 *
 * @OA\Tag(
 *     name="Translation",
 *     description="API Endpoints pro překlad textů"
 * )
 *
 * @OA\Tag(
 *     name="Claude",
 *     description="Claude AI komunikace"
 * )
 */
class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
}
