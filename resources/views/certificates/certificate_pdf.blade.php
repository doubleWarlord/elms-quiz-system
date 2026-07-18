<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Certificate</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; margin: 0; padding: 24px; color: #1b2235; }
        .card { border: 8px double #152447; border-radius: 8px; padding: 28px; text-align: center; }
        .overline { text-transform: uppercase; letter-spacing: 3px; color: #3f4c68; margin: 0; font-size: 12px; }
        h1 { margin: 12px 0 20px; font-size: 34px; color: #10192f; }
        .body { font-size: 15px; line-height: 1.7; margin: 0 auto 22px; max-width: 650px; }
        .meta { text-align: left; margin: 0 auto; max-width: 520px; }
        .meta p { margin: 8px 0; }
        .footer { margin-top: 24px; color: #2e3d64; font-weight: 600; }
        .logo { max-width: 180px; max-height: 70px; display: block; margin: 0 auto 10px; }
    </style>
</head>
<body>
    <div class="card">
        @if (!empty($logoPath))
            <img src="{{ $logoPath }}" alt="Certificate logo" class="logo">
        @endif
        <p class="overline">Certificate</p>
        <h1>{{ $certificate['title'] }}</h1>
        <p class="body">{{ $certificate['body'] }}</p>

        <div class="meta">
            <p><strong>Student:</strong> {{ $student['name'] }}</p>
            <p><strong>Quiz:</strong> {{ $quiz['title'] }}</p>
            <p><strong>Score:</strong> {{ $studentQuiz['score'] }}%</p>
            <p><strong>Certificate Code:</strong> {{ $studentQuiz['certificate_code'] }}</p>
            <p><strong>Completed:</strong> {{ $studentQuiz['completed_at'] }}</p>
        </div>

        <p class="footer">{{ $certificate['footer'] }}</p>
    </div>
</body>
</html>
