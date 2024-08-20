<?php

namespace App\Console;

use App\Http\Controllers\Api\Logistik\Sigarang\Transaksi\StokOpnameController;
use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Stok\StokOpnameFarmasiController;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('send:rajal')
                    ->everyMinute()
                    ->between('19:00', '23:30');

        $schedule->call(function () {
            info('mulai stok opname farmasi');
            $opname = new StokOpnameFarmasiController;
            $data = $opname->storeMonthly();
            info($data);
        })->dailyAt('00:20');
        // $schedule->command('inspire')->hourly();
        $schedule->call(function () {
            info('mulai stok opname');
            $opname = new StokOpnameController;
            $data = $opname->storeMonthly();
            info($data);
        })->dailyAt('00:30');
        // $schedule->call(function () {
        //     info('mulai');
        //     $opname = new StokOpnameController;
        //     $data = $opname->storeCoba();
        //     info($data);
        // })->hourlyAt(16);
        // $schedule->call(function () {

        //     info('nyoba');
        // })->everyMinute();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
