<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InternalUserController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $this->ensureAdmin();
        $allowedRoles = $this->allowedRoles();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'role' => ['required', Rule::in($allowedRoles)],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        User::create($data);

        return back()->with('status', 'User internal berhasil ditambahkan.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->ensureAdmin();
        $allowedRoles = $this->allowedRoles();

        abort_if(
            $user->role === 'super_admin' && auth()->user()?->role !== 'super_admin',
            403,
            'Hanya Super Admin yang bisa mengubah akun Super Admin.'
        );

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:6'],
            'role' => ['required', Rule::in($allowedRoles)],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        if (blank($data['password'])) {
            unset($data['password']);
        }

        $user->update($data);

        return back()->with('status', 'User internal berhasil diperbarui.');
    }

    public function deactivate(User $user): RedirectResponse
    {
        $this->ensureAdmin();

        abort_if(auth()->id() === $user->id, 422, 'Akun sendiri tidak bisa dinonaktifkan.');
        abort_if(
            $user->role === 'super_admin' && auth()->user()?->role !== 'super_admin',
            403,
            'Hanya Super Admin yang bisa menonaktifkan akun Super Admin.'
        );

        $user->update(['status' => 'inactive']);

        return back()->with('status', 'User internal berhasil dinonaktifkan.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->ensureAdmin();

        abort_if(auth()->id() === $user->id, 422, 'Akun sendiri tidak bisa dihapus.');
        abort_if(
            $user->role === 'super_admin' && auth()->user()?->role !== 'super_admin',
            403,
            'Hanya Super Admin yang bisa menghapus akun Super Admin.'
        );

        $user->delete();

        return back()->with('status', 'User internal berhasil dihapus.');
    }

    private function ensureAdmin(): void
    {
        abort_unless(in_array(auth()->user()?->role, ['super_admin', 'admin'], true), 403);
    }

    /**
     * @return array<int, string>
     */
    private function allowedRoles(): array
    {
        return auth()->user()?->role === 'super_admin'
            ? ['super_admin', 'admin', 'teknisi']
            : ['admin', 'teknisi'];
    }
}
