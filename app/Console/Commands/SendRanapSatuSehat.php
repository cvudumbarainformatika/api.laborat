<?php

namespace App\Console\Commands;

use App\Helpers\Satsets\PostKunjunganRanapHelper;
use Illuminate\Console\Command;
use PhpParser\Node\Stmt\TryCatch;

class SendRanapSatuSehat extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'satset:send-ranap';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kirim data Kunjungan Ranap ke Satu Sehat per 1 data per menit';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        try {
            $data = PostKunjunganRanapHelper::ranap();
            $this->info("Rawat Inap Success Terkirim Ke Satu Sehat");
        } catch (\Throwable $th) {
            //throw $th;
            $this->info("Rawat Inap ERROR Terkirim Ke Satu Sehat :" . $th->getMessage());
        }
    }
}
