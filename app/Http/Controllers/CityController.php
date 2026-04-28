<?php

namespace App\Http\Controllers;

use App\Models\City;
use Illuminate\Http\Request;

class CityController extends Controller
{
    // List all cities
    public function index()
    {
        $cities = City::orderBy('id', 'DESC')->paginate(20);

        return view('admin.cities.index', compact('cities'));
    }

    // Show form
    public function create()
    {
        return view('admin.cities.create');
    }

    // Store new city
    public function store(Request $req)
    {
        $req->validate([
            'name'      => 'required|string|max:100',
            'city_code' => 'required|string|max:11|unique:cities,city_code',
            'country'   => 'nullable|string|max:22',
        ]);

        City::create($req->only('name', 'city_code', 'country'));

        return redirect()->route('admin.cities.index')
            ->with('success', 'City created successfully.');
    }

    // Edit form
    public function edit($id)
    {
        $city = City::findOrFail($id);

        return view('admin.cities.edit', compact('city'));
    }

    // Update city
    public function update(Request $req, $id)
    {
        $city = City::findOrFail($id);

        $req->validate([
            'name'      => 'required|string|max:100',
            'city_code' => "required|string|max:11|unique:cities,city_code,{$city->id}",
            'country'   => 'nullable|string|max:22',
        ]);

        $city->update($req->only('name', 'city_code', 'country'));

        return redirect()->route('admin.cities.index')
            ->with('success', 'City updated successfully.');
    }

    // Delete
    public function destroy($id)
    {
        $city = City::findOrFail($id);
        $city->delete();

        return redirect()->route('admin.cities.index')
            ->with('success', 'City deleted successfully.');
    }
}
