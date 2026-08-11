<?php

namespace App\Services;

use App\Models\CartItem;
use App\Models\Favorite;
use App\Models\User;
use Illuminate\Http\Request;

class GuestCommerceService
{
    public function attachToUser(Request $request, User $user): void
    {
        $sessionId = $request->session()->getId();

        $sessionCart = collect($request->session()->get('guest_cart', []))
            ->mapWithKeys(
                fn ($quantity, $productId) => [
                    (int) $productId => max(1, min(99, (int) $quantity)),
                ]
            );

        $sessionCart->each(
            function (int $quantity, int $productId) use ($user): void {
                $existing = CartItem::where('user_id', $user->id)
                    ->where('product_id', $productId)
                    ->first();

                if ($existing) {
                    $existing->update([
                        'quantity' => min(
                            99,
                            $existing->quantity + $quantity
                        ),
                    ]);

                    return;
                }

                CartItem::create([
                    'user_id' => $user->id,
                    'product_id' => $productId,
                    'quantity' => $quantity,
                ]);
            }
        );

        $request->session()->forget('guest_cart');

        CartItem::where('session_id', $sessionId)
            ->get()
            ->each(function (CartItem $guestItem) use ($user): void {
                $existing = CartItem::where('user_id', $user->id)
                    ->where('product_id', $guestItem->product_id)
                    ->first();

                if ($existing) {
                    $existing->increment(
                        'quantity',
                        $guestItem->quantity
                    );

                    $guestItem->delete();

                    return;
                }

                $guestItem->update([
                    'user_id' => $user->id,
                    'session_id' => null,
                ]);
            });

        Favorite::where('session_id', $sessionId)
            ->get()
            ->each(function (Favorite $guestFavorite) use ($user): void {
                $existing = Favorite::where('user_id', $user->id)
                    ->where('product_id', $guestFavorite->product_id)
                    ->first();

                if ($existing) {
                    $guestFavorite->delete();

                    return;
                }

                $guestFavorite->update([
                    'user_id' => $user->id,
                    'session_id' => null,
                ]);
            });
    }
}
