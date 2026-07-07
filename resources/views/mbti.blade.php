@extends('layouts.app')

@section('title', 'MBTI Quiz')

@section('content')
    <div class="container">
        <div class="mbti-header">
            <h1>MBTI Personality Quiz</h1>
            <p>Start the test, complete it, then return here to check your result.</p>
        </div>

        <div class="mbti-result-card">
            <h2>Start MBTI Test</h2>
            <p>This will create a personality test link using Devil.ai API.</p>

            <button id="startMbtiBtn" class="mbti-submit-btn">
                Start Test
            </button>

            <button id="checkMbtiBtn" class="mbti-submit-btn secondary-btn">
                Check My Result
            </button>
        </div>

        <div id="mbtiResult" class="mbti-result"></div>
    </div>
@endsection

@push('scripts')
    @vite(['resources/js/mbti.js'])
@endpush