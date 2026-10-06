<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Models\TaxiGo\Hub;
use App\Models\TaxiGo\InterurbanDestination;
use App\Models\TaxiGo\Neighbourhood;
use App\Models\TaxiGo\SplitBeneficiary;
use App\Models\TaxiGo\Zone;
use Illuminate\Database\Seeder;

/**
 * Initial TaxiGo data from the official tariff and the signed agreement.
 *
 * Safe to run more than once, including on production: existing rows are
 * left as they are, so values edited in the Dashboard are never overwritten.
 *
 *   php artisan db:seed --class=TaxiGoSeeder
 */
class TaxiGoSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedTariff();
        $this->seedSplitBeneficiaries();
        $this->seedSettings();
    }

    // -------------------------------------------------------
    // Hubs, zones, neighbourhoods, interurban fares (FCFA)
    // -------------------------------------------------------
    protected function seedTariff(): void
    {
        $hubs = [
            [
                'code' => 'DLA', 'name' => 'Douala International Airport', 'city' => 'Douala',
                'lat' => 4.0061, 'lng' => 9.7195,
                'night_start' => '22:00:00', 'night_end' => '05:00:00', 'night_surcharge' => 2000,
                'vip_hourly_rate' => 15000, 'sort' => 1,
                'zones' => ['A' => 10000, 'B' => 15000, 'C' => 20000, 'D' => 20000],
            ],
            [
                'code' => 'NSI', 'name' => 'Yaoundé Nsimalen International Airport', 'city' => 'Yaoundé',
                'lat' => 3.7226, 'lng' => 11.5533,
                'night_start' => '22:00:00', 'night_end' => '05:30:00', 'night_surcharge' => 2500,
                'vip_hourly_rate' => 15000, 'sort' => 2,
                'zones' => ['A' => 15000, 'B' => 12000, 'C' => 20000, 'D' => 18000],
            ],
        ];

        foreach ($hubs as $data) {
            $zones = $data['zones'];
            unset($data['zones']);

            $hub = Hub::firstOrCreate(['code' => $data['code']], $data);

            $sort = 0;
            foreach ($zones as $code => $fare) {
                Zone::firstOrCreate(
                    ['hub_id' => $hub->id, 'code' => $code],
                    ['name' => 'Zone ' . $code, 'fare' => $fare, 'sort' => ++$sort]
                );
            }

            $this->seedNeighbourhoods($hub);
            $this->seedInterurban($hub);
        }
    }

    /**
     * Neighbourhood lists per zone, from the TaxiGo Official Tariff sheet.
     */
    protected function neighbourhoods(): array
    {
        return [
            'DLA' => [
                'A' => ['Bonapriso', 'Akwa', 'Bali', 'Koumassi'],
                'B' => ['Bonamoussadi', 'Kotto', 'Makèpè', 'Denver'],
                'C' => ['Logbessou', 'Nyalla', 'Yassa', 'Japoma'],
                'D' => ['Bonabéri (Post-Pont)'],
            ],
            'NSI' => [
                'A' => ['Centre-ville', 'Hippodrome', 'Bastos', 'Golf'],
                'B' => ['Mvan', 'Odza', 'Messamendongo', 'Ahala'],
                'C' => ['Etoudi', 'Santa Barbara', 'Ngousso', 'Olembe'],
                'D' => ['Mendong', 'Biyem-Assi', 'Simbock', 'Damas'],
            ],
        ];
    }

    /**
     * Flat interurban fares from each airport, from the TaxiGo Official Tariff sheet.
     */
    protected function interurban(): array
    {
        return [
            'DLA' => [
                'Edéa'         => 50000,
                'Limbé/Buéa'   => 60000,
                'Kribi'        => 120000,
                'Nkongsamba'   => 100000,
                'Région Ouest' => 180000,
            ],
            'NSI' => [
                'Mbalmayo'     => 50000,
                'Obala'        => 50000,
                'Boumnyébel'   => 100000,
                'Kribi'        => 150000,
                'Région Ouest' => 180000,
            ],
        ];
    }

    protected function seedNeighbourhoods(Hub $hub): void
    {
        foreach ($this->neighbourhoods()[$hub->code] ?? [] as $zoneCode => $names) {
            $zone = $hub->zones()->where('code', $zoneCode)->first();
            if (!$zone) {
                continue;
            }

            foreach ($names as $name) {
                Neighbourhood::firstOrCreate(['zone_id' => $zone->id, 'name' => $name]);
            }
        }
    }

    protected function seedInterurban(Hub $hub): void
    {
        $sort = 0;
        foreach ($this->interurban()[$hub->code] ?? [] as $name => $fare) {
            InterurbanDestination::firstOrCreate(
                ['hub_id' => $hub->id, 'name' => $name],
                ['fare' => $fare, 'sort' => ++$sort]
            );
        }
    }

    // -------------------------------------------------------
    // Revenue split (Agreement §6). Accounts are added from the
    // Dashboard once the client sends them (due 20 Oct).
    // -------------------------------------------------------
    protected function seedSplitBeneficiaries(): void
    {
        $rows = [
            ['key' => 'bank_financing', 'name' => 'Vehicle financing bank',       'percent' => 40, 'channel' => 'bank'],
            ['key' => 'driver',         'name' => 'Driver',                       'percent' => 25, 'channel' => 'mtn', 'is_driver_share' => true],
            ['key' => 'alo_fuel',       'name' => 'ALO Technologies — fuel',      'percent' => 15, 'channel' => 'mtn'],
            ['key' => 'alo_commission', 'name' => 'ALO Technologies — commission', 'percent' => 10, 'channel' => 'mtn'],
            ['key' => 'adc',            'name' => 'ADC',                          'percent' => 5,  'channel' => 'bank'],
            ['key' => 'dealership',     'name' => 'Dealership (TaxiGo)',          'percent' => 5,  'channel' => 'mtn'],
        ];

        foreach ($rows as $i => $row) {
            SplitBeneficiary::firstOrCreate(['key' => $row['key']], $row + ['sort' => $i + 1]);
        }
    }

    // -------------------------------------------------------
    // Settings (existing settings table)
    // -------------------------------------------------------
    protected function seedSettings(): void
    {
        $settings = [
            // GoBus and GoRent are hidden for the TaxiGo release, never deleted
            ['key' => 'module_gobus_enabled',  'value' => '0', 'type' => 'boolean', 'group' => 'modules', 'label' => 'Show GoBus'],
            ['key' => 'module_gorent_enabled', 'value' => '0', 'type' => 'boolean', 'group' => 'modules', 'label' => 'Show GoRent'],

            ['key' => 'taxigo_referral_commission_amount', 'value' => '0',  'type' => 'number', 'group' => 'taxigo', 'label' => 'Commission per verified sign-up (FCFA)'],
            ['key' => 'taxigo_dispatch_offer_timeout_sec', 'value' => '30', 'type' => 'number', 'group' => 'taxigo', 'label' => 'Seconds a driver has to accept an offer'],
            ['key' => 'taxigo_scheduled_broadcast_minutes', 'value' => '60', 'type' => 'number', 'group' => 'taxigo', 'label' => 'Send scheduled rides to drivers this many minutes before pickup'],
            ['key' => 'taxigo_driver_offline_after_minutes', 'value' => '5', 'type' => 'number', 'group' => 'taxigo', 'label' => 'Mark a driver offline after this many minutes without a location'],
            ['key' => 'taxigo_complete_min_minutes',  'value' => '5', 'type' => 'number', 'group' => 'taxigo', 'label' => 'Minimum minutes on board before a ride can be completed'],
            ['key' => 'taxigo_complete_max_distance_km', 'value' => '2', 'type' => 'number', 'group' => 'taxigo', 'label' => 'Maximum distance from drop-off to complete a ride (km)'],
            ['key' => 'taxigo_support_phone', 'value' => '', 'type' => 'text', 'group' => 'taxigo', 'label' => 'Support phone shown in the apps'],
        ];

        foreach ($settings as $row) {
            Setting::firstOrCreate(['key' => $row['key']], $row);
        }
    }
}
