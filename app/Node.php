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
            $node->load = round($data['load']*100,2);
            $node->memory = $data['memory'];
            $node->vmcount = $data['vmcount'];

            $node->balancevalue = (
                ($node->memory*100) +
                ($node->vmcount)
            );

            $collection->add($node);
        }

        $totalBalance = 0;

        foreach($collection as $c)
        {
            $totalBalance += $c->balancevalue;
        }

        $midPoint = $totalBalance / \count($collection);



        $points = [
            'midPoint' => $midPoint,
            'lowpoint' =>  $midPoint - ($totalBalance * .015),
            'hightpoint' => $midPoint + ($totalBalance * .020)
            ];

        $collection->each(function($n) use ($points) {

            if($n->balancevalue <= $points['lowpoint'])
            {
                $n->balancestatus = 'low';
            } elseif ( $n->balancevalue >= $points['hightpoint'])
            {
                $n->balancestatus = 'high';
            } else {
                $n->balancestatus = 'mid';
            }

        });


        $collection = $collection->sortBy(function($obj){
            $parts = explode('-', $obj->name);

            return $parts[count($parts)-1];

        });

        return $collection;

    }

    public static function getVirtualMachines($nodeName)
    {

        $nodeData = \Proxmox::get('/nodes/'.$nodeName.'/qemu/');
        return $nodeData['data'];

    }

    private static function getVMConfig($nodeName, $vmid)
    {
        $config = \Proxmox::get('/nodes/'.$nodeName.'/qemu/'.$vmid.'/config');
        return $config['data'];
    }

    public static function returnAllVMS()
    {

        $collection = new Collection();

        $allnodes = \Proxmox::get('/nodes');
        foreach($allnodes['data'] as $node)
        {

            $vms = self::getVirtualMachines($node['node']);

            if(isset($vms)) {
                foreach ($vms as $vm) {
                    $vm['config'] = (object)self::getVMConfig($node['node'], $vm['vmid']);
                    $collection->push((object)$vm);
                }
            }

        }

        return $collection;
    }

    public static function qemuLink($vmid)
    {
        //https://cias-pve-blade-1.rit.edu:8006/#v1:0:=qemu%2F115:4::::::

        $nodes = self::getAll();

        $link = 'https://'.$nodes->pop()->name.'.rit.edu:8006/#v1:0:=qemu%2F'.$vmid;

        return $link;

    }

    private static function getAllVMS()
    {

        $allnodes = \Proxmox::get('/nodes');

        self::$data = [];
        foreach($allnodes['data'] as $node) {
            if (isset($node['cpu'])) {
                $nodeData = \Proxmox::get('/nodes/' . $node['node'] . '/qemu/');
                if (isset($node['cpu'])) {
                    self::$data[$node['node']]['load'] = round($node['cpu'], 2);
                    self::$data[$node['node']]['memory'] = $node['mem'] / $node['maxmem'];
                    self::$data[$node['node']]['vmcount'] = 0;

                    foreach ($nodeData['data'] as $vms) {
                        if ($vms['status'] == 'running') {
                            self::$data[$node['node']]['vmcount']++;
                        }
                    }
                }

            } else {
                self::$data[$node['node']]['load'] = 0;
                self::$data[$node['node']]['memory'] = 0;
                self::$data[$node['node']]['vmcount'] = 0;
            }
        }
    }

    public static function getStorage()
    {
        $storage = \Proxmox::get('/storage');

        $ret = [];
        foreach($storage['data'] as $d)
        {
            if(stristr($d['content'], 'images'))
            {
                $showLocal = env('PROXMOX_SHOW_LOCAL_STORAGE') ?: true;

                if($showLocal === true)
                {
                    $ret[$d['storage']] = $d['storage'];
                } else {
                    if($d['storage'] != 'local')
                    {
                        $ret[$d['storage']] = $d['storage'];
                    }
                }

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

            if(isset($nodeData['data'])) {
                foreach ($nodeData['data'] as $vms) {
                    if ($vms['template']) {
                        $templates[$node . "::" . $vms['vmid']] = $vms['name'];
                    }
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

        //Lets just return the first recommendation
        return [array_pop($recommend)];

    }

    private static function recommendVMCount()
    {
        //Lets do vmcount first
        $nodes = self::getAll();
        $totalVMs = 0;
        $recommend = [];

        $nodes = $nodes->sortBy('vmcount');

        foreach($nodes as $key => $node) {
            if ($node->memory == 0 && $node->load == 0) {
                unset($nodes[$key]);
            }
        }

        foreach($nodes as $key => $node)
        {
            $totalVMs += $node->vmcount;
        }
        $vmcountAverage = ceil($totalVMs / count($nodes));

        $nodeCount = ["add" => [], "remove" => []];

        foreach($nodes as $node)
        {
            //Lets determine what action needs to be taken to bring all the nodes to the average vm count

            $diff = $vmcountAverage - $node->vmcount;
            if($node->balancestatus == 'high')
            {
                //We need to add vm's
                $nodeCount["remove"][$node->name] = abs($diff);
            } elseif ($node->balancestatus == 'low')
            {
                $nodeCount["add"][$node->name] = abs($diff);
            }
        }

        $allreadyRemoved = [];

        //We known which machines need more machines and which need less machines;
        //Lets recommend which machines to add from the VMs from
        foreach($nodeCount["add"] as $name => $count)
        {
            //First lets see if any nodes need this EXACT amount of VM's removed
            $key = array_search($count, $nodeCount["remove"]);
            if($key !== false)
            {
                //We have an exact match
                if(!isset($allreadyRemoved[$key])) {
                    $recommend[] = "Remove " . $count . " from " . $key . " to " . $name;
                    $allreadyRemoved[$key] = true;
                }
            } else {
                //No one has an exact match

                foreach($nodeCount["remove"] as $removeName => $removeCount)
                {

                    if($removeCount >= 3) {
                        $removeCount = 2;
                    }


                    if($count == 0 || $removeCount == 0)
                    {
                        $count = 1;

                    }

                    if($removeCount < $count && $removeCount != 0)
                    {
                        if(!isset($allreadyRemoved[$removeName])) {
                            $recommend[] = 'Remove ' . $removeCount . ' from ' . $removeName . ' to ' . $name;
                            //Update the node counts
                            $nodeCount["remove"][$removeName] = $nodeCount["remove"][$removeName] - $removeCount;
                            $allreadyRemoved[$removeName] = true;
                        }
                    } else {
                        if(!isset($allreadyRemoved[$removeName])) {
                            $recommend[] = 'Remove ' . $count . ' from ' . $removeName . ' to ' . $name;
                            $nodeCount["remove"][$removeName] = $nodeCount["remove"][$removeName] - $count;
                            $allreadyRemoved[$removeName] = true;
                        }
                    }
                }
            }
        }

        $allreadyAdded = [];


        if(empty($recommend))
        {
            foreach($nodeCount['remove'] as $name => $count)
            {
                foreach($nodes as $node)
                {
                    if($node->name !== $name && !isset($allreadyAdded[$node->name])) {
                        if ($count > 0) {
                            $recommend[] = 'Remove 1 from ' . $name . ' to ' . $node->name;
                            $allreadyRemoved[$name] = true;
                            $allreadyAdded[$name] = true;
                            $allreadyAdded[$node->name] = true;
                        }
                        $count--;
                    }
                }
            }
        }


        return $recommend;
    }

    public static function migrate($howmany, $from, $to)
    {

        echo 'Migrate ' .$howmany. ' vms from ' .$from. ' to ' .$to. '<br/>';

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

    public static function migrateVM($vmid, $from, $to)
    {

        $data = ['target' => $to, "online" => 1];
        $url = '/nodes/'.$from.'/qemu/'.$vmid."/migrate";

        $result = \Proxmox::create($url, $data);

        var_dump($result);
    }

    public static function getTasks()
    {

        return array_reverse(array_sort(\Proxmox::get('cluster/tasks')['data'], function($value){
            return $value['starttime'];
        })
        );

    }

}
