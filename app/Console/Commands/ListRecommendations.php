<?php

namespace App\Console\Commands;

use App\Node;
use Illuminate\Console\Command;

class ListRecommendations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'recommendations:list';

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
        $this->info('The following recommendations are made to bring the cluster into compliance.');

        $data = Node::getDashboardData();

        $headers = ['Recommendation'];

        $this->table($headers, [$data['recommendations'], $data['maprecommendations']] );

    }
}
