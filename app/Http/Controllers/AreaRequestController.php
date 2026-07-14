<?php

namespace App\Http\Controllers;

use App\Models\AreaRequest;
use App\Models\Village;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AreaRequestController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:100'],
            'address' => ['required', 'string', 'max:255'],
            'whatsapp' => ['required', 'string', 'max:25', 'regex:/^[0-9+()\-\s]{8,25}$/'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'coverage_status' => ['nullable', 'in:available,expansion,unavailable'],
        ], [
            'whatsapp.regex' => 'Nomor WhatsApp tidak valid.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Data pengajuan belum valid.',
                'errors' => $validator->errors()->toArray(),
            ], 422);
        }

        $validated = $validator->validated();

        $areaRequest = AreaRequest::query()->create([
            ...$validated,
            'coverage_status' => $validated['coverage_status'] ?? Village::STATUS_UNAVAILABLE,
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Pengajuan berhasil disimpan. Tim BozonkNet akan meninjau lokasi Anda.',
            'request_id' => $areaRequest->id,
        ], 201);
    }
}
