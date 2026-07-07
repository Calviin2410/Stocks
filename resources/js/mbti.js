const startBtn = document.getElementById('startMbtiBtn');
const checkBtn = document.getElementById('checkMbtiBtn');
const resultBox = document.getElementById('mbtiResult');

function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]').getAttribute('content');
}

startBtn.addEventListener('click', async function () {
    resultBox.innerHTML = `
        <div class="loading-box">
            Creating MBTI test...
        </div>
    `;

    try {
        const response = await fetch('/mbti/new-test', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': getCsrfToken(),
                'Accept': 'application/json',
            },
        });

        const text = await response.text();
        console.log('Raw response:', text);

        let result;

        try {
            result = JSON.parse(text);
        } catch (error) {
            resultBox.innerHTML = `
                <div class="error-box">
                    Server did not return JSON. Check Console / Network.
                </div>
            `;
            return;
        }

        if (!result.success) {
            resultBox.innerHTML = `
                <div class="error-box">
                    ${result.message || 'Failed to create MBTI test.'}
                </div>
            `;
            console.log(result);
            return;
        }

        const apiData = result.data?.data || result.data;

        const testId = apiData?.test_id;
        const testUrl = apiData?.test_url;

        if (!testId || !testUrl) {
            resultBox.innerHTML = `
                <div class="error-box">
                    API response does not contain test_id or test_url.
                </div>
            `;
            console.log(result);
            return;
        }

        localStorage.setItem('mbti_test_id', testId);
        console.log('Saved MBTI test id:', testId);

        window.location.href = testUrl;
    } catch (error) {
        resultBox.innerHTML = `
            <div class="error-box">
                MBTI API request failed. Check browser Console.
            </div>
        `;
        console.error(error);
    }
});

checkBtn.addEventListener('click', async function () {
    const testId = localStorage.getItem('mbti_test_id');
    console.log('Checking MBTI test id:', testId);

    if (!testId) {
        resultBox.innerHTML = `
            <div class="error-box">
                No MBTI test found. Please start the test first.
            </div>
        `;
        return;
    }

    resultBox.innerHTML = `
        <div class="loading-box">
            Checking result...
        </div>
    `;

    try {
        const response = await fetch(`/mbti/check-test?test_id=${encodeURIComponent(testId)}`);
        const result = await response.json();

        console.log('Check result:', result);

        if (!result.success) {
            resultBox.innerHTML = `
                <div class="error-box">
                    ${result.message || 'Failed to check MBTI result.'}
                </div>
            `;
            console.log(result);
            return;
        }

        const data = result.data?.data || result.data || result.raw?.data;

        if (!data) {
            resultBox.innerHTML = `
                <div class="loading-box">
                    Your test is not completed yet, or this test ID does not match the completed test.
                    Please finish the test first, then check again.
                </div>
            `;
            console.log(result);
            return;
        }

        const prediction = data.prediction || data.personality_type || data.type || data.result;

        if (!prediction) {
            resultBox.innerHTML = `
                <div class="loading-box">
                    Result found, but MBTI type is not available yet.
                </div>
            `;
            console.log(data);
            return;
        }

        const investmentProfile = result.investment_profile;

        resultBox.innerHTML = `
            <div class="mbti-result-card">
                <h2>Your MBTI Result: ${prediction}</h2>
                <p>Result Date: ${data.result_date || '-'}</p>

                ${data.results_page ? `<a href="${data.results_page}" target="_blank">View Full Result</a>` : ''}

                <hr>

                <h3>Investment Style: ${investmentProfile?.risk_style || 'Unknown'}</h3>

                <p>
                    <strong>Strategy:</strong>
                    ${investmentProfile?.strategy || 'No strategy available.'}
                </p>

                <p>
                    <strong>Risk Reminder:</strong>
                    ${investmentProfile?.warning || 'Investment involves risk. Please make your own decision carefully.'}
                </p>

                <small>
                    This is based on MBTI-style self-reflection only. It is not professional financial advice.
                </small>
            </div>
        `;
    } catch (error) {
        resultBox.innerHTML = `
            <div class="error-box">
                Failed to check result. Please check Console.
            </div>
        `;
        console.error(error);
    }
});