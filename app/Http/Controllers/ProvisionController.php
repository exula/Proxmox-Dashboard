<?php

namespace App\Http\Controllers;

use App\Node;
use Illuminate\Http\Request;

class ProvisionController extends Controller
{
    //

    public function create(Request $request)
    {

        $templates = Node::getTemplates();
        $storage = Node::getStorage();

        return view('provision', compact('templates', 'storage'));
    }

    public function store(Request $request)
    {

        $name = $request->get('name');
        $template = $request->get('template');
        $storage = $request->get('storage');

        dd($template);



    }

}
