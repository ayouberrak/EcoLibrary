<?php

namespace App\Http\Controllers;
use OpenApi\Attributes as OA;

#[OA\Info(
    title:'EcoLibrary',
    version:'1.0.0',
    description:'Api pour tester une library briaf'
)]
#[OA\Server(
    url:'http://localhost:8000/api',
    description:'serveur local'
)]
#[OA\SecurityScheme(
    securityScheme:'sanctum',
    type:'http',
    scheme:'bearer',
    bearerFormat:'JWT'
)]
#[OA\Security(security: [['sanctum' => []]])]
abstract class Controller
{

}
