<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $data['title'] ?? 'Exam Travel Assist' }}</title>
</head>
<body style="margin:0;padding:24px;background:#f4f6fb;font-family:system-ui,-apple-system,'Segoe UI',Roboto,sans-serif;color:#16202e">
    <div style="max-width:560px;margin:0 auto;background:#ffffff;border:1px solid #e2e8f0;border-radius:14px;padding:24px">
        <p style="margin:0 0 16px;font-weight:700;color:#0f2a52">Exam Travel Assist</p>
        <h1 style="margin:0 0 12px;font-size:20px">{{ $data['title'] ?? 'Notification' }}</h1>
        <p style="margin:0 0 12px;line-height:1.6">{!! nl2br(e($data['body'] ?? '')) !!}</p>
        @if(! empty($data['action_url']))
            <p style="margin:20px 0">
                <a href="{{ $data['action_url'] }}" style="background:#1d4ed8;color:#fff;padding:10px 16px;border-radius:10px;text-decoration:none;display:inline-block">
                    {{ $data['action_label'] ?? 'Open Exam Travel Assist' }}
                </a>
            </p>
        @endif
        <hr style="border:0;border-top:1px solid #e2e8f0;margin:24px 0">
        <p style="margin:0;font-size:12px;color:#64748b">
            You received this email because you have an Exam Travel Assist account.
            This is an independent student help platform and is not affiliated with any examination authority,
            railway, transport operator or government body.
        </p>
    </div>
</body>
</html>
