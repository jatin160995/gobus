<?php
namespace App\Traits;

use Illuminate\Support\Facades\Auth;
use App\Models\ProviderUser;

trait ProviderHelpers
{
    public function providerId()
    {
        $mapping = ProviderUser::where('user_id', Auth::id())->first();

        if (!$mapping) {
            abort(403, "This user is not attached to any provider.");
        }

        return $mapping->provider_id;
    }
}
