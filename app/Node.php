<?php

namespace App;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

use ProxmoxVE\Proxmox;

class Node extends Model
{
    //
    private static $data = [];
    private static $alreadyMigrated = [];

    public static function getAll()
    {
        //Lets fake some data right now

        self::getAllVMS();

        $collection = new Collection();

        foreach(self::$data as $nodeName => $data)
        {
            $node = new Node();
            $node->name = $nodeName;
            $node->load = $data['load']*100;
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


        $collection = $collection->sortBy(function($obj){
            $parts = preg_split("/-/", $obj->name);

            return $parts[count($parts)-1];

        });

        return $collection;

    }

    private static function getAllVMS()
    {

        $allnodes = \Proxmox::get('/nodes');

        self::$data = [];
        foreach($allnodes['data'] as $node) {
            if (isset($node['cpu'])) {
                $nodeData = \Proxmox::get('/nodes/' . $node['node'] . '/qemu/');
                if (isset($node['cpu'])) {
                    self::$data[$node['node']]['load'] = round($node['cpu'], 4);
                    self::$data[$node['node']]['memory'] = $node['mem'] / $node['maxmem'];
                    self::$data[$node['node']]['vmcount'] = 0;

                    foreach ($nodeData['data'] as $vms) {
                        if ($vms['status'] == 'running') {
                            self::$data[$node['node']]['vmcount']++;
                        }
                    }
                }

            }
        }

    }

    public static function getStorage()
    {
        $storage = \Proxmox::get('/storage');

        $ret = [];

        foreach($storage['data'] as $d)
        {
            if($d['storage'] != 'local' && stristr('images', $d['content']))
            {
                $ret[$d['storage']] = $d['storage'];
            }
        }

        asort($ret);

        return $ret;

    }

    public static function getTemplates()
    {

        self::getAllVMS();
        $templates = [];
        foreach(self::$data as $node => $data)
        {
            $nodeData = \Proxmox::get('/nodes/'.$node.'/qemu/');

            foreach($nodeData['data'] as $vms)
            {
                if($vms['template'])
                {
                    $templates[$node."::".$vms['vmid']] = $vms['name'];
                }
            }
        }

        asort($templates);

        return $templates;

    }

    public static function getClusterStatus()
    {
        $status = \Proxmox::get('/cluster/resources');

        $cluster = \Proxmox::get('/cluster/status');

        $ha = \Proxmox::get('/cluster/ha/status/current');

        if($ha['data'][0]['status'] == 'OK')
        {
            $quorum = true;
        } else {
            $quorum = false;
        }

        $cpu = ['total' => 0, 'used' => 0];
        $memory = ['total' => 0, 'used' => 0];
        $disk = ['total' => 0, 'used' => 0];
        $vms = ['running' => 0, 'paused' => 0, 'stopped' => 0];

        $count = 0;

        foreach($status['data'] as $stat)
        {
            if ($stat['type'] == 'node') {

                if(isset($stat['cpu'])) {
                    $count++;
                    $memory['total'] += $stat['maxmem'];
                    $memory['used'] += $stat['mem'];

                    $cpu['total'] += $stat['maxcpu'];
                    $cpu['used'] += $stat['cpu'];

                    $disk['total'] += $stat['maxdisk'];
                    $disk['used'] += $stat['disk'];
                }
            }



            if($stat['type'] == 'qemu')
            {
                if($stat['status'] == 'running') {
                    $vms['running'] += 1;
                }

                if($stat['status'] == 'paused') {
                    $vms['paused'] += 1;
                }

                if($stat['status'] == 'stopped') {
                    $vms['stopped'] += 1;
                }

            }

        }

        $cpu['used'] = $cpu['used'] / $count;


        $online = 0;
        $offline = 0;

        foreach($cluster['data'] as $node)
        {
            if($node['type'] == 'node')
            {
                if($node['online'] == 1) {
                    $online += 1;
                } else {
                    $offline += 1;
                }
            }
        }

        //Do some work on the cpu and memory so we don't have to do any work on the client  side
        $cpu['used'] = round($cpu['used'] * 100);


        $memory['used'] = round(($memory['used'] / $memory['total']) * 100);
        $memory['total'] = round($memory['total']/1024/1024/1024);


        $disk['used'] = round(($disk['used'] / $disk['total'])*100);
        $disk['total'] = round($disk['total']/1024/1024/1024);

        $returnArray = [
            "cpu" => $cpu,
            "memory" => $memory,
            "disk" => $disk,
            'vms' => $vms,
            'quorum' => $quorum,
            'online' => $online,
            'offline' => $offline
        ];

        return $returnArray;

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
            } elseif ($diff > ($totalVMs % count($nodes)))
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
        })->slice(0,$howmany);

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