<?php

namespace App\Http\Controllers;

use App\Models\LaboratLuar;
use App\Models\Simrs\Penunjang\Radiologi\Transpermintaanradiologi;
use App\Models\TransaksiLaborat;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;

class AutoUpdateRadiologiController extends Controller
{

    public function updatebatal()
    {
        $updated = Transpermintaanradiologi::whereDate('rs3', '<=', Carbon::now()->subDays(10))
            ->where('rs9', '=', '')
            ->update([
                'rs9' => '3' // dibatalkan
            ]);

        $this->info("✅  Status updated for {$updated} record(s).");
        echo "✅  Status updated for {$updated} record(s).";
    }

    
}
