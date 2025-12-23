<?php

namespace App\Console\Commands;

use App\Http\Controllers\Api\Simrs\Master\TindakanController;
use Illuminate\Console\Command;

class CekTarif extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tarif:cek';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cek Terif yang berubah';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        info('Cek Data tarif Tindakan yang berubah');
        $data = TindakanController::pindahKeTabelMaster();
        info($data);

        return Command::SUCCESS;
    }
}
