<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use OpenApi\Attributes as OA;


class AuthController extends Controller
{
     #[OA\Post(
        path: "/register",
        summary: "register",
        tags: ["Auth"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(property: "name", type: "string"),
                    new OA\Property(property: "email", type: "string", format: "email"),
                    new OA\Property(property: "password", type: "string", format: "password")
                ],
                required: ["name", "email", "password"]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "registred",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(property: "user_id", type: "integer"),
                        new OA\Property(property: "token", type: "string")
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: "invalid ",
            )
        ]
    )]
    public function register(Request $request){
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);


        $user = User::create([
            'name'=>$request->name,
            'email'=>$request->email,
            'password'=>Hash::make($request->password)
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'token'=>$token,
            'user'=>$user
        ]);
    }


    #[OA\Post(
        path:"/login",
        summary:"login",
        tags:["Auth"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type:"object",
                properties:[
                    new OA\Property(property: "email",type:"string",format:"email"),
                    new OA\Property(property:"password",type:"string",format:"password")
                ],
                required:["email","password"]
            )
        ),
        responses:[
            new OA\Response(
                response:201,
                description:"loged",
                content: new OA\JsonContent(
                    type:"object",
                    properties:[
                        new OA\Property(property: "user_id", type: "integer"),
                        new OA\Property(property: "token", type: "string")
                    ]
                )
            ),
            new OA\Response(
                response:442,
                description:"invalid",
            )
        ]
    )]
    public function login(Request $request){
        $request->validate([
            'email'=> 'required|string|email|max:255',
            'password'=>'required|string|min:8',
        ]);

        $user = User::whereEmail($request->email)->first();

        if(!$user || !Hash::check($request->password , $user->password)){
            return response()->json(['error'=>'email or password invalid'], 401);
        }


        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'token'=>$token,
            'user'=>$user
        ]);
    }


    #[OA\Post(
        path: "/logout",
        summary: "logout",
        tags: ["Auth"],
        security: [["sanctum" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "user is ",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "tu es deconecter")
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: "user non authentifie"
            )
        ]
    )]
    public function logout(Request $request){
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message'=>'tu es deconecter'
        ]);
    }
}
