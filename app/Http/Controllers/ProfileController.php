<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Actualiza datos, foto y visibilidad del perfil (US-13).
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->fill($request->datosOperador());

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        if ($request->hasFile('foto')) {
            $this->borrarFoto($user);
            $user->foto_path = $request->file('foto')->store('fotos', 'public');
        } elseif ($request->boolean('quitar_foto')) {
            $this->borrarFoto($user);
            $user->foto_path = null;
        }

        $user->save();
        $user->visibilidad()->updateOrCreate([], $request->visibilidad());

        return Redirect::route('profile.edit')->with('status', 'Perfil actualizado.');
    }

    private function borrarFoto(User $user): void
    {
        if ($user->foto_path !== null) {
            Storage::disk('public')->delete($user->foto_path);
        }
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $this->borrarFoto($user);
        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
