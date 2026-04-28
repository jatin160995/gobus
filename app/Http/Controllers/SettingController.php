<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\ActivityLog;

class SettingController extends Controller
{
    /**
     * List settings by group
     */
    public function index()
    {
        $groups = \App\Models\Setting::select('group')
            ->distinct()
            ->pluck('group');

        $settings = \App\Models\Setting::orderBy('group')->get()
            ->groupBy('group');

        return view('admin.settings.index', compact('groups', 'settings'));
    }
 public function create()
    {
        return view('admin.settings.create');
    }

    public function store(Request $request)
    {
        Setting::create($request->all());

        return redirect()->route('settings.index')
            ->with('success', 'Setting created successfully');
    }

    /**
     * Edit a setting
     */
    public function edit($id)
    {
        $setting = Setting::findOrFail($id);

        return view('admin.settings.edit', compact('setting'));
    }

    /**
     * Update setting
     */
    public function update(Request $request)
{
    foreach ($request->settings ?? [] as $id => $value) {
        \App\Models\Setting::where('id', $id)
            ->update(['value' => is_array($value) ? json_encode($value) : $value]);
    }
 // Activity Log (automatic style)
        
        //die();
        ActivityLog::create([
            'user_id' => Auth::id(),
            'action'  => 'Updated Setting' 
        ]);
    // activity()
    //     ->module('settings')
    //     ->action('Updated settings')
    //     ->log('Admin updated system settings');

    return back()->with('success', 'Settings updated successfully');
}

   
}
