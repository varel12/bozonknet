<?php

namespace App\Http\Controllers;

use App\Models\NetworkMarker;
use App\Models\Odc;
use App\Models\Odp;
use App\Models\CustomerSubscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NetworkMarkerController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        abort_unless(in_array(auth()->user()?->role, ['admin', 'teknisi'], true), 403);

        $data = $request->validate([
            'type' => ['required', Rule::in(['ODC', 'ODP', 'Pelanggan'])],
            'odc_id' => ['nullable', 'integer', 'exists:odcs,id'],
            'odp_id' => ['nullable', 'integer', 'exists:odps,id'],
            'customer_subscription_id' => ['nullable', 'integer', 'exists:customer_subscriptions,id'],
            'code' => ['nullable', 'string', 'max:80'],
            'name' => ['required', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $data['status'] = 'active';

        NetworkMarker::create($data);

        if ($data['type'] === 'ODC' && ! empty($data['odc_id'])) {
            Odc::whereKey($data['odc_id'])->update([
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'status' => 'Mapped',
            ]);
        }

        if ($data['type'] === 'ODP' && ! empty($data['odp_id'])) {
            Odp::whereKey($data['odp_id'])->update([
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'status' => 'Available',
            ]);
        }

        if ($data['type'] === 'Pelanggan' && ! empty($data['customer_subscription_id'])) {
            CustomerSubscription::whereKey($data['customer_subscription_id'])->update([
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
            ]);
        }

        return back()->with('status', 'Marking lokasi berhasil disimpan.');
    }

    public function destroyOdpMarker(NetworkMarker $networkMarker): RedirectResponse
    {
        abort_unless(auth()->user()?->role === 'admin', 403);
        abort_unless(strtoupper($networkMarker->type) === 'ODP', 404);

        $networkMarker->delete();

        return redirect()->route('internal.admin', ['page' => 'jaringan'])->with('status', 'Data ODP dari teknisi berhasil dihapus.');
    }

    public function destroyOdp(Odp $odp): RedirectResponse
    {
        abort_unless(auth()->user()?->role === 'admin', 403);

        $odp->delete();

        return redirect()->route('internal.admin', ['page' => 'jaringan'])->with('status', 'Data ODP berhasil dihapus.');
    }
}
