<?php

namespace App\Console\Commands;

use App\Map;
use App\Node;
use Illuminate\Console\Command;

class DoRecommendations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'recommendations:do';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //
        $data = Node::getDashboardData();

        $this->info('Calculating recommendations');

        if(isset($data['recommendations'][0]))
        {
            $this->info($data['recommendations'][0]);
            $cmd = "echo '".$data['recommendations'][0]. '\' | /usr/local/bin/cias-hipchat -R @bjcpgd';
            system($cmd);
            Node::doRecommendations($data['recommendations']);
        } else {
            $this->warn('No performance recommendations');
            $this->info('Calculating failure domain moves');
            if(isset($data['maprecommendations'][0]))
            {
                $cmd = "echo '".$data['maprecommendations'][0]. '\' | /usr/local/bin/cias-hipchat -R @bjcpgd';
                system($cmd);
                $this->info($data['maprecommendations'][0]);
                Map::doRecommendations($data['maprecommendations']);
            }
        }

    }
}
