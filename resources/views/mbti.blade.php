@extends('layouts.app')

@section('title', 'MBTI Quiz')

@section('content')
    <div class="container">
        <div class="mbti-hero">
            <div>
                <span class="mbti-badge">Personality Investment Profile</span>

                <h1>Discover Your MBTI Investment Style</h1>

                <p>
                    Complete a short MBTI-style personality test to understand your decision-making style,
                    risk tendency, and investment behaviour.
                </p>
            </div>
        </div>

        <div class="mbti-steps-grid">
            <div class="mbti-step-card active-step">
                <div class="mbti-step-number">1</div>
                <h3>Start the Test</h3>
                <p>
                    Click the button below to open the MBTI test page.
                </p>
            </div>

            <div class="mbti-step-card">
                <div class="mbti-step-number">2</div>
                <h3>Complete the Questions</h3>
                <p>
                    Answer all questions honestly on Devil.ai.
                </p>
            </div>

            <div class="mbti-step-card">
                <div class="mbti-step-number">3</div>
                <h3>Check Your Result</h3>
                <p>
                    Return here and click “Check My Result”.
                </p>
            </div>
        </div>

        <div class="mbti-action-card">
            <div class="mbti-action-left">
                <h2>Ready to begin?</h2>
                <p>
                    Press <strong>Start Test</strong> to begin your MBTI test. After finishing the test,
                    come back to this page and press <strong>Check My Result</strong>.
                </p>

                <div class="mbti-note-box">
                    Your result will be saved to your profile and used to personalize your investment style reminder.
                </div>
            </div>

            <div class="mbti-action-buttons">
                <button id="startMbtiBtn" class="mbti-primary-btn">
                    Start MBTI Test
                </button>

                <button id="checkMbtiBtn" class="mbti-secondary-btn">
                    Check My Result
                </button>
            </div>
        </div>

        <div id="mbtiResult" class="mbti-result"></div>
    </div>
@endsection

@push('scripts')
    @vite(['resources/js/mbti.js'])
@endpush