<?php

namespace App\Http\Controllers;

use App\Node;
use Illuminate\Http\Request;

use App\Http\Requests;
use Khill\Lavacharts\Lavacharts;

class HomeController extends Controller
{
    //
    public function index(Request $request)
    {

        return view('dashboard', compact('nodes', 'recommendations','totalvms', 'status'));
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

        $return['recommendations'] = Node::makeRecommendations();

        $return['status'] = Node::getClusterStatus();

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

    public function tasks(Request $request)
    {

        $tasks = Node::getTasks();


        return view('tasks', compact('tasks'));

    }

}

