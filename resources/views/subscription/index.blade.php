@extends('layouts.app')

@section('title', 'Subscribe')

@section('content')
    <div class="container">
        <div class="subscription-page">
            <div class="subscription-header">
                <span class="subscription-demo-badge">
                    MarketLens Premium
                </span>

                <h1>Choose Your Premium Package</h1>

                <p>
                    Select a package and enter card information.
                </p>
            </div>

            @if ($profile?->hasActiveSubscription())
                <div class="current-subscription-box">
                    <strong>Current subscription:</strong>

                    {{ ucwords(str_replace('_', ' ', $profile->subscription_plan)) }}

                    <span>
                        Expires on
                        {{ $profile->subscription_expires_at->format('d M Y') }}
                    </span>
                </div>
            @endif

            @if ($errors->any())
                <div class="error-box">
                    {{ $errors->first() }}
                </div>
            @endif

            <form
                method="POST"
                action="{{ route('subscription.subscribe') }}"
                class="subscription-form"
                autocomplete="off"
            >
                @csrf

                <div class="subscription-package-grid">
                    @foreach ($packages as $key => $package)
                        <label class="subscription-package-card">
                            <input
                                type="radio"
                                name="plan"
                                value="{{ $key }}"
                                {{ old('plan', '1_month') === $key ? 'checked' : '' }}
                            >

                            <div class="subscription-package-content">
                                <h3>{{ $package['name'] }}</h3>

                                <p>Premium access</p>

                                <strong>
                                    RM{{ number_format($package['price'], 2) }}
                                </strong>
                            </div>
                        </label>
                    @endforeach
                </div>

                <div class="demo-payment-section">
                    <h2>Payment Details</h2>

                    <div class="subscription-field">
                        <label for="card_holder">Card Holder Name</label>

                        <input
                            id="card_holder"
                            type="text"
                            name="card_holder"
                            value="{{ old('card_holder') }}"
                            placeholder="Calvin Tan"
                            required
                        >
                    </div>

                    <div class="subscription-field">
                        <label for="card_number">Card Number</label>

                        <input
                            id="card_number"
                            type="text"
                            name="card_number"
                            value="{{ old('card_number') }}"
                            placeholder="1234 5678 9012 3456"
                            inputmode="numeric"
                            maxlength="23"
                            required
                        >
                    </div>

                    <div class="subscription-payment-row">
                        <div class="subscription-field">
                            <label for="expiry_date">Expiry Date</label>

                            <input
                                id="expiry_date"
                                type="text"
                                name="expiry_date"
                                value="{{ old('expiry_date') }}"
                                placeholder="12/30"
                                maxlength="5"
                                required
                            >
                        </div>

                        <div class="subscription-field">
                            <label for="cvv">CVV</label>

                            <input
                                id="cvv"
                                type="password"
                                name="cvv"
                                placeholder="123"
                                inputmode="numeric"
                                maxlength="4"
                                required
                            >
                        </div>
                    </div>

                    <button type="submit" class="subscription-submit-btn">
                        Activate or Extend Subscription
                    </button>
                </div>
            </form>
        </div>
    </div>

    <button id="chatbotOpenBtn" class="chatbot-open-btn show">
        Stock Assistant
    </button>

    <div id="chatbotBox" class="chatbot-box hide">
        <div class="chatbot-header">
            <span>Stock Assistant</span>
            <button id="chatbotCloseBtn" class="chatbot-close-btn">×</button>
        </div>

        <div id="chatbotMessages" class="chatbot-messages">
            <div class="bot-message">
                Hi, ask me about the latest stock news.
            </div>
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