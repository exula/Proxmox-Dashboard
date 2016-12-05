<?php

namespace App\Http\Controllers;

use App\Map;
use App\Node;
use Illuminate\Http\Request;

class Mapcontroller extends Controller
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

        foreach($recommendations as $recommend)
        {

            $matches= preg_split('/ /', $recommend);

            $vmid = $matches[1];
            $from = $matches[4];
            $to = $matches[6];

            Node::migrateVM($vmid, $from, $to);

        }
        return redirect()->route('tasks');
    }

}
