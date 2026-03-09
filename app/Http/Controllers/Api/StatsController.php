<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Books;
use App\Models\Categorie;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Statistics", description: "Administrative statistics and monitoring")]
class StatsController extends Controller
{
    #[OA\Get(
        path: "/admin/stats/global",
        summary: "Get global statistics of the library",
        tags: ["Statistics"],
        security: [["sanctum" => []]],
        responses: [
            new OA\Response(response: 200, description: "Successful operation"),
            new OA\Response(response: 403, description: "Forbidden")
        ]
    )]
    public function global()
    {
        return response()->json([
            'total_books' => Books::count(),
            'total_categories' => Categorie::count(),
            'total_items_in_stock' => Books::sum('total_quantity'),
            'total_degraded_items' => Books::sum('degraded_quantity'),
            'global_health_percentage' => Books::sum('total_quantity') > 0 
                ? round((1 - (Books::sum('degraded_quantity') / Books::sum('total_quantity'))) * 100, 2) 
                : 100
        ]);
    }

    #[OA\Get(
        path: "/admin/stats/top-books",
        summary: "Get most viewed books",
        tags: ["Statistics"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(name: "limit", in: "query", schema: new OA\Schema(type: "integer", default: 5))
        ],
        responses: [
            new OA\Response(response: 200, description: "Successful operation")
        ]
    )]
    public function topBooks(Request $request)
    {
        $limit = $request->query('limit', 5);
        $books = Books::orderBy('views', 'desc')->take($limit)->get(['id', 'title', 'author', 'views']);
        
        return response()->json($books);
    }

    #[OA\Get(
        path: "/admin/stats/degraded",
        summary: "Detailed report of degraded books",
        tags: ["Statistics"],
        security: [["sanctum" => []]],
        responses: [
            new OA\Response(response: 200, description: "Successful operation")
        ]
    )]
    public function degradedReport()
    {
        $books = Books::where('degraded_quantity', '>', 0)
            ->orderBy('degraded_quantity', 'desc')
            ->get(['id', 'title', 'author', 'total_quantity', 'degraded_quantity']);
            
        return response()->json([
            'count' => $books->count(),
            'books' => $books
        ]);
    }
}
