<?php

namespace App\Http\Controllers;

use App\Models\CustomerSubscription;
use App\Models\Odp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerAssignmentController extends Controller
{
    public function assign(Request $request, CustomerSubscription $customerSubscription): RedirectResponse
    {
        abort_unless(in_array(auth()->user()?->role, ['teknisi', 'admin', 'super_admin'], true), 403);

        $data = $request->validate([
            'odp_id' => ['required', 'integer', 'exists:odps,id'],
        ]);

        DB::transaction(function () use ($customerSubscription, $data) {
            $odp = Odp::query()->lockForUpdate()->findOrFail($data['odp_id']);
            $availablePorts = $odp->available_ports ?? max(0, $odp->total_ports - $odp->used_ports);

            abort_if($availablePorts <= 0, 422, 'Port ODP sudah penuh.');

            $customerSubscription->update([
                'odp_id' => $odp->id,
                'status' => 'Installed',
            ]);

            $odp->used_ports = min($odp->total_ports, $odp->used_ports + 1);
            $odp->available_ports = max(0, $availablePorts - 1);
            $odp->status = $odp->available_ports === 0 ? 'Full' : 'Available';
            $odp->save();
        });

        return back()->with('status', 'Pelanggan berhasil dihubungkan ke ODP dan port otomatis diperbarui.');
    }
}
