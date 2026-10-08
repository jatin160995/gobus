<?php

namespace App\Http\Requests\TaxiGo;

use App\Services\TaxiGo\FareCalculator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QuoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $min = (int) config('taxigo.min_schedule_minutes');
        $max = (int) config('taxigo.max_schedule_days');

        return [
            'hub_id'                    => ['required', 'integer', Rule::exists('taxigo_hubs', 'id')->where('is_active', true)],
            'trip_type'                 => ['required', Rule::in(FareCalculator::TRIP_TYPES)],
            'neighbourhood_id'          => ['nullable', 'integer', 'required_without_all:zone_id,interurban_destination_id,vip_hours',
                                            Rule::exists('taxigo_neighbourhoods', 'id')->where('is_active', true)],
            'zone_id'                   => ['nullable', 'integer', Rule::exists('taxigo_zones', 'id')->where('is_active', true)],
            'interurban_destination_id' => ['nullable', 'integer', 'required_if:trip_type,interurban',
                                            Rule::exists('taxigo_interurban_destinations', 'id')->where('is_active', true)],
            'vip_hours'                 => ['nullable', 'integer', 'min:1', 'max:24', 'required_if:trip_type,vip_hourly'],
            'is_scheduled'              => ['sometimes', 'boolean'],
            'pickup_at'                 => ['nullable', 'date', 'required_if:is_scheduled,true,1',
                                            'after:' . now()->addMinutes($min - 1)->toIso8601String(),
                                            'before:' . now()->addDays($max)->toIso8601String()],
        ];
    }

    public function messages(): array
    {
        return [
            'pickup_at.after'  => 'Scheduled pickups must be at least ' . config('taxigo.min_schedule_minutes') . ' minutes from now.',
            'pickup_at.before' => 'Pickups can be scheduled up to ' . config('taxigo.max_schedule_days') . ' days ahead.',
            'neighbourhood_id.required_without_all' => 'Choose a neighbourhood.',
        ];
    }
}
