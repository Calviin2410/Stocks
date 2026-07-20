<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;


class ProfileController extends Controller
{
    public function edit()
    {
        $user = Auth::user();
        $profile = $user->profile;

        return view('profile.edit', [
            'user' => $user,
            'profile' => $profile,
        ]);
    }

    public function update(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        $minBirthDate = now()->subYears(70)->toDateString();
        $maxBirthDate = now()->subYears(18)->toDateString();

        $validated = $request->validate([
            'username' => [
                'required',
                'string',
                'max:255',
                'unique:users,username,' . $user->id,
            ],
            'email' => [
                'required',
                'email',
                'unique:profiles,email,' . optional($user->profile)->id,
            ],
            'phone_number' => [
                'required',
                'string',
                'max:30',
            ],
            'birth_date' => [
                'required',
                'date',
                'after_or_equal:' . $minBirthDate,
                'before_or_equal:' . $maxBirthDate,
            ],
            'salary' => ['required', 'numeric', 'min:0'],
            'job_position' => ['required', 'string', 'max:255'],
        ], [
            'birth_date.after_or_equal' => 'You must be 70 years old or below.',
            'birth_date.before_or_equal' => 'You must be at least 18 years old.',
        ]);

        DB::transaction(function () use ($user, $validated) {
            $user->update([
                'username' => $validated['username'],
            ]);

            $user->profile()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'email' => $validated['email'],
                    'phone_number' => $validated['phone_number'],
                    'birth_date' => $validated['birth_date'],
                    'salary' => $validated['salary'],
                    'job_position' => $validated['job_position'],
                ]
            );
        });

        return redirect('/profile/edit')->with('success', 'Profile updated successfully.');
    }
}