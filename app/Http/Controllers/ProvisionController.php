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
        $templateParts = preg_split('/::/', $request->get('template'));

        $node = $templateParts[0];
        $template = $templateParts[1];
        $name = $request->get('name');
        $storage = $request->get('storage');

        $idData = \Proxmox::get('/cluster/nextid');
        $newID = $idData['data'];

        $notes = $request->get('notes', '');

        $data = [
            'newid' => $newID,
            'name' => $name,
            'target' => $node,
            'full' => 1,
            'storage' => $storage,
            'description' => 'Provisioned from dashboard: '.date('m/d/Y').' -- NOTES: '.$notes,
        ];

        $url = '/nodes/'.$node.'/qemu/'.$template.'/clone';
        $clone = \Proxmox::create($url, $data);

        return redirect()->route('tasks');
    }
}
