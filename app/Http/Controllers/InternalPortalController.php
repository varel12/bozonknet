<?php

namespace App\Http\Controllers;

use App\Models\AreaRequest;
use App\Models\CustomerSubscription;
use App\Models\InternetPackage;
use App\Models\NetworkMarker;
use App\Models\Odc;
use App\Models\Odp;
use App\Models\Olt;
use App\Models\RegistrationLog;
use App\Models\User;
use App\Models\Village;
use Illuminate\View\View;

class InternalPortalController extends Controller
{
    public function admin(): View
    {
        abort_unless(in_array(auth()->user()?->role, ['admin', 'super_admin'], true), 403);

        $subscriptions = CustomerSubscription::with('village')->latest()->limit(8)->get();
        $areaRequests = AreaRequest::latest()->limit(8)->get();
        $odps = Odp::with('odc.olt')->orderBy('status')->orderBy('code')->get();
        $markers = NetworkMarker::latest()->get();
        $users = User::orderBy('role')->orderBy('name')->get();
        $packages = InternetPackage::orderByDesc('is_active')->orderBy('speed_mbps')->get();
        $olts = Olt::withCount('odcs')->latest()->get();
        $odcs = Odc::with('olt')->withCount('odps')->orderBy('code')->get();
        $registrationLogs = RegistrationLog::with('package')->latest()->limit(12)->get();
        $areaSummary = RegistrationLog::query()
            ->where('status', 'Pending')
            ->get()
            ->groupBy(fn (RegistrationLog $item) => $item->village_name ?: 'Lokasi belum terdeteksi')
            ->map(fn ($items, $label) => ['label' => $label, 'total' => $items->count()])
            ->sortByDesc('total')
            ->take(8)
            ->values();

        if ($areaSummary->isEmpty()) {
            $areaSummary = CustomerSubscription::with('village')
            ->get()
            ->groupBy(fn (CustomerSubscription $item) => $item->village?->name ?: 'Desa belum terdeteksi')
            ->map(fn ($items, $label) => ['label' => $label, 'total' => $items->count()])
            ->sortByDesc('total')
            ->take(8)
            ->values();
        }

        return view('internal.admin', [
            'subscriptions' => $subscriptions,
            'areaRequests' => $areaRequests,
            'odps' => $odps,
            'markers' => $markers,
            'users' => $users,
            'packages' => $packages,
            'olts' => $olts,
            'odcs' => $odcs,
            'registrationLogs' => $registrationLogs,
            'areaSummary' => $areaSummary,
            'villages' => Village::orderBy('name')->get(),
            'stats' => [
                'subscriptions' => CustomerSubscription::count(),
                'areaRequests' => AreaRequest::count(),
                'pendingRequests' => RegistrationLog::where('status', 'Pending')->count() + AreaRequest::where('status', 'pending')->count(),
                'activeOdps' => Odp::whereIn('status', ['Available', 'active'])->count() + NetworkMarker::where('type', 'ODP')->where('status', 'active')->count(),
                'packages' => InternetPackage::where('is_active', true)->count(),
                'olts' => Olt::count(),
            ],
        ]);
    }

    public function teknisi(): View
    {
        abort_unless(in_array(auth()->user()?->role, ['admin', 'super_admin', 'teknisi'], true), 403);

        $subscriptions = CustomerSubscription::with('village')->where('status', 'pending')->latest()->limit(10)->get();
        $areaRequests = AreaRequest::latest()->limit(10)->get();
        $odps = Odp::orderBy('code')->get();
        $markers = NetworkMarker::latest()->limit(20)->get();

        return view('internal.teknisi', [
            'subscriptions' => $subscriptions,
            'areaRequests' => $areaRequests,
            'odps' => $odps,
            'odcs' => Odc::orderBy('code')->get(),
            'markers' => $markers,
            'villages' => Village::orderBy('name')->get(),
        ]);
    }
}
