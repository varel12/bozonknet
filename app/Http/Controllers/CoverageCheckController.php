<?php

namespace App\Http\Controllers;

use App\Models\CoverageArea;
use App\Models\Village;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CoverageCheckController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'village_id' => ['nullable', 'integer', 'exists:villages,id'],
            'latitude' => ['required_without:village_id', 'nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['required_without:village_id', 'nullable', 'numeric', 'between:-180,180'],
        ]);

        $village = isset($validated['village_id'])
            ? Village::query()->findOrFail($validated['village_id'])
            : null;

        $latitude = (float) ($village?->latitude ?? $validated['latitude']);
        $longitude = (float) ($village?->longitude ?? $validated['longitude']);
        $area = CoverageArea::query()->where('is_active', true)->firstOrFail();
        $distance = $this->distanceInMeters(
            $latitude,
            $longitude,
            (float) $area->center_latitude,
            (float) $area->center_longitude,
        );

        $status = $village?->status ?? match (true) {
            $distance <= $area->available_radius_meters => Village::STATUS_AVAILABLE,
            $distance <= $area->expansion_radius_meters => Village::STATUS_EXPANSION,
            default => Village::STATUS_UNAVAILABLE,
        };

        $messages = [
            Village::STATUS_AVAILABLE => [
                'label' => 'Tersedia',
                'title' => 'Jaringan aktif di lokasi ini',
                'description' => 'Lokasi berada dalam cakupan aktif BozonkNet dan dapat melanjutkan proses berlangganan.',
            ],
            Village::STATUS_EXPANSION => [
                'label' => 'Segera Hadir',
                'title' => 'Sedang dalam proses perluasan',
                'description' => 'Jaringan sedang dikembangkan menuju lokasi ini. Kirim minat agar tim kami dapat memprioritaskan area Anda.',
            ],
            Village::STATUS_UNAVAILABLE => [
                'label' => 'Belum Tersedia',
                'title' => 'Belum menjangkau lokasi ini',
                'description' => 'Lokasi belum masuk jalur jaringan aktif. Anda tetap dapat mengajukan area untuk peninjauan tim.',
            ],
        ];

        return response()->json([
            'status' => $status,
            ...$messages[$status],
            'latitude' => $latitude,
            'longitude' => $longitude,
            'distance_meters' => (int) round($distance),
            'location' => $village
                ? "Desa {$village->name}, Kecamatan {$village->district}"
                : sprintf('Titik %.5f, %.5f', $latitude, $longitude),
        ]);
    }

    private function distanceInMeters(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6_371_000;
        $latitudeDelta = deg2rad($lat2 - $lat1);
        $longitudeDelta = deg2rad($lon2 - $lon1);
        $a = sin($latitudeDelta / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($longitudeDelta / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
