<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\Favorite;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AccountAuthController extends Controller
{
    public function register(Request $request): RedirectResponse
    {
        $attributes = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create($attributes + ['is_admin' => false]);

        Auth::login($user);
        $this->attachGuestCommerce($request, $user);
        $request->session()->regenerate();

        return redirect()->route('home')->with('status', 'Your Lumina Beauty account is ready.');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'The email or password is incorrect.',
            ])->redirectTo(url()->previous().'#account');
        }

        $this->attachGuestCommerce($request, Auth::user());
        $request->session()->regenerate();

        return redirect()->intended(route('home'));
    }

    public function adminLogin(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember')) || ! Auth::user()->is_admin) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => 'Admin access is available only for developer-created admin accounts.',
            ])->redirectTo(url()->previous().'#account');
        }

        $this->attachGuestCommerce($request, Auth::user());
        $request->session()->regenerate();

        return redirect()->route('admin.dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    private function attachGuestCommerce(Request $request, User $user): void
    {
        $sessionId = $request->session()->getId();
        $sessionCart = collect($request->session()->get('guest_cart', []))
            ->mapWithKeys(fn ($quantity, $productId) => [(int) $productId => max(1, min(99, (int) $quantity))]);

        $sessionCart->each(function (int $quantity, int $productId) use ($user): void {
            $existing = CartItem::where('user_id', $user->id)->where('product_id', $productId)->first();

            if ($existing) {
                $existing->update(['quantity' => min(99, $existing->quantity + $quantity)]);

                return;
            }

            CartItem::create([
                'user_id' => $user->id,
                'product_id' => $productId,
                'quantity' => $quantity,
            ]);
        });

        $request->session()->forget('guest_cart');

        CartItem::where('session_id', $sessionId)->get()->each(function (CartItem $guestItem) use ($user): void {
            $existing = CartItem::where('user_id', $user->id)->where('product_id', $guestItem->product_id)->first();

            if ($existing) {
                $existing->increment('quantity', $guestItem->quantity);
                $guestItem->delete();

                return;
            }

            $guestItem->update(['user_id' => $user->id, 'session_id' => null]);
        });

        Favorite::where('session_id', $sessionId)->get()->each(function (Favorite $guestFavorite) use ($user): void {
            $existing = Favorite::where('user_id', $user->id)->where('product_id', $guestFavorite->product_id)->first();

            if ($existing) {
                $guestFavorite->delete();

                return;
            }

            $guestFavorite->update(['user_id' => $user->id, 'session_id' => null]);
        });
    }
}
