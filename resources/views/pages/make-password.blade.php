<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Bcrypt Hash</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', system-ui, -apple-system, sans-serif; background: #f8fafc; margin: 0; padding: 0; }
        .wrap { max-width: 640px; margin: 40px auto; background: #fff; border-radius: 12px; padding: 24px; box-shadow: 0 10px 40px rgba(0,0,0,0.05); }
        h1 { margin: 0 0 12px; font-size: 20px; color: #0f172a; }
        form { display: flex; gap: 12px; flex-wrap: wrap; }
        input[type="text"] { flex: 1; min-width: 240px; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; }
        button { padding: 10px 16px; border: none; border-radius: 8px; background: #2563eb; color: #fff; font-weight: 600; cursor: pointer; }
        button:hover { background: #1d4ed8; }
        .field { margin-top: 16px; }
        .label { font-size: 12px; color: #64748b; margin-bottom: 4px; text-transform: uppercase; letter-spacing: .04em; }
        .value { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; background: #f1f5f9; padding: 10px 12px; border-radius: 8px; word-break: break-all; }
    </style>
</head>
<body>
    <div class="wrap">
        <h1>Generate Bcrypt Hash</h1>
        <p style="margin: 0 0 16px; color: #475569;">Enter a password and submit to see its bcrypt hash. This page is public; do not share real passwords here.</p>
        <form method="get" action="{{ route('make.password') }}">
            <input type="text" name="value" value="{{ $input }}" placeholder="Enter password to hash" autocomplete="off">
            <button type="submit">Generate</button>
        </form>

        <div class="field">
            <div class="label">Input</div>
            <div class="value">{{ $input !== '' ? $input : '—' }}</div>
        </div>

        <div class="field">
            <div class="label">Bcrypt Hash</div>
            <div class="value">{{ $hash ?? '—' }}</div>
        </div>
    </div>
</body>
</html>
