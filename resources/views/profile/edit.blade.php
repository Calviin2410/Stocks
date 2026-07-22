@extends('layouts.app')

@section('title', 'Edit Profile')

@section('content')
    <div class="container">
        <div class="profile-edit-card">
            <div class="profile-edit-header">
                <h1>Edit Profile</h1>
                <p>Update your personal information and view your MBTI profile.</p>
            </div>

            @if (session('success'))
                <div class="success-box">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="error-box">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="/profile/update" class="profile-edit-form">
                @csrf

                <div class="profile-field">
                    <label>Username</label>
                    <input
                        type="text"
                        name="username"
                        value="{{ old('username', $user->username) }}"
                        required
                    >
                </div>

                <div class="profile-field">
                    <label>Email</label>
                    <input
                        type="email"
                        name="email"
                        value="{{ old('email', $profile->email ?? '') }}"
                        required
                    >
                </div>

                <div class="profile-field">
                    <label>Phone Number</label>
                    <input
                        type="text"
                        name="phone_number"
                        value="{{ old('phone_number', $profile->phone_number ?? '') }}"
                        required
                    >
                </div>

                <div class="profile-field">
                    <label>Birth Date</label>
                    <input
                        type="date"
                        name="birth_date"
                        value="{{ old('birth_date', $profile?->birth_date?->format('Y-m-d')) }}"
                        required
                    >
                </div>

                <div class="profile-field">
                    <label>Age</label>
                    <input
                        type="text"
                        value="{{ $profile?->age ? $profile->age . ' years old' : '-' }}"
                        disabled
                    >
                </div>

                <div class="profile-field">
                    <label>Monthly Salary</label>
                    <input
                        type="number"
                        name="salary"
                        value="{{ old('salary', $profile->salary ?? '') }}"
                        step="0.01"
                        min="0"
                        required
                    >
                </div>

                <div class="profile-field">
                    <label>Job Position</label>
                    <input
                        type="text"
                        name="job_position"
                        value="{{ old('job_position', $profile->job_position ?? '') }}"
                        required
                    >
                </div>

                <div class="profile-field">
                    <label>MBTI Type</label>
                    <input
                        type="text"
                        value="{{ $profile->mbti_type ?? 'Not completed yet' }}"
                        disabled
                    >
                    <small>
                        MBTI type cannot be edited manually. Complete the MBTI quiz to update it.
                    </small>
                </div>

                <button type="submit" class="profile-save-btn">
                    Save Changes
                </button>
            </form>

            <div class="profile-subscription-section">
                <div>
                    <h2>Subscription</h2>
                    <p>View or manage your premium membership.</p>
                </div>

                @if ($profile?->hasActiveSubscription())
                    <div class="profile-subscription-active">
                        <div>
                            <span class="profile-plan-badge">Premium</span>

                            <h3>
                                {{ ucwords(str_replace('_', ' ', $profile->subscription_plan)) }}
                            </h3>

                            <p>
                                Price:
                                RM{{ number_format($profile->subscription_price, 2) }}
                            </p>

                            <p>
                                Started:
                                {{ $profile->subscribed_at?->format('d M Y') ?? '-' }}
                            </p>

                            <p>
                                Expires:
                                {{ $profile->subscription_expires_at?->format('d M Y') ?? '-' }}
                            </p>
                        </div>

                        <div class="profile-subscription-actions">
                            <a
                                href="{{ route('subscription.show') }}"
                                class="profile-renew-btn"
                            >
                                Extend Subscription
                            </a>

                            <form
                                method="POST"
                                action="{{ route('subscription.cancel') }}"
                            >
                                @csrf

                                <button type="submit" class="profile-cancel-btn">
                                    Cancel Subscription
                                </button>
                            </form>
                        </div>
                    </div>
                @else
                    <div class="profile-subscription-free">
                        <div>
                            <span class="profile-free-badge">Free Plan</span>

                            @if (
                                $profile?->subscription_expires_at
                                && $profile->subscription_expires_at->isPast()
                            )
                                <p>
                                    Your subscription expired on
                                    {{ $profile->subscription_expires_at->format('d M Y') }}.
                                </p>
                            @else
                                <p>You currently do not have an active subscription.</p>
                            @endif
                        </div>

                        <a
                            href="{{ route('subscription.show') }}"
                            class="profile-renew-btn"
                        >
                            View Packages
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <button id="chatbotOpenBtn" class="chatbot-open-btn show">
        MarketLens Assistant
    </button>

    <div id="chatbotBox" class="chatbot-box hide">
        <div class="chatbot-header">
            <span>Stock Assistant</span>
            <button id="chatbotCloseBtn" class="chatbot-close-btn">×</button>
        </div>

        <div id="chatbotMessages" class="chatbot-messages">
            <div class="bot-message">Hi, I’m your MarketLens assistant. Ask me about market news, stock strategy, or your personal investment profile.</div>
        </div>

        <div class="chatbot-input-row">
            <input
                id="chatbotInput"
                class="chatbot-input"
                type="text"
                placeholder="Ask about Nvidia, Apple, Tesla..."
            >

            <button id="chatbotSendBtn" class="chatbot-send-btn">
                Send
            </button>
        </div>
    </div>
@endsection

@push('scripts')
    @vite(['resources/js/app.js'])
@endpush