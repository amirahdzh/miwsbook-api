<?php

namespace App\Http\Controllers\API;

use App\Models\Book;
use App\Models\Category;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:api', 'isOwner'])->except(['index', 'show', 'getBooksByCategory']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categories = Category::all();

        return response()->json([
            'message' => 'Data categories retrieved successfully',
            'data' => $categories,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $category = new Category;
        $category->name = $request->name;
        $category->save();

        return response()->json([
            'message' => 'Category created successfully',
            'data' => $category,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $category = Category::with('books')->find($id);

        if (!$category) {
            return response()->json([
                "message" => "Category not found",
            ], 404);
        }

        return response()->json([
            "message" => "Category details retrieved successfully",
            "data" => [
                "id" => $category->id,
                "name" => $category->name,
                "created_at" => $category->created_at,
                "updated_at" => $category->updated_at,
                "books" => $category->books->map(function ($book) {
                    return [
                        "id" => $book->id,
                        "title" => $book->title,
                        "summary" => $book->summary,
                        "image" => $book->image,
                        "year" => $book->year,
                        "created_at" => $book->created_at,
                        "updated_at" => $book->updated_at,
                    ];
                }),
            ],
        ]);
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json([
                "message" => "Category not found",
            ], 404);
        }

        // Check if the category is 'No Category' or 'Owner'
        if ($category->name === 'No Category' || $category->name === 'Owner') {
            return response()->json([
                "message" => "Cannot update this category",
            ], 403);
        }

        // Validate data
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        // Update category data
        $category->name = $request->name;
        $category->save();

        return response()->json([
            "message" => "Category updated successfully",
            "data" => $category,
        ]);
    }

    /**
     * Remove the specified category from storage.
     */
    public function destroy(string $id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json([
                "message" => "Category not found",
            ], 404);
        }

        // Pastikan tidak ada penghapusan paksa tanpa mengelola hubungan many-to-many
        $category->books()->detach(); // Lepaskan hubungan dari pivot table

        $category->delete();

        return response()->json([
            "message" => "Category deleted successfully",
        ]);
    }


    /**
     * Get books by category.
     */
    public function getBooksByCategory($categoryId)
    {
        $category = Category::find($categoryId);
        if (!$category) {
            return response()->json(['message' => 'Category not found'], 404);
        }

        $books = $category->books; // Menggunakan relasi many-to-many

        return response()->json([
            "message" => "Books retrieved successfully",
            "data" => $books,
        ]);
    }


    public function attachCategories(Request $request, $bookId)
    {
        $book = Book::find($bookId);
        if (!$book) {
            return response()->json(['message' => 'Book not found'], 404);
        }

        $validated = $request->validate([
            'categories' => 'required|array',
            'categories.*' => 'exists:categories,id',
        ]);

        $book->categories()->attach($validated['categories']);

        return response()->json([
            'message' => 'Categories added to book successfully',
            'data' => $book->categories
        ]);
    }

    public function detachCategories(Request $request, $bookId)
    {
        $book = Book::find($bookId);
        if (!$book) {
            return response()->json(['message' => 'Book not found'], 404);
        }

        $validated = $request->validate([
            'categories' => 'required|array',
            'categories.*' => 'exists:categories,id',
        ]);

        $book->categories()->detach($validated['categories']);

        return response()->json([
            'message' => 'Categories removed from book successfully',
            'data' => $book->categories
        ]);
    }
}
