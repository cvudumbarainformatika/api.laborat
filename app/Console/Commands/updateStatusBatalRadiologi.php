<?php

namespace App\Console\Commands;

use App\Models\Simrs\Penunjang\Radiologi\Transpermintaanradiologi;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class updateStatusBatalRadiologi extends Command
{
    protected $signature = 'permintaanRadiologi:update-batal';

    protected $description = 'Update rs9 if rs3 >= 10 days and rs9 is empty';

    public function handle()
    {
       $updated = Transpermintaanradiologi::whereDate('rs3', '<=', Carbon::now()->subDays(10))
            ->where('rs9', '=', '')
            ->update([
                'rs9' => '3'
            ]);

        $this->info("✅  Status updated for {$updated} record(s).");
    }

    
}
