<?php

namespace Tests\Feature;

use App\Models\AreaRequest;
use App\Models\CustomerSubscription;
use App\Models\Village;
use Database\Seeders\BozonkNetSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoverageFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BozonkNetSeeder::class);
    }

    public function test_homepage_displays_seeded_coverage_data(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Internet lokal untuk warga')
            ->assertSee('Bojonggede')
            ->assertSee('ODP-RGJ-01');
    }

    public function test_clicked_coordinate_is_classified_by_radius(): void
    {
        $this->postJson(route('coverage.check'), [
            'latitude' => -6.4406,
            'longitude' => 106.8083,
        ])->assertOk()->assertJsonPath('status', 'available');

        $this->postJson(route('coverage.check'), [
            'latitude' => -6.4406,
            'longitude' => 106.8333,
        ])->assertOk()->assertJsonPath('status', 'expansion');

        $this->postJson(route('coverage.check'), [
            'latitude' => -6.4406,
            'longitude' => 106.8683,
        ])->assertOk()->assertJsonPath('status', 'unavailable');
    }

    public function test_village_check_uses_managed_village_status(): void
    {
        $village = Village::query()->where('name', 'Pabuaran')->firstOrFail();

        $this->postJson(route('coverage.check'), ['village_id' => $village->id])
            ->assertOk()
            ->assertJsonPath('status', 'expansion')
            ->assertJsonPath('location', 'Desa Pabuaran, Kecamatan Bojonggede');
    }

    public function test_customer_can_submit_an_area_request(): void
    {
        $this->postJson(route('area-requests.store'), [
            'name' => 'Budi Santoso',
            'address' => 'Pabuaran, Bojonggede',
            'whatsapp' => '0812-3456-7890',
            'latitude' => -6.4480,
            'longitude' => 106.7980,
            'coverage_status' => 'expansion',
        ])->assertCreated()->assertJsonStructure(['message', 'request_id']);

        $this->assertDatabaseHas(AreaRequest::class, [
            'name' => 'Budi Santoso',
            'whatsapp' => '0812-3456-7890',
            'coverage_status' => 'expansion',
            'status' => 'pending',
        ]);
    }

    public function test_area_request_rejects_invalid_whatsapp_number(): void
    {
        $response = $this->postJson(route('area-requests.store'), [
            'name' => 'Budi Santoso',
            'address' => 'Pabuaran, Bojonggede',
            'whatsapp' => 'abc',
        ]);

        $this->assertSame(422, $response->getStatusCode());
        $errors = json_decode($response->getContent(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame('Nomor WhatsApp tidak valid.', $errors['errors']['whatsapp'][0]);
    }

    public function test_customer_can_submit_a_subscription_for_available_village(): void
    {
        $village = Village::query()->where('name', 'Bojonggede')->firstOrFail();

        $this->postJson(route('subscriptions.store'), [
            'name' => 'Siti Aminah',
            'whatsapp' => '0812-6464-4446',
            'email' => 'siti@example.com',
            'billing_day' => 5,
            'village_id' => $village->id,
            'address' => 'Rumah nomor 12, RT 01/RW 05, dekat terminal.',
            'latitude' => -6.4406,
            'longitude' => 106.8083,
            'plan_code' => 'standard',
        ])->assertCreated()->assertJsonStructure(['message', 'subscription_id']);

        $this->assertDatabaseHas(CustomerSubscription::class, [
            'name' => 'Siti Aminah',
            'email' => 'siti@example.com',
            'billing_day' => 5,
            'village_id' => $village->id,
            'street_address' => 'Rumah nomor 12, RT 01/RW 05, dekat terminal.',
            'plan_code' => 'standard',
            'plan_name' => 'Internet Standard',
            'monthly_price' => 249750,
            'status' => 'pending',
        ]);
    }

    public function test_subscription_rejects_unavailable_village(): void
    {
        $village = Village::query()->where('name', 'Susukan')->firstOrFail();

        $this->postJson(route('subscriptions.store'), [
            'name' => 'Siti Aminah',
            'whatsapp' => '0812-6464-4446',
            'email' => 'siti@example.com',
            'billing_day' => 3,
            'village_id' => $village->id,
            'address' => 'Alamat lengkap Susukan.',
            'plan_code' => 'basic',
        ])->assertUnprocessable()
            ->assertJsonPath('errors.village_id.0', 'Desa yang dipilih belum berada dalam cakupan aktif.');

        $this->assertDatabaseMissing(CustomerSubscription::class, [
            'email' => 'siti@example.com',
            'village_id' => $village->id,
        ]);
    }
}
