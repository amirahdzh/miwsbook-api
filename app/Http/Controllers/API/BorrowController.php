<?php

namespace App\Http\Controllers\API;

use Carbon\Carbon;
use App\Models\Book;
use App\Models\User;
use App\Models\Borrow;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class BorrowController extends Controller
{
    public function index()
    {
        // Retrieve all borrow records with related user and book data
        $borrows = Borrow::with(['user', 'book'])->get();

        // Format the response data
        $data = $borrows->map(function ($borrow) {
            return [
                'id' => $borrow->id,
                'return_date' => $borrow->return_date,
                'borrow_date' => $borrow->borrow_date,
                'book_id' => $borrow->book_id,
                'user_id' => $borrow->user_id,
                'created_at' => $borrow->created_at,
                'updated_at' => $borrow->updated_at,
                'user' => [
                    'id' => $borrow->user->id,
                    'name' => $borrow->user->name,
                    'email' => $borrow->user->email,
                    'role_id' => $borrow->user->role_id,
                    'created_at' => $borrow->user->created_at,
                    'updated_at' => $borrow->user->updated_at,
                ],
                'book' => [
                    'id' => $borrow->book->id,
                    'title' => $borrow->book->title,
                    'summary' => $borrow->book->summary,
                    'image' => $borrow->book->image,
                    'stok' => $borrow->book->stok,
                    'category_id' => $borrow->book->category_id,
                    'created_at' => $borrow->book->created_at,
                    'updated_at' => $borrow->book->updated_at,
                ],
            ];
        });

        // Return the response
        return response()->json([
            'message' => 'All Borrow Records',
            'data' => $data,
        ]);
    }

    public function store(Request $request)
    {
        // Validate the input
        $validator = Validator::make($request->all(), [
            'return_date' => 'nullable|date',
            'book_id' => 'required|exists:books,id'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Get the current authenticated user
        $currentUser = auth()->user();
        $user = User::find($currentUser->id);

        // Determine the return_date, defaulting to 7 days from now if not provided
        $returnDate = $request->input('return_date', Carbon::now()->addDays(7));

        // Retrieve the book and check if stock is available
        $book = Book::find($request->input('book_id'));
        if ($book->stock <= 0) {
            return response()->json([
                'message' => 'Book is out of stock.',
            ], 400);
        }

        // Create or update the borrow record
        $borrowData = $user->borrows()->updateOrCreate(
            ['user_id' => $user->id, 'book_id' => $request->input('book_id')],
            [
                'return_date' => $returnDate,
                'borrow_date' => Carbon::now(),
            ]
        );

        // Decrease the book stock by 1
        $book->stock -= 1;
        $book->save();

        // Return a successful response
        return response()->json([
            'message' => 'Borrow successfully updated or created',
            'borrow' => $borrowData,
        ], 200);
    }

    public function update(Request $request, $id)
    {
        $borrow = Borrow::findOrFail($id);
        $borrow->return_date = $request->input('return_date');
        $borrow->save();

        return response()->json($borrow);
    }

    public function borrowedByUser()
    {
        // Get the current authenticated user
        $user = Auth::user();

        // Retrieve borrowed books with related book information
        $borrows = Borrow::with('book')
            ->where('user_id', $user->id)
            ->get();

        // Transform the data to include remaining days and overdue status
        $borrows = $borrows->map(function ($borrow) {
            return [
                'id' => $borrow->id,
                'book' => [
                    'id' => $borrow->book->id,
                    'title' => $borrow->book->title,
                    'author' => $borrow->book->author, // Ensure this field exists
                    'image' => $borrow->book->image,
                    // Add more book fields as necessary
                ],
                'borrow_date' => $borrow->borrow_date,
                'return_date' => $borrow->return_date,
                'days_remaining' => $this->calculateDaysRemaining($borrow->return_date),
                'is_overdue' => $this->isOverdue($borrow->return_date),
            ];
        });

        return response()->json([
            'message' => 'Borrowed books retrieved successfully',
            'data' => $borrows,
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $borrow = Borrow::findOrFail($id);
        $book = $borrow->book;

        // Increase the stock of the book
        $book->stock += 1;
        $book->save();

        // Delete the borrow
        $borrow->delete();

        return response()->json(['message' => 'Borrow deleted successfully']);
    }

    /**
     * Calculate the number of days remaining until the return date.
     *
     * @param  string  $returnDate
     * @return int
     */
    private function calculateDaysRemaining($returnDate)
    {
        $returnDate = Carbon::parse($returnDate);
        $today = Carbon::now();
        return $returnDate->greaterThanOrEqualTo($today)
            ? $returnDate->diffInDays($today)
            : -$today->diffInDays($returnDate);
    }


    /**
     * Determine if the book is overdue.
     *
     * @param  string  $returnDate
     * @return bool
     */
    private function isOverdue($returnDate)
    {
        return $this->calculateDaysRemaining($returnDate) < 0;
    }
}
