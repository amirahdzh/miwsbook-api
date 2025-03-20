<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Author;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;

class AuthorController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:api', 'isOwner'])->except(['index', 'show']);
    }
    /**
     * Display a listing of the authors.
     */
    public function index()
    {
        $authors = Author::with('books')->get();
        return response()->json($authors);
    }

    /**
     * Store a newly created author in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'bio' => 'nullable|string',
            'photo' => 'nullable|image|max:2048', // Max 2MB
        ]);

        if ($request->hasFile('photo')) {
            $imageName = time() . '-author.' . $request->photo->extension();
            $request->photo->storeAs('authors', $imageName);
            $path = config('app.url') . '/storage/authors/';
            $validated['photo'] = $path . $imageName;
        }

        // if ($request->hasFile('photo')) {
        //     $validated['photo'] = $request->file('photo')->store('authors', 'public');
        // }

        $author = Author::create($validated);

        return response()->json([
            'message' => 'Author created successfully',
            'data' => $author
        ], 201);
    }

    /**
     * Display the specified author.
     */
    public function show(Author $author)
    {
        return response()->json($author->load('books'));
    }

    /**
     * Update the specified author in storage.
     */
    public function update(Request $request, Author $author)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'bio' => 'sometimes|string',
            'photo' => 'sometimes|image|max:2048',
        ]);

        if ($request->hasFile('photo')) {
            if ($author->photo) {
                Storage::disk('public')->delete($author->photo);
            }
            $validated['photo'] = $request->file('photo')->store('authors', 'public');
        }

        $author->update($validated);

        return response()->json([
            'message' => 'Author updated successfully',
            'data' => $author
        ]);
    }

    /**
     * Remove the specified author from storage.
     */
    public function destroy(Author $author)
    {
        if ($author->photo) {
            Storage::disk('public')->delete($author->photo);
        }

        $author->delete();

        return response()->json([
            'message' => 'Author deleted successfully'
        ]);
    }
}
