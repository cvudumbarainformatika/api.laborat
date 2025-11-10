<?php

namespace App\Console\Commands;

use App\Http\Controllers\Api\Simrs\Laporan\IT\LaporanAntianRsDanBpjsController;
use Illuminate\Console\Command;

class kirimUlangTaskId extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'task:resend';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kirim Ulang Task Id BPJS';

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
     */
    public function handle()
    {


        info('Kirim Ulang Task Id BPJs');
        $data = LaporanAntianRsDanBpjsController::cariPasienPerluKirimUlangTaskId();
        info($data);

        return Command::SUCCESS;
        // return 0;
    }
}
