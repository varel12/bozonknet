<?php

namespace App\Http\Controllers;

use App\Models\InternetPackage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InternetPackageController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $this->ensureAdmin();

        $data = $request->validate([
            'code' => ['required', 'string', 'max:30', 'alpha_dash', 'unique:internet_packages,code'],
            'name' => ['required', 'string', 'max:100'],
            'speed_mbps' => ['required', 'integer', 'min:1', 'max:10000'],
            'price' => ['required', 'integer', 'min:0'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        InternetPackage::create($data);

        return redirect()->route('internal.admin', ['page' => 'packages'])->with('status', 'Paket internet berhasil ditambahkan.');
    }

    public function update(Request $request, InternetPackage $internetPackage): RedirectResponse
    {
        $this->ensureAdmin();

        $data = $request->validate([
            'code' => ['required', 'string', 'max:30', 'alpha_dash', Rule::unique('internet_packages', 'code')->ignore($internetPackage->id)],
            'name' => ['required', 'string', 'max:100'],
            'speed_mbps' => ['required', 'integer', 'min:1', 'max:10000'],
            'price' => ['required', 'integer', 'min:0'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        $internetPackage->update($data);

        return redirect()->route('internal.admin', ['page' => 'packages'])->with('status', 'Paket internet berhasil diperbarui.');
    }

    public function destroy(InternetPackage $internetPackage): RedirectResponse
    {
        $this->ensureAdmin();

        $internetPackage->delete();

        return redirect()->route('internal.admin', ['page' => 'packages'])->with('status', 'Paket internet berhasil dihapus.');
    }

    private function ensureAdmin(): void
    {
        abort_unless(in_array(auth()->user()?->role, ['admin', 'super_admin'], true), 403);
    }
}
