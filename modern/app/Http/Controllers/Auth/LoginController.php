<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class LoginController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('auth/Login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('username', $credentials['username'])->first();

        if (! $user || ! $this->passwordMatches($user, $credentials['password'])) {
            throw ValidationException::withMessages([
                'username' => 'These credentials do not match our records.',
            ]);
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended('/');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * Users imported from the legacy app only have an unsalted MD5 hash. If it
     * matches, rehash with bcrypt and throw the MD5 away, so nobody has to reset
     * their password at cutover.
     */
    private function passwordMatches(User $user, string $password): bool
    {
        if ($user->password !== null) {
            return Hash::check($password, $user->password);
        }

        if ($user->legacy_password !== null && hash_equals($user->legacy_password, md5($password))) {
            $user->forceFill(['password' => $password, 'legacy_password' => null])->save();

            return true;
        }

        return false;
    }
}
