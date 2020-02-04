<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Map extends Model
{
    //
    private $map = [];

    private $domainLookup = [];

    private $VMgroups = [];

    public static function doRecommendations($recommendations = [])
    {
        foreach ($recommendations as $recommend) {
            $matches = explode(' ', $recommend);

            $vmid = $matches[1];
            $from = $matches[4];
            $to = $matches[6];

            Node::migrateVM($vmid, $from, $to);
        }
    }

    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    public function getFailureDomains()
    {
        $filename = base_path().'/.failuredomain';

        if (is_file($filename)) {
            $this->domainLookup = json_decode(file_get_contents($filename), true);
        }
    }

    public function setFailureDomains()
    {
        $filename = base_path().'/.failuredomain';

        file_put_contents($filename, json_encode($this->domainLookup, JSON_PRETTY_PRINT));
    }

    public function current()
    {
        $this->getFailureDomains();
        $current = Node::getAll();

        $domainIterator = 0;

        foreach ($current as $key => $node) {
            if (isset($this->domainLookup[$node->name])) {
                $domain = $this->domainLookup[$node->name];
            } else {
                $domain = $domainIterator;
                $this->domainLookup[$node->name] = $domain;
            }
            if ($node->load > '0' && $node->memory > '0') {
                $this->map[] = ['name' => $node->name, 'vms' => $this->cleanNodeData(Node::getVirtualMachines($node->name)), 'domain' => $domain, 'offline' => false];
            } else {
                $this->map[] = ['name' => $node->name, 'vms' => [], 'domain' => $domain, 'offline' => true];
            }
            $domainIterator++;
        }

//      $this->setFailureDomains();

        $this->computeVMGroups();

        return $this->map;
    }

    private function cleanNodeData($nodeArray)
    {
        $keep = ['vmid', 'cpu', 'name', 'status'];

        foreach ($nodeArray as $vmsKey => $vms) {
            if ($vms['status'] == 'running') {
                foreach ($vms as $key => $value) {
                    if (! in_array($key, $keep)) {
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
        foreach ($this->map as $nodes) {
            foreach ($nodes['vms'] as $vm) {
                $vmlist[] = $vm['name'];
            }
            sort($vmlist);
        }

        $groups = [];

        foreach ($vmlist as $list) {
            foreach ($vmlist as $list2) {
                $listParts = explode('-', $list);
                $list2Parts = explode('-', $list2);

                if (count($listParts) > 2) {
                    if (\is_array($listParts) && \is_array($list2Parts)) {
                        array_pop($listParts);
                        array_pop($list2Parts);

                        if ($listParts === $list2Parts) {
                            $groups[$list][] = $list2;
                        }
                    }
                }
            }
        }

        $finalGroup = [];

        asort($groups);

        foreach ($groups as $key => $group) {
            foreach ($groups as $group2) {
                if ($group == $group2) {
                    $finalGroup[md5(implode(',', $group))] = $group;
                }
            }
        }

        $this->VMgroups = $finalGroup;
    }

    public function recommended()
    {
        if (empty($this->map)) {
            $this->current();
        }

        $this->computeVMGroups();
        //First round is to make sure that all machines in the same groups are in different failure domains

        foreach ($this->map as $nodes) {
            $domains[$nodes['domain']][] = $nodes;
        }

        foreach ($domains as $domain => $nodes) {
            $domains[$domain]['groups'] = [];
            foreach ($nodes as $node) {
                if ($node['offline'] === false) {
                    foreach ($node['vms'] as $vm) {
                        foreach ($this->VMgroups as $groupName => $groupValue) {
                            if (array_search($vm['name'], $groupValue) !== false) {
                                $domains[$domain]['groups'][$groupName][] = $vm['name'];
                                $domains[$domain]['nodes'][$node['name']] = $node['name'];
                            }
                        }
                    }
                }
            }
        }

        $recommends = [];

        foreach ($domains as $domainName => $domain) {
            foreach ($domain['groups'] as $groupName => $group) {
                $offset = ceil(count($this->VMgroups[$groupName]) / count($this->map));
                $max = (count($this->VMgroups[$groupName]) % 2) + $offset;

                if (count($group) > $max) {
                    //Move one at a time to a new node
                    $newNode = $this->differentFailureDomainNode($domainName, $groupName, $domains, $max);

                    $vmMove = array_pop($domain['groups'][$groupName]);

                    //Find the VM ID
                    if (! empty($newNode)) {
                        $recommends[$this->getVMid($vmMove)] = 'move '.$this->getVMid($vmMove)." ($vmMove)".' from '.$this->getVMLocation($vmMove).' to '.$newNode;
                    }
                }
            }
        }

        asort($recommends);

        return [array_pop($recommends)];
    }

    public static function isVMmapped($vmname)
    {
        $map = new self();
        $map->current();

        $mygroup = '';
        foreach ($map->VMgroups as $key => $vms) {
            $group = array_search($vmname, $vms);
            if ($group) {
                $mygroup = $key;
            }
        }

        if (empty($mygroup)) {
            return false;
        }

        if (count($map->VMgroups[$mygroup]) > 1) {
            return true;
        }

        return false;
    }

    private function getVMLocation($name)
    {
        foreach ($this->map as $nodes) {
            foreach ($nodes['vms'] as $vm) {
                if ($vm['name'] == $name) {
                    return $nodes['name'];
                }
            }
        }

        return false;
    }

    private function getVMid($name)
    {
        foreach ($this->map as $nodes) {
            foreach ($nodes['vms'] as $vm) {
                if ($vm['name'] == $name) {
                    return $vm['vmid'];
                }
            }
        }

        return false;
    }

    private function differentFailureDomainNode($existingDomain, $group, $domainArray, $max = 1)
    {
        $possibleNodes = [];
        foreach ($this->map as $nodes) {
            if ($nodes['domain'] != $existingDomain && $nodes['offline'] === false) {
                if (! isset($nodes['groups'][$group]) || $nodes['groups'][$group] < $max) {
                    $possibleNodes[] = $nodes;
                }
            }
        }

        if (count($possibleNodes) > 1) {

            //Ok there are two options, pick the one with least number of VM's currently
            foreach ($possibleNodes as $node) {
                //Only allow a move if a VM of this group isn't already in the node
                $names = array_pluck($node['vms'], 'name');
                if (count(array_intersect($this->VMgroups[$group], $names)) == 0) {
                    $nodeCount[$node['name']] = count($node['vms']);
                }
            }
        }
        if (empty($nodeCount)) {
            return false;
        }

        asort($nodeCount);
        $keys = array_keys($nodeCount);

        return $keys[0];
    }
}
