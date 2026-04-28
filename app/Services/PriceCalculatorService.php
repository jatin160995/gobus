<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class PriceCalculatorService
{

    public function calculateCarPrice($basePrice, $providerId)
    {

        /*
        |--------------------------------------------------------------------------
        | Provider Commission
        |--------------------------------------------------------------------------
        */

        $commissionPercent = DB::table('providers')
            ->where('id', $providerId)
            ->value('commission_rate');

        $commissionPercent = $commissionPercent ?? 0;

        $commissionAmount = ($basePrice * $commissionPercent) / 100;


        /*
        |--------------------------------------------------------------------------
        | Insurance
        |--------------------------------------------------------------------------
        */

        $insurancePercent = DB::table('settings')
            ->where('key', 'insurance_car_percentage')
            ->value('value');

        $insurancePercent = $insurancePercent ? floatval($insurancePercent) : 2.2;

        $insuranceAmount = ($basePrice * $insurancePercent) / 100;


        /*
        |--------------------------------------------------------------------------
        | Platform Commission After Insurance
        |--------------------------------------------------------------------------
        */

        $platformCommission = $commissionAmount - $insuranceAmount;

        if ($platformCommission < 0) {
            $platformCommission = 0;
        }


        /*
        |--------------------------------------------------------------------------
        | VAT
        |--------------------------------------------------------------------------
        */

        $vatPercent = DB::table('settings')
            ->where('key', 'vat_tax_percentage')
            ->value('value');

        $vatPercent = $vatPercent ? floatval($vatPercent) : 0;

        $vatAmount = ($commissionAmount * $vatPercent) / 100;


        /*
        |--------------------------------------------------------------------------
        | Total
        |--------------------------------------------------------------------------
        */

        $grandTotal = $basePrice + $commissionAmount + $vatAmount;


        return [

            'base_price' => $basePrice,

            'commission' => $commissionAmount,

            'insurance' => $insuranceAmount,

            'platform_commission' => $platformCommission,

            'vat_percent' => $vatPercent,

            'vat_amount' => $vatAmount,

            'grand_total' => $grandTotal
        ];
    }


    public function calculateBusPrice($basePrice, $providerId)
    {

        /*
        |--------------------------------------------------------------------------
        | Provider Commission
        |--------------------------------------------------------------------------
        */

        $commissionPercent = DB::table('providers')
            ->where('id', $providerId)
            ->value('commission_rate');

        $commissionPercent = $commissionPercent ?? 0;

        $commissionAmount = ($basePrice * $commissionPercent) / 100;


        /*
        |--------------------------------------------------------------------------
        | Insurance
        |--------------------------------------------------------------------------
        */

        $insuranceValue = DB::table('settings')
            ->where('key', 'insurance_bus_fee')
            ->value('value');

        $insuranceValue = $insuranceValue ? floatval($insuranceValue) : 100;

        $insuranceAmount = $insuranceValue;


        /*
        |--------------------------------------------------------------------------
        | Platform Commission After Insurance
        |--------------------------------------------------------------------------
        */

        $platformCommission = $commissionAmount - $insuranceAmount;

        if ($platformCommission < 0) {
            $platformCommission = 0;
        }


        /*
        |--------------------------------------------------------------------------
        | VAT
        |--------------------------------------------------------------------------
        */

        $vatPercent = DB::table('settings')
            ->where('key', 'vat_tax_percentage')
            ->value('value');

        $vatPercent = $vatPercent ? floatval($vatPercent) : 0;

        $vatAmount = ($commissionAmount * $vatPercent) / 100;


        /*
        |--------------------------------------------------------------------------
        | Total
        |--------------------------------------------------------------------------
        */

        $grandTotal = $basePrice + $commissionAmount + $vatAmount;


        return [

            'base_price' => $basePrice,

            'commission' => $commissionAmount,

            'insurance' => $insuranceAmount,

            'platform_commission' => $platformCommission,

            'vat_percent' => $vatPercent,

            'vat_amount' => $vatAmount,

            'grand_total' => $grandTotal
        ];
    }
}