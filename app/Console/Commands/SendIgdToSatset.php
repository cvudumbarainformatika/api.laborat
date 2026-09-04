<?php

namespace App\Console\Commands;

use App\Helpers\Satsets\PostKunjunganIgdHelper;
use Illuminate\Console\Command;

class SendIgdToSatset extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'satset:send-igd';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kirim Kunjungan IGD Ke Satu Sehat';

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
     * @return int
     */
    public function handle()
    {
        try {
            $data = PostKunjunganIgdHelper::cekKunjungan();
            $this->info("IGD Terkirim Ke Satu Sehat: " . json_encode($data));
        } catch (\Throwable $th) {
            $this->info("IGD ERROR Terkirim Ke Satu Sehat :" . $th->getMessage());
        }

        return Command::SUCCESS;
    }
}
