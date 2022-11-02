<?php

namespace App\Http\Controllers;

use App\Models\Map;
use Illuminate\Http\Request;

class MapController extends Controller
{
    //
    public function index(Request $request)
    {
        $map = new Map();

        $current = $map->current();
        $recommendations = $map->recommended();

        return view('map', compact('current', 'recommendations'));
    }

    public function doRecommendations(Request $request)
    {
        $recommendations = json_decode($request->get('maprecommendations'));
        Map::doRecommendations($recommendations);

        return redirect()->route('tasks');
    }
}
