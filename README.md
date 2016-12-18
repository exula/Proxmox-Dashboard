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

## Warning
This is a project that we have been using internally and have been tweaking to our needs. I'm hoping it will be useful to other people with some tweaks. 
Please feel free to submit pull requests so we can make this better for everyone!


## Installation

Requirements

* PHP 5.6+
* php-curl
* libcurl
* composer

1. Clone this repository to a working directory

2. Use composer to install the required libraries
```
composer install
```

3. Copy the environment file
```
cp .env.example .env
```

4. Use Laravel to generate a new application key
```
php artisan key:generate
```

5. This project does not require any database configuration, you will need to update the following fields in the .env file
* ```PROXMOX_HOST=pve.example.com```         
  * This is the FQDN to one of your Proxmox nodes. You can also pass a comma delemited list of FQDN
* ```PROXMOX_USER=root```
  * A user that has full privledges to your Proxmox nodes
* ```PROXMOX_PASS=123456```
  * <--- Password for that use
* ```PROXMOX_REALM=pve```                       
  * <--- What proxmox realm that user is in

6. Start a local web server

```
php artisan serve
```

You should now be able to point your browser to http://localhost:8000/ and a see a dashboard of your Proxmox Nodes
