<?php

namespace App\Http\Controllers;

use App\Map;
use App\Node;
use Illuminate\Http\Request;

use App\Http\Requests;
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

        $return['nodes'] = Node::getAll();

        $return['totalvms'] = 0;
        foreach($return['nodes'] as $node)
        {
            $return['totalvms'] += $node->vmcount;
        }

        $return['nodes'] = array_values($return['nodes']->toArray());

        $tasks = Node::getTasks();

        $migrating = false;
        foreach($tasks as $task) {
            if($task['type'] === 'qmigrate' && empty($task['status']))
            {
                $migrating = true;
            }
        }

        $return['status'] = Node::getClusterStatus();

        if($migrating === false) {
            $return['recommendations'] = Node::makeRecommendations();

            $map = new Map();
            $return['maprecommendations'] = $map->recommended();

            if ($return['maprecommendations'][0] !== null) {
                $return['recommendations'] = [];
            } else {
                $return['maprecommendations'] = [];
            }
        } else {
            $return['recommendations'] = [];
            $return['maprecommendations'] = [];
        }

        return response()->json($return);


    }

    public function doRecommendations(Request $request)
    {

        $recommendations = json_decode($request->get('recommendations'));

        foreach($recommendations as $recommend)
        {

            $matches= preg_split('/ /', $recommend);

            $action = strtolower($matches[0]);
            $howmany = $matches[1];


            if($action == 'remove')
            {
                $from = $matches[3];
                $to = $matches[5];
            } else {
                //If it's an add reverse
                $to = $matches[3];
                $from = $matches[5];
            }

            Node::migrate($howmany, $from, $to);

        }
        return redirect()->route('tasks');
    }

    public function virtualmachines()
    {
        $virtualmachines = Node::returnAllVMS();

        $virtualmachines->sortBy('vmid');

        return view('virtualmachines', compact("virtualmachines"));

    }

    public function tasks(Request $request)
    {

        $tasks = Node::getTasks();


        return view('tasks', compact('tasks'));

    }

}

