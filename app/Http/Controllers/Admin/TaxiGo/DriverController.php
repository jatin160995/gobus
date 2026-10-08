<?php

namespace App\Http\Controllers\Admin\TaxiGo;

use App\Http\Controllers\Controller;
use App\Models\TaxiGo\Driver;
use App\Models\TaxiGo\Hub;
use App\Models\TaxiGo\Ride;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * Drivers are TaxiGo employees: their app login is created here, never by
 * self sign-up.
 */
class DriverController extends Controller
{
    public function index(Request $request)
    {
        $drivers = Driver::with(['user', 'hub', 'vehicle'])
            ->withCount(['rides as completed_rides' => fn ($q) => $q->where('status', 'completed')])
            ->when($request->hub, fn ($q, $hub) => $q->where('hub_id', $hub))
            ->when($request->search, fn ($q, $s) => $q->whereHas('user', fn ($u) => $u
                ->where('name', 'like', "%{$s}%")->orWhere('phone', 'like', "%{$s}%")))
            ->orderByDesc('is_active')->orderBy('id', 'desc')
            ->paginate(20)->withQueryString();

        $stats = [
            'total'  => Driver::count(),
            'active' => Driver::where('is_active', true)->count(),
            'online' => Driver::where('is_active', true)->where('is_online', true)->count(),
        ];

        return view('admin.taxigo.drivers.index', ['drivers' => $drivers, 'stats' => $stats, 'hubs' => Hub::orderBy('sort')->get()]);
    }

    public function create()
    {
        return view('admin.taxigo.drivers.form', ['driver' => new Driver(['payout_channel' => 'mtn', 'is_active' => true]), 'hubs' => Hub::orderBy('sort')->get()]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        DB::transaction(function () use ($request, $data) {
            $user = User::create([
                'name'     => $data['name'],
                'phone'    => $data['phone'],
                'email'    => $data['email'] ?? null,
                'password' => Hash::make($data['password']),
                'role'     => 'driver',
            ]);

            Driver::create([
                'user_id'        => $user->id,
                'hub_id'         => $data['hub_id'],
                'licence_number' => $data['licence_number'] ?? null,
                'payout_channel' => $data['payout_channel'],
                'payout_msisdn'  => preg_replace('/\D/', '', $data['payout_msisdn']),
                'is_active'      => $request->boolean('is_active'),
                'photo'          => $request->file('photo')?->store('taxigo/drivers', 'public'),
            ]);
        });

        return redirect()->route('admin.taxigo.drivers.index')->with('success', __('taxigo.driver_created', ['name' => $data['name']]));
    }

    public function edit(Driver $driver)
    {
        return view('admin.taxigo.drivers.form', ['driver' => $driver->load('user', 'vehicle'), 'hubs' => Hub::orderBy('sort')->get()]);
    }

    public function update(Request $request, Driver $driver)
    {
        $data = $this->validated($request, $driver);

        DB::transaction(function () use ($request, $data, $driver) {
            $driver->user->update(array_filter([
                'name'     => $data['name'],
                'phone'    => $data['phone'],
                'email'    => $data['email'] ?? null,
                'password' => !empty($data['password']) ? Hash::make($data['password']) : null,
            ], fn ($v, $k) => $v !== null || $k === 'email', ARRAY_FILTER_USE_BOTH));

            $photo = $driver->photo;
            if ($request->hasFile('photo')) {
                $photo && Storage::disk('public')->delete($photo);
                $photo = $request->file('photo')->store('taxigo/drivers', 'public');
            }

            $active = $request->boolean('is_active');
            $driver->update([
                'hub_id'         => $data['hub_id'],
                'licence_number' => $data['licence_number'] ?? null,
                'payout_channel' => $data['payout_channel'],
                'payout_msisdn'  => preg_replace('/\D/', '', $data['payout_msisdn']),
                'is_active'      => $active,
                'is_online'      => $active ? $driver->is_online : false,
                'photo'          => $photo,
            ]);
        });

        return redirect()->route('admin.taxigo.drivers.index')->with('success', __('taxigo.driver_saved', ['name' => $data['name']]));
    }

    public function toggle(Driver $driver)
    {
        if ($driver->is_active && Ride::where('driver_id', $driver->id)->whereIn('status', Ride::ACTIVE_STATUSES)->exists()) {
            return back()->with('error', __('taxigo.driver_busy'));
        }

        $driver->update(['is_active' => !$driver->is_active, 'is_online' => false]);
        $driver->user->update(['status' => $driver->is_active ? 'active' : 'inactive']);

        return back()->with('success', __($driver->is_active ? 'taxigo.driver_activated' : 'taxigo.driver_deactivated', ['name' => $driver->user->name]));
    }

    private function validated(Request $request, ?Driver $driver = null): array
    {
        $userId = $driver?->user_id;

        // The phone is the driver-app login: keep digits only, however it was typed
        $request->merge(['phone' => preg_replace('/\D/', '', (string) $request->input('phone'))]);

        return $request->validate([
            'name'           => ['required', 'string', 'max:150'],
            'phone'          => ['required', 'string', 'regex:/^[0-9 +]{9,15}$/', Rule::unique('users', 'phone')->ignore($userId)],
            'email'          => ['nullable', 'email', 'max:150', Rule::unique('users', 'email')->ignore($userId)],
            'password'       => [$driver ? 'nullable' : 'required', 'string', 'min:8'],
            'hub_id'         => ['required', Rule::exists('taxigo_hubs', 'id')],
            'licence_number' => ['nullable', 'string', 'max:50'],
            'payout_channel' => ['required', Rule::in(['mtn', 'orange'])],
            'payout_msisdn'  => ['required', 'string', 'regex:/^[0-9 +]{9,15}$/'],
            'photo'          => ['nullable', 'image', 'max:4096'],
        ], [
            'phone.regex'         => __('taxigo.msisdn_format'),
            'payout_msisdn.regex' => __('taxigo.msisdn_format'),
        ]);
    }
}
