<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Map extends Model
{
    //
    private $map = array();

    private $domainLookup = [];

    private $VMgroups = [];

    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    public function getFailureDomains()
    {
        $filename = base_path()."/.failuredomain";

        if(is_file($filename))
        {
            $this->domainLookup = json_decode(file_get_contents($filename), true);
        }
    }

    public function setFailureDomains()
    {
        $filename = base_path()."/.failuredomain";

        file_put_contents($filename, json_encode($this->domainLookup,JSON_PRETTY_PRINT));
    }

    public function current()
    {
        $this->getFailureDomains();
        $current = Node::getAll();

        $domainIterator = 0;

        foreach($current as $key => $node)
        {
            if(isset($this->domainLookup[$node->name]))
            {
                $domain = $this->domainLookup[$node->name];
            } else {
                $domain = $domainIterator;
                $this->domainLookup[$node->name] = $domain;
            }
            $this->map[] = ["name" => $node->name, 'vms' => $this->cleanNodeData(Node::getVirtualMachines($node->name)), 'domain' => $domain];
            $domainIterator++;
        }

        $this->setFailureDomains();

        $this->computeVMGroups();

        return $this->map;
    }

    private function cleanNodeData($nodeArray)
    {

        $keep = ['vmid', 'cpu', 'name', 'status'];

        foreach($nodeArray as $vmsKey => $vms) {
            if($vms['status'] == 'running') {
                foreach ($vms as $key => $value) {
                    if (!in_array($key, $keep)) {
                        unset($nodeArray[$vmsKey][$key]);
                    }
                }
            } else {
                unset($nodeArray[$vmsKey]);
            }
        }
        return $nodeArray;
    }

    private function computeVMGroups()
    {

        $vmlist = [];
        foreach($this->map as $nodes)
        {
            foreach($nodes['vms'] as $vm)
            {
                $vmlist[] = $vm['name'];
            }
        }

        $groups = [];

        foreach($vmlist as $list)
        {
            foreach($vmlist as $list2)
            {
                if(levenshtein($list, $list2) < 2) {
                    $groups[$list][] = $list2;
                }
            }
        }

        $finalGroup = [];
        foreach($groups as $key => $group)
        {
            foreach($groups as $group2)
            {
                if($group == $group2)
                {
                    $finalGroup[md5(implode(',',$group))] = $group;
                }
            }
        }

        $this->VMgroups = $finalGroup;

    }

    public function recommended()
    {

        if(empty($this->map))
        {
            $this->current();
        }

        $this->computeVMGroups();
        //First round is to make sure that all machines in the same groups are in different failure domains
        foreach($this->map as $nodeKey => $nodes)
        {
            foreach($nodes['vms'] as $vm)
            {
                foreach($this->VMgroups as $groupName => $groupValue) {
                    if(array_search($vm['name'], $groupValue) !== false)
                    {
                        $this->map[$nodeKey]['groups'][$groupName][] = $vm['name'];
                    }
                }
            }
        }

        $recommends = [];
        foreach($this->map as $nodes)
        {
            foreach($nodes['groups'] as $groupName => $group)
            {
                if(count($group) > 1)
                {

                    //Move one at a time to a new node
                    $newNode = $this->differentFailureDomainNode($nodes, $groupName);
                    if($newNode != $nodes['name']) {
                        $vmMove = array_pop($nodes['groups'][$groupName]);

                        //Find the VM ID

                        $recommends[] = 'move '.$this->getVMid($vmMove)." ($vmMove)".' from ' . $nodes['name'] . ' to ' . $newNode;
                    }
                }
            }
        }
        return $recommends;

    }

    private function getVMid($name)
    {
        foreach($this->map as $nodes)
        {
            foreach($nodes['vms'] as $vm)
            {
                if($vm['name'] == $name)
                {
                    return $vm['vmid'];
                }

            }
        }
        return false;
    }

    private function differentFailureDomainNode($node, $group)
    {
        $existingDomain = $node['domain'];

        foreach($this->map as $nodes){
            if($nodes['domain'] != $existingDomain)
            {
                if(!isset($nodes['groups'][$group]) || $nodes['groups'][$group] < 1) {
                    return $nodes['name'];
                }
            }
        }

        return $node['name'];
    }

}
