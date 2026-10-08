<?php

namespace App\Http\Controllers\Admin\TaxiGo;

use App\Http\Controllers\Controller;
use App\Models\TaxiGo\SplitBeneficiary;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Who receives each share of a fare. Percentages are copied onto every
 * payout when a ride is paid, so changes only affect new payments.
 */
class SplitController extends Controller
{
    public function index()
    {
        $beneficiaries = SplitBeneficiary::orderBy('sort')->get();

        return view('admin.taxigo.split.index', compact('beneficiaries'));
    }

    public function update(Request $request)
    {
        $beneficiaries = SplitBeneficiary::orderBy('sort')->get();

        $data = $request->validate([
            'rows'                       => ['required', 'array'],
            'rows.*.name'                => ['required', 'string', 'max:150'],
            'rows.*.percent'             => ['required', 'numeric', 'min:0', 'max:100'],
            'rows.*.channel'             => ['nullable', Rule::in(['mtn', 'orange', 'bank'])],
            'rows.*.msisdn'              => ['nullable', 'string', 'regex:/^[0-9 +]{9,15}$/'],
            'rows.*.bank_name'           => ['nullable', 'string', 'max:150'],
            'rows.*.bank_account_name'   => ['nullable', 'string', 'max:150'],
            'rows.*.bank_account_number' => ['nullable', 'string', 'max:60'],
        ], [
            'rows.*.msisdn.regex' => __('taxigo.msisdn_format'),
        ]);

        $total = $beneficiaries->sum(fn ($b) => (float) ($data['rows'][$b->id]['percent'] ?? $b->percent));
        if (abs($total - 100) > 0.001) {
            throw ValidationException::withMessages(['rows' => __('taxigo.split_total_error', ['total' => rtrim(rtrim(number_format($total, 2), '0'), '.')])]);
        }

        foreach ($beneficiaries as $b) {
            $row = $data['rows'][$b->id] ?? null;
            if (!$row) {
                continue;
            }

            $b->update([
                'name'                => $row['name'],
                'percent'             => $row['percent'],
                // The driver is paid on their own number, chosen per driver
                'channel'             => $b->is_driver_share ? $b->channel : ($row['channel'] ?? $b->channel),
                'msisdn'              => $b->is_driver_share ? null : (preg_replace('/\D/', '', $row['msisdn'] ?? '') ?: null),
                'bank_name'           => $row['bank_name'] ?? null,
                'bank_account_name'   => $row['bank_account_name'] ?? null,
                'bank_account_number' => $row['bank_account_number'] ?? null,
            ]);
        }

        return back()->with('success', __('taxigo.split_saved'));
    }
}
