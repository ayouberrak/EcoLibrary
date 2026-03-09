<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Books;
use App\Models\User;
use App\Notifications\BookDegraded;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

class BookController extends Controller
{
    #[OA\Get(
        path: "/books",
        summary: "List all books with optional title search",
        tags: ["Books"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(
                name: "title",
                in: "query",
                description: "Search by book title",
                required: false,
                schema: new OA\Schema(type: "string")
            )
        ],
        responses: [
            new OA\Response(response: 200, description: "Successful operation")
        ]
    )]
    public function index(Request $request){
        $query = Books::query();

        if ($request->has('title')) {
            $query->where('title', 'like', '%' . $request->title . '%');
        }

        return $query->get();
    }

    #[OA\Post(
        path: "/books",
        summary: "Add a new book",
        tags: ["Books"],
        security: [["sanctum" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["category_id", "title", "author", "total_quantity"],
                properties: [
                    new OA\Property(property: "category_id", type: "integer", example: 1),
                    new OA\Property(property: "title", type: "string", example: "The Great Gatsby"),
                    new OA\Property(property: "author", type: "string", example: "F. Scott Fitzgerald"),
                    new OA\Property(property: "description", type: "string", example: "A classic novel set in the Roaring Twenties."),
                    new OA\Property(property: "total_quantity", type: "integer", example: 10)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Book created"),
            new OA\Response(response: 422, description: "Validation error"),
            new OA\Response(response: 403, description: "Forbidden")
        ]
    )]
    public function store(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'title' => 'required|string',
            'author' => 'required|string',
            'description' => 'nullable|string',
            'total_quantity' => 'required|integer|min:0'
        ]);

        $book = Books::create([
            'category_id' => $request->category_id,
            'title' => $request->title,
            'slug' => Str::slug($request->title) . '-' . uniqid(),
            'author' => $request->author,
            'description' => $request->description,
            'total_quantity' => $request->total_quantity,
        ]);

        return response()->json($book, 201);
    }

    #[OA\Get(
        path: "/books/{id}",
        summary: "Get book details",
        tags: ["Books"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "Book ID",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(response: 200, description: "Successful operation"),
            new OA\Response(response: 404, description: "Book not found")
        ]
    )]
    public function show($id){
        return Books::findOrFail($id);
    }

    #[OA\Get(
        path: "/categories/{category_slug}/books/{book_slug}",
        summary: "Get book by category slug and book slug",
        tags: ["Books"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(name: "category_slug", in: "path", required: true, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "book_slug", in: "path", required: true, schema: new OA\Schema(type: "string"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Successful operation"),
            new OA\Response(response: 404, description: "Book not found")
        ]
    )]
    public function showBySlug($category_slug, $book_slug)
    {
        $book = Books::where('slug', $book_slug)
            ->whereHas('categories', function($query) use ($category_slug) {
                $query->where('slug', $category_slug);
            })->firstOrFail();

        return response()->json($book);
    }

    #[OA\Put(
        path: "/books/{id}",
        summary: "Update an existing book",
        tags: ["Books"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "category_id", type: "integer"),
                    new OA\Property(property: "title", type: "string"),
                    new OA\Property(property: "author", type: "string"),
                    new OA\Property(property: "description", type: "string"),
                    new OA\Property(property: "total_quantity", type: "integer"),
                    new OA\Property(property: "degraded_quantity", type: "integer")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Book updated"),
            new OA\Response(response: 422, description: "Validation error"),
            new OA\Response(response: 403, description: "Forbidden"),
            new OA\Response(response: 404, description: "Book not found")
        ]
    )]
    public function update(Request $request, $id)
    {
        $book = Books::findOrFail($id);
        $oldDegraded = $book->degraded_quantity;

        $request->validate([
            'category_id' => 'sometimes|exists:categories,id',
            'title' => 'sometimes|string',
            'author' => 'sometimes|string',
            'description' => 'nullable|string',
            'total_quantity' => 'sometimes|integer|min:0',
            'degraded_quantity' => 'sometimes|integer|min:0'
        ]);

        $data = $request->only(['category_id', 'title', 'author', 'description', 'total_quantity', 'degraded_quantity']);
        
        if (isset($data['title'])) {
            $data['slug'] = Str::slug($data['title']) . '-' . uniqid();
        }

        $book->update($data);

        if (isset($data['degraded_quantity']) && $data['degraded_quantity'] > $oldDegraded) {
            $admins = User::where('is_admin', true)->get();
            Notification::send($admins, new BookDegraded($book));
        }

        return response()->json($book);
    }

    #[OA\Delete(
        path: "/books/{id}",
        summary: "Delete a book",
        tags: ["Books"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 204, description: "Book deleted"),
            new OA\Response(response: 403, description: "Forbidden"),
            new OA\Response(response: 404, description: "Book not found")
        ]
    )]
    public function destroy($id)
    {
        $book = Books::findOrFail($id);
        $book->delete();

        return response()->json(null, 204);
    }

    #[OA\Get(
        path: "/categories/{category}/books",
        summary: "List books in a specific category",
        tags: ["Books"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(
                name: "category",
                in: "path",
                description: "Category ID or Slug",
                required: true,
                schema: new OA\Schema(type: "string")
            )
        ],
        responses: [
            new OA\Response(response: 200, description: "Successful operation")
        ]
    )]
    public function showByCategorie($category){
        if (is_numeric($category)) {
            return Books::where('category_id', $category)->get();
        }

        return Books::whereHas('categories', function($query) use ($category) {
            $query->where('slug', $category);
        })->get();
    }
}
