<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class SubscriptionController extends Controller
{
    public function show()
    {
        /** @var User $user */
        $user = Auth::user();

        return view('subscription.index', [
            'packages' => config('subscriptions'),
            'profile' => $user->profile,
        ]);
    }

    public function subscribe(Request $request)
    {
        $packages = config('subscriptions');

        // 移除用户输入中的空格或横线
        $request->merge([
            'card_number' => preg_replace(
                '/\D/',
                '',
                (string) $request->input('card_number')
            ),
            'cvv' => preg_replace(
                '/\D/',
                '',
                (string) $request->input('cvv')
            ),
        ]);

        $validated = $request->validate([
            'plan' => [
                'required',
                Rule::in(array_keys($packages)),
            ],
            'card_holder' => [
                'required',
                'string',
                'max:255',
            ],
            'card_number' => [
                'required',
                'digits_between:12,19',
            ],
            'expiry_date' => [
                'required',
                'regex:/^(0[1-9]|1[0-2])\/\d{2}$/',
            ],
            'cvv' => [
                'required',
                'digits_between:3,4',
            ],
        ], [
            'expiry_date.regex' => 'Expiry date must use MM/YY format.',
        ]);

        /** @var User $user */
        $user = Auth::user();

        $profile = $user->profile;

        if (!$profile) {
            return back()->withErrors([
                'profile' => 'User profile was not found.',
            ]);
        }

        $selectedPackage = $packages[$validated['plan']];

        /*
         * 如果原本的订阅还没过期，从原本 expiry date 继续加。
         * 如果已经过期或未订阅，则从今天开始计算。
         */
        $startDate = $profile->subscription_expires_at
            && $profile->subscription_expires_at->isFuture()
                ? $profile->subscription_expires_at->copy()
                : now();

        $newExpiryDate = $startDate
            ->copy()
            ->addMonthsNoOverflow($selectedPackage['months']);

        $profile->update([
            'is_subscribed' => true,
            'subscription_plan' => $validated['plan'],
            'subscription_price' => $selectedPackage['price'],
            'subscribed_at' => now(),
            'subscription_expires_at' => $newExpiryDate,
        ]);

        /*
         * card_holder、card_number、expiry_date、cvv
         * 不会保存进 database。
         */

        return redirect()
            ->route('profile.edit')
            ->with(
                'success',
                'Subscription activated successfully. Expiry date: '
                . $newExpiryDate->format('d M Y')
            );
    }

    public function cancel()
    {
        /** @var User $user */
        $user = Auth::user();

        if ($user->profile) {
            $user->profile->update([
                'is_subscribed' => false,
                'subscription_plan' => null,
                'subscription_price' => null,
                'subscribed_at' => null,
                'subscription_expires_at' => null,
            ]);
        }

        return redirect()
            ->route('profile.edit')
            ->with('success', 'Subscription cancelled successfully.');
    }
}