<h2>{{ $certificateTitle ?? ('Quiz Passed: ' . $quizTitle) }}</h2>

<p>Hello {{ $studentName }},</p>

<p>Congratulations. You passed the quiz.</p>

<ul>
  <li>Quiz: {{ $quizTitle }}</li>
  <li>Score: {{ $score }}%</li>
  <li>Pass Mark: {{ $passPercentage }}%</li>
  <li>Attempt: {{ $attemptNumber }}</li>
  @if (!empty($certificateEnabled) && !empty($certificateCode))
  <li>Certificate Code: {{ $certificateCode }}</li>
  @endif
</ul>

<p>{{ $certificateBody ?? '' }}</p>

@if (!empty($certificateFooter))
<p>{{ $certificateFooter }}</p>
@endif

@if (!empty($certificateEnabled))
<p>This certificate confirms completion of the quiz at or above the pass mark.</p>
@else
<p>You have completed the quiz at or above the pass mark.</p>
@endif

<p>Regards,<br>{{ config('app.name') }}</p>
