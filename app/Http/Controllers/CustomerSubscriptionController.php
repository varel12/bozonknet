<?php

namespace App\Http\Controllers;

use App\Models\CustomerSubscription;
use App\Models\InternetPackage;
use App\Models\RegistrationLog;
use App\Models\Village;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CustomerSubscriptionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $planCodes = InternetPackage::query()->where('is_active', true)->pluck('code')->all();

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:100'],
            'whatsapp' => ['required', 'string', 'max:25', 'regex:/^[0-9+()\-\s]{8,25}$/'],
            'email' => ['required', 'email:rfc', 'max:150'],
            'billing_day' => ['required', 'integer', 'between:1,10'],
            'village_id' => ['nullable', 'integer', 'exists:villages,id'],
            'address' => ['required', 'string', 'max:1000'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'plan_code' => ['required', Rule::in($planCodes)],
        ], [
            'whatsapp.regex' => 'Nomor WhatsApp tidak valid.',
            'email.email' => 'Format email tidak valid.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Data pendaftaran belum lengkap atau tidak valid.',
                'errors' => $validator->errors()->toArray(),
            ], 422);
        }

        $validated = $validator->validated();
        $plan = InternetPackage::query()->where('code', $validated['plan_code'])->where('is_active', true)->firstOrFail();
        $village = isset($validated['village_id'])
            ? Village::query()->findOrFail($validated['village_id'])
            : Village::query()->where('status', Village::STATUS_AVAILABLE)->orderBy('name')->firstOrFail();

        if ($village->status !== Village::STATUS_AVAILABLE) {
            RegistrationLog::create([
                'customer_name' => $validated['name'],
                'phone_number' => $validated['whatsapp'],
                'village_name' => $village->name,
                'latitude' => $validated['latitude'] ?? $village->latitude,
                'longitude' => $validated['longitude'] ?? $village->longitude,
                'package_id' => $plan->id,
                'status' => 'Rejected',
                'payload' => $validated,
            ]);

            return response()->json([
                'message' => 'Jaringan belum tersedia di desa tersebut. Silakan ajukan perluasan area terlebih dahulu.',
                'errors' => [
                    'village_id' => ['Desa yang dipilih belum berada dalam cakupan aktif.'],
                ],
            ], 422);
        }

        $subscription = CustomerSubscription::query()->create([
            'name' => $validated['name'],
            'whatsapp' => $validated['whatsapp'],
            'email' => $validated['email'],
            'billing_day' => $validated['billing_day'],
            'customer_type' => 'residential',
            'village_id' => $village->id,
            'package_id' => $plan->id,
            'street_address' => $validated['address'],
            'full_address' => $validated['address'],
            'village_name' => $village->name,
            'latitude' => $validated['latitude'] ?? $village->latitude,
            'longitude' => $validated['longitude'] ?? $village->longitude,
            'coverage_status' => $village->status,
            'plan_code' => $validated['plan_code'],
            'plan_name' => $plan->name,
            'speed_mbps' => $plan->speed_mbps,
            'monthly_price' => $plan->price,
            'installation_fee' => 0,
            'status' => 'Pending',
            'consented_at' => now(),
        ]);

        RegistrationLog::create([
            'customer_name' => $subscription->name,
            'phone_number' => $subscription->whatsapp,
            'village_name' => $village->name,
            'latitude' => $subscription->latitude,
            'longitude' => $subscription->longitude,
            'package_id' => $plan->id,
            'status' => 'Pending',
            'payload' => [
                'subscription_id' => $subscription->id,
                'address' => $subscription->full_address,
                'email' => $subscription->email,
                'billing_day' => $subscription->billing_day,
            ],
        ]);

        return response()->json([
            'message' => 'Pendaftaran berhasil dikirim. Tim BozonkNet akan menghubungi Anda dalam 1x24 jam.',
            'subscription_id' => $subscription->id,
        ], 201);
    }
}
