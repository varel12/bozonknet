<?php

namespace App\Http\Controllers;

use App\Models\Odc;
use App\Models\Odp;
use App\Models\Olt;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NetworkProvisioningController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->role === 'super_admin', 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'ip_address' => ['nullable', 'ip'],
            'location_description' => ['nullable', 'string', 'max:255'],
            'odc_count' => ['required', 'integer', 'min:1', 'max:50'],
            'odp_per_odc' => ['required', 'integer', 'min:1', 'max:100'],
            'ports_per_odp' => ['required', 'integer', 'min:1', 'max:128'],
        ]);

        DB::transaction(function () use ($data) {
            $olt = Olt::create([
                'name' => $data['name'],
                'ip_address' => $data['ip_address'] ?? null,
                'location_description' => $data['location_description'] ?? null,
            ]);

            for ($odcIndex = 1; $odcIndex <= $data['odc_count']; $odcIndex++) {
                $odc = Odc::create([
                    'olt_id' => $olt->id,
                    'code' => sprintf('ODC-%03d-%02d', $olt->id, $odcIndex),
                    'name' => sprintf('%s ODC %02d', $olt->name, $odcIndex),
                    'status' => 'Unmapped',
                ]);

                for ($odpIndex = 1; $odpIndex <= $data['odp_per_odc']; $odpIndex++) {
                    Odp::create([
                        'odc_id' => $odc->id,
                        'code' => sprintf('ODP-%03d-%02d-%02d', $olt->id, $odcIndex, $odpIndex),
                        'name' => sprintf('%s ODP %02d', $odc->name, $odpIndex),
                        'village_name' => null,
                        'latitude' => null,
                        'longitude' => null,
                        'total_ports' => $data['ports_per_odp'],
                        'used_ports' => 0,
                        'available_ports' => $data['ports_per_odp'],
                        'status' => 'Available',
                    ]);
                }
            }
        });

        return redirect()->route('internal.admin', ['page' => 'topology'])->with('status', 'OLT, ODC, dan ODP placeholder berhasil dibuat.');
    }
}
