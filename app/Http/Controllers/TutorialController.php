<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

class TutorialController extends Controller
{
    public function index()
    {
        /** @var User $user */
        $user = Auth::user();

        if (!$user->profile?->hasActiveSubscription()) {
            return redirect()
                ->route('subscription.show')
                ->withErrors([
                    'subscription' => 'Tutorial page is only available for Premium users.',
                ]);
        }

        return view('tutorial.index');
    }
}