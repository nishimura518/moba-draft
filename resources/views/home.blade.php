<!doctype html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ゲキスクドラフトツール</title>
    <style>
        :root { --bg:#0b1220; --panel:#111b2e; --text:#e5e7eb; --muted:#93a4c7; --accent:#60a5fa; --danger:#f87171; }
        body{ margin:0; font-family: ui-sans-serif,system-ui,-apple-system,"Segoe UI",Roboto,"Noto Sans JP",Arial; background:var(--bg); color:var(--text); }
        .wrap{ max-width:980px; margin:0 auto; padding:24px 16px; }
        .hero{ display:grid; gap:16px; padding:18px; border-radius:16px; background:linear-gradient(180deg, rgba(96,165,250,.14), rgba(96,165,250,.04)); border:1px solid rgba(255,255,255,.10); }
        h1{ margin:0; font-size:22px; letter-spacing:.02em; }
        p{ margin:0; color:var(--muted); line-height:1.6; }
        .panel{ background:var(--panel); border:1px solid rgba(255,255,255,.08); border-radius:14px; padding:14px; }
        .title{ font-weight:700; font-size:13px; color:var(--muted); margin:0 0 10px; letter-spacing:.04em; text-transform:uppercase; }
        .row{ display:flex; gap:10px; align-items:center; flex-wrap:wrap; }
        .btn{ background:#1f2a44; color:var(--text); border:1px solid rgba(255,255,255,.12); padding:10px 12px; border-radius:12px; cursor:pointer; font-weight:600; }
        .btn.primary{ background:rgba(96,165,250,.18); border-color:rgba(96,165,250,.35); }
        .error{ margin-top:10px; color:var(--danger); font-size:13px; }
        .hint{ font-size:12px; color:var(--muted); }
        code{ background:rgba(255,255,255,.06); padding:2px 6px; border-radius:8px; }
        .contact-note{ margin-top:28px; font-size:13px; color:var(--muted); line-height:1.6; }
        .contact-note a{ color:var(--accent); text-decoration:none; }
        .contact-note a:hover{ text-decoration:underline; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="hero">
        <h1>ゲキスクドラフトツール</h1>
        <p>部屋を作成してURL共有で参加できます。<br>
        入室後「参加」でリーダーとなり、両チームのリーダーが揃ってから「開始」でドラフトを開始します。<br>
        間違えてリーダーになってしまった場合は、退出ボタンから退出してください。</p>

        <form method="post" action="/rooms">
            @csrf
            <div class="row">
                <button class="btn primary" type="submit">部屋を作成して開始</button>
            </div>
            @if ($errors->any())
                <div class="error">{{ $errors->first() }}</div>
            @endif
        </form>

        <p class="contact-note">何かあればこちらまでどうぞ→ <a href="https://x.com/FT_GEKISUKU" target="_blank" rel="noopener noreferrer">https://x.com/FT_GEKISUKU</a></p>
    </div>
</div>
</body>
</html>

