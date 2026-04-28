<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Passenger;
use Illuminate\Http\Request;

class PassengerController extends Controller
{
    /**
     * List all saved passengers for authenticated user
     */
    public function index(Request $request)
    {
        $passengers = Passenger::where('user_id', $request->user()->id)
            ->orderByDesc('is_default')
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'status' => true,
            'data'   => $passengers,
        ]);
    }

    /**
     * Store a new passenger
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'       => 'required|string|max:100',
            'phone'      => 'nullable|string|max:30',
            'gender'     => 'nullable|in:male,female,other',
            'id_number'  => 'nullable|string|max:100',
            'is_default' => 'nullable|boolean',
        ]);

        // If new passenger is set as default, unset existing default
        if (!empty($validated['is_default'])) {
            Passenger::where('user_id', $request->user()->id)
                ->update(['is_default' => 0]);
        }

        $passenger = Passenger::create([
            'user_id'    => $request->user()->id,
            'name'       => $validated['name'],
            'phone'      => $validated['phone'] ?? null,
            'gender'     => $validated['gender'] ?? 'male',
            'id_number'  => $validated['id_number'] ?? null,
            'is_default' => $validated['is_default'] ?? false,
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Passenger created successfully.',
            'data'    => $passenger,
        ], 201);
    }

    /**
     * Show a single passenger (must belong to user)
     */
    public function show(Request $request, $id)
    {
        $passenger = Passenger::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        return response()->json([
            'status' => true,
            'data'   => $passenger,
        ]);
    }

    /**
     * Update a passenger
     */
    public function update(Request $request, $id)
    {
        $passenger = Passenger::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $validated = $request->validate([
            'name'       => 'sometimes|required|string|max:100',
            'phone'      => 'nullable|string|max:30',
            'gender'     => 'nullable|in:male,female,other',
            'id_number'  => 'nullable|string|max:100',
            'is_default' => 'nullable|boolean',
        ]);

        // If setting this as default, clear others first
        if (!empty($validated['is_default'])) {
            Passenger::where('user_id', $request->user()->id)
                ->where('id', '!=', $passenger->id)
                ->update(['is_default' => 0]);
        }

        $passenger->update($validated);

        return response()->json([
            'status'  => true,
            'message' => 'Passenger updated successfully.',
            'data'    => $passenger->fresh(),
        ]);
    }

    /**
     * Delete a passenger
     */
    public function destroy(Request $request, $id)
    {
        $passenger = Passenger::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $passenger->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Passenger deleted successfully.',
        ]);
    }
}