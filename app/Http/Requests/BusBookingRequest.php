<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class BusBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'schedule_id'              => 'required|exists:trip_schedules,id',
            'trip_type'                => 'required|in:one_way,round_trip',
            'seat_numbers'             => 'required|array|min:1',
            'seat_numbers.*'           => 'required|string',

            // Return trip — only required for round_trip
            'return_schedule_id'       => 'required_if:trip_type,round_trip|nullable|exists:trip_schedules,id',
            'return_seat_numbers'      => 'required_if:trip_type,round_trip|nullable|array|min:1',
            'return_seat_numbers.*'    => 'nullable|string',

            // Passengers — must match seat count
            'passengers'               => 'required|array|min:1',
            'passengers.*.name'        => 'required|string|max:100',
            'passengers.*.phone'       => 'nullable|string|max:30',
            'passengers.*.gender'      => 'nullable|in:male,female,other',
            'passengers.*.id_number'   => 'nullable|string|max:100',

            // Optional
            'offer_code'               => 'nullable|string|exists:offers,code',
            'notes'                    => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'schedule_id.required'          => 'Please select a departure schedule.',
            'schedule_id.exists'            => 'Selected schedule does not exist.',
            'trip_type.in'                  => 'Trip type must be one_way or round_trip.',
            'seat_numbers.required'         => 'Please select at least one seat.',
            'return_schedule_id.required_if'=> 'Return schedule is required for round trips.',
            'return_seat_numbers.required_if'=> 'Return seats are required for round trips.',
            'passengers.required'           => 'At least one passenger is required.',
            'passengers.*.name.required'    => 'Each passenger must have a name.',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Custom "after" validation: seat count must match passenger count
    |--------------------------------------------------------------------------
    */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {

            $seats      = $this->input('seat_numbers', []);
            $passengers = $this->input('passengers', []);

            if (count($seats) !== count($passengers)) {
                $validator->errors()->add(
                    'passengers',
                    'Number of passengers must match number of seats selected.'
                );
            }

            // For round trip, return seats must also match passenger count
            if ($this->input('trip_type') === 'round_trip') {
                $returnSeats = $this->input('return_seat_numbers', []);
                if (count($returnSeats) !== count($passengers)) {
                    $validator->errors()->add(
                        'return_seat_numbers',
                        'Number of return seats must match number of passengers.'
                    );
                }
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Return JSON error response (API style, consistent with project)
    |--------------------------------------------------------------------------
    */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'status'  => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422)
        );
    }
}