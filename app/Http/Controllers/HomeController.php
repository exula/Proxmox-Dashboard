<?php

namespace App\Http\Controllers;

use App\Http\Requests;
use App\Map;
use App\Node;
use Illuminate\Http\Request;
use Khill\Lavacharts\Lavacharts;

class HomeController extends Controller
{
    //
    public function index(Request $request)
    {
        return view('dashboard');
    }

    public function dash(Request $request)
    {
        return view('guestdash');
    }

    public function dashboardData()
    {
        return response()->json(Node::getDashboardData());
    }

    public function doRecommendations(Request $request)
    {
        $recommendations = json_decode($request->get('recommendations'));

        Node::doRecommendations($recommendations);

        return redirect()->route('dashboard');
    }

    public function virtualmachines()
    {
        $virtualmachines = Node::returnAllVMS();

        $virtualmachines->sortBy('vmid');

        return view('virtualmachines', compact('virtualmachines'));
    }

    public function tasks(Request $request)
    {
        $tasks = Node::getTasks();

        return view('tasks', compact('tasks'));
    }
}
