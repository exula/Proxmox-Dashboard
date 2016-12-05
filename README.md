# Proxmox-Dashboard
A Laravel Application that manages our Proxmox Cluster. Features include cluster resources and a feature that balances QEMU guests across the cluster.

##Features
* Cluster Resource monitoring
* Make and execute recommendations to balancing cluster nodes
* Migrate machines based on failure domains and naming of VM's
* Provision new QEMU vms
* Monitor all tasks on the cluster


## Failure domains
By default this software assumes that each PVE cluster node is a seperate failure domain.
If you want to tweak this and put nodes in the same failure domain look at the .failuredomain.example file.
This JSON formated file will be read and determine where VM's should be migrated to.

## Grouping of VM's 
In a typical cluster of VM's they will have some sort of naming convention that logically groups them together. 
This software attempts to group VM's together based on names and spread them across nodes in different failure domains.
It isn't perfect, and it has a chance of breaking the performance recommendations for balancing the number of VM's across the cluster

