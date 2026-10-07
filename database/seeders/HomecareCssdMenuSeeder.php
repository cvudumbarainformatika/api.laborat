<?php

namespace Database\Seeders;

use App\Models\Pegawai\Akses\AksesUser;
use App\Models\Pegawai\Akses\Menu;
use Illuminate\Database\Seeder;
use RuntimeException;

class HomecareCssdMenuSeeder extends Seeder
{
    public function run(): void
    {
        $patientMenu = Menu::query()
            ->whereIn('link', ['/homecare/list-pasien', 'homecare/list-pasien'])
            ->first();

        if (!$patientMenu) {
            throw new RuntimeException('Menu List Pasien HomeCare tidak ditemukan di database SSO');
        }

        $cssdMenu = Menu::updateOrCreate(
            [
                'aplikasi_id' => $patientMenu->aplikasi_id,
                'link' => '/homecare/permintaan-cssd',
            ],
            [
                'nama' => 'Permintaan CSSD',
                'name' => 'homecare.permintaan-cssd',
                'icon' => 'assignment',
            ]
        );

        $userIds = AksesUser::query()
            ->where('aplikasi_id', $patientMenu->aplikasi_id)
            ->where('menu_id', $patientMenu->id)
            ->whereNull('submenu_id')
            ->pluck('user_id');

        foreach ($userIds as $userId) {
            AksesUser::firstOrCreate([
                'user_id' => $userId,
                'aplikasi_id' => $patientMenu->aplikasi_id,
                'menu_id' => $cssdMenu->id,
                'submenu_id' => null,
            ]);
        }
    }
}
