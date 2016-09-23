<?php

namespace App;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

use ProxmoxVE\Proxmox;

class Node extends Model
{
    //
    private static $data = [
        "cias-proxmox-1" => [
            "load" => "3.2",
            "memory" => ".51",
            "vmcount" => "5"
        ],
        "cias-proxmox-2" => [
            "load" => "15",
            "memory" => ".90",
            "vmcount" => "13"
        ],
        "cias-proxmox-3" => [
            "load" => "1",
            "memory" => ".80",
            "vmcount" => "13"
        ],
        "cias-proxmox-4" => [
            "load" => "1.2",
            "memory" => ".31",
            "vmcount" => "4"
        ],
    ];

    private static $alreadyMigrated = [];

    public static function getAll()
    {
        //Lets fake some data right now

        $allnodes = \Proxmox::get('/nodes');

        self::$data = [];
        foreach($allnodes['data'] as $node)
        {

            $nodeData = \Proxmox::get('/nodes/'.$node['node'].'/qemu/');


            self::$data[$node['node']]['load'] = round($node['cpu'],4);
            self::$data[$node['node']]['memory'] = round(($node['maxmem'] / $node['mem'])/100,4);
            self::$data[$node['node']]['vmcount'] = 0;

            foreach($nodeData['data'] as $vms)
            {
                if($vms['status'] == 'running')
                {
                    self::$data[$node['node']]['vmcount']++;
                }
            }

        }

        $collection = new Collection();

        foreach(self::$data as $nodeName => $data)
        {
            $node = new Node();
            $node->name = $nodeName;
            $node->load = $data['load'];
            $node->memory = $data['memory'];
            $node->vmcount = $data['vmcount'];

            $collection->add($node);
        }

        //Lets add some weighting to the vmcount
        $mAverage = 0;
        $mCount = 0;
        $totalCount = 0;
        $collection->each(function($n) use (&$mAverage, &$mCount, &$totalCount) {
            $mAverage += $n->memory;
            $mCount++;
            $totalCount += $n->vmcount;
        });

        $mAverage = $mAverage / $mCount;

        $collection->each(function($n) use ($mAverage, $totalCount){
           //If the memory usage is less than the average, lets pretend there are less vms on the machine
            if($n->memory < $mAverage)
            {
                $diff = abs($n->memory - $mAverage);

                $vmOffset = floor(($diff * $totalCount)/4);

                $n->vmcount -= $vmOffset;
                if($n->vmcount <= 0)
                {
                    $n->vmcount = 0;
                }

            } elseif($n->memory > $mAverage)
            {
                $diff = abs($n->memory - $mAverage);

                $vmOffset = floor(($diff * $totalCount)/4);

                $n->vmcount += $vmOffset;
                if($n->vmcount <= 0)
                {
                    $n->vmcount = 0;
                }
            }
        });



        return $collection->sortBy('name');

    }

    public static function makeRecommendations()
    {

        $recommend = [];

        $recommend = array_merge($recommend, Node::recommendVMCount());

        return $recommend;

    }

    private static function recommendVMCount()
    {
        //Lets do vmcount first
        $nodes = self::getAll();
        $totalVMs = 0;
        $recommend = [];

        foreach($nodes as $node)
        {
            $totalVMs += $node->vmcount;
        }
        $vmcountAverage = ceil($totalVMs / count($nodes));

        $nodeCount = ["add" => [], "remove" => []];

        foreach($nodes as $node)
        {
            //Lets determine what action needs to be taken to bring all the nodes to the average vm count

            $diff = $vmcountAverage - $node->vmcount;

            if($diff < 0)
            {
                //We need to add vm's
                $nodeCount["remove"][$node->name] = abs($diff);
            } elseif ($diff > 1)
            {
                $nodeCount["add"][$node->name] = abs($diff);
            }
        }

        //We known which machines need more machines and which need less machines;
        //Lets recommend which machines to add from the VMs from
        foreach($nodeCount["add"] as $name => $count)
        {
            //First lets see if any nodes need this EXACT amount of VM's removed
            $key = array_search($count, $nodeCount["remove"]);
            if($key !== false)
            {
                //We have an exact match
                $recommend[] = "Remove ".$count." from ".$key. " to ".$name;
            } else {
                //No one has an exact match

                foreach($nodeCount["remove"] as $removeName => $removeCount)
                {

                    if($removeCount < $count && $removeCount != 0)
                    {

                        $recommend[] = "Remove ".$removeCount." from ".$removeName." to ".$name;
                        //Update the node counts
                        $nodeCount["remove"][$removeName] = $nodeCount["remove"][$removeName] - $removeCount;
                    } else {

                        $recommend[] = "Remove ".$count. " from ".$removeName." to ".$name;
                        $nodeCount["remove"][$removeName] = $nodeCount["remove"][$removeName] - $count;
                    }
                }
            }
        }
        return $recommend;
    }

    public static function migrate($howmany, $from, $to)
    {

        echo "Migrate ".$howmany. " vms from ".$from." to ".$to."<br/>";

        //Lets determine which vms we pick to migrate

        $vms = new Collection(\Proxmox::get('/nodes/'.$from."/qemu")['data']);

        $vms = $vms->sortByDesc('cpu')->filter(function($i) {
            return $i['status'] == 'running';
        })->slice(0,2);

        foreach($vms as $vm)
        {

            $data = ['target' => $to, "online" => 1];
            $url = '/nodes/'.$from.'/qemu/'.$vm['vmid']."/migrate";

            $result = \Proxmox::create($url, $data);

            var_dump($result);

        }


    }

    public static function getTasks()
    {

        return array_reverse(array_sort(\Proxmox::get('cluster/tasks')['data'], function($value){
            return $value['starttime'];
        })
        );

    }

}