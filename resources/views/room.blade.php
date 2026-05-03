<!doctype html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>MOBA Draft - Room {{ $roomUuid }}</title>
    <style>
        :root { --bg:#0b1220; --panel:#111b2e; --text:#e5e7eb; --muted:#93a4c7; --accent:#60a5fa; --danger:#f87171; }
        body{ margin:0; font-family: ui-sans-serif,system-ui,-apple-system,"Segoe UI",Roboto,"Noto Sans JP",Arial; background:var(--bg); color:var(--text); }
        .wrap{ max-width:1520px; margin:0 auto; padding:16px; display:grid; grid-template-columns: 320px 1fr 320px 320px; grid-template-rows: auto auto; gap:16px; }
        .panel{ background:var(--panel); border:1px solid rgba(255,255,255,.08); border-radius:12px; padding:12px; }
        .title{ font-weight:700; font-size:14px; color:var(--muted); margin:0 0 10px; letter-spacing:.04em; text-transform:uppercase; }
        .row{ display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
        .btn{ background:#1f2a44; color:var(--text); border:1px solid rgba(255,255,255,.12); padding:8px 10px; border-radius:10px; cursor:pointer; }
        .btn.primary{ background:rgba(96,165,250,.18); border-color:rgba(96,165,250,.35); }
        .btn:disabled{ opacity:.45; cursor:not-allowed; }
        .input{ background:#0b1220; color:var(--text); border:1px solid rgba(255,255,255,.12); padding:8px 10px; border-radius:10px; width:100%; }
        .kv{ display:grid; grid-template-columns: 96px 1fr; gap:6px 10px; font-size:13px; }
        .kv div:nth-child(odd){ color:var(--muted); }
        .tag{ font-size:12px; padding:2px 8px; border-radius:999px; border:1px solid rgba(255,255,255,.14); color:var(--muted); }
        .tag.accent{ border-color:rgba(96,165,250,.4); color:#bfe0ff; }
        .tag.danger{ border-color:rgba(248,113,113,.4); color:#ffd1d1; }
        .grid{ display:grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap:10px; }
        @media (max-width: 1100px){ .wrap{ grid-template-columns: 1fr; } .grid{ grid-template-columns: repeat(6, minmax(0, 1fr)); } }
        @media (max-width: 700px){ .grid{ grid-template-columns: repeat(4, minmax(0, 1fr)); } }
        .card{ position:relative; border-radius:14px; overflow:hidden; border:1px solid rgba(255,255,255,.10); background:#0b1220; cursor:pointer; }
        .card img{ width:100%; aspect-ratio: 1 / 1; object-fit:cover; display:block; }
        /* number badge removed */
        .card[aria-disabled="true"]{ opacity:.4; cursor:not-allowed; }
        .card.selected{ outline:2px solid rgba(96,165,250,.8); outline-offset:2px; }
        .list{ display:grid; gap:8px; }
        .pill{ display:flex; gap:8px; align-items:center; padding:8px 10px; border-radius:10px; border:1px solid rgba(255,255,255,.10); background:rgba(255,255,255,.03); }
        .pill strong{ font-size:13px; }
        .pill span{ font-size:12px; color:var(--muted); }
        .small{ font-size:12px; color:var(--muted); }
        .statusBig{
            font-size:28px;
            font-weight:800;
            letter-spacing:.02em;
            line-height:1.25;
            margin:0;
        }
        .turnLine{ color:var(--text); transition: color .2s ease; }
        .turnLine--neutral{ color:var(--text); }
        .turnLine--mine{ color:#93c5fd; }
        .turnLine--opp{ color:#fca5a5; }
        .hr{ height:1px; background:rgba(255,255,255,.08); margin:12px 0; }
        .toast{ min-height:18px; font-size:12px; color:var(--muted); }
        .toast.error{ color: var(--danger); }
        .imgrow{ display:flex; flex-wrap:wrap; gap:8px; }
        .thumb{ width:56px; height:56px; border-radius:12px; overflow:hidden; border:1px solid rgba(255,255,255,.12); background:#0b1220; position:relative; }
        .thumb img{ width:100%; height:100%; object-fit:cover; display:block; }
        .thumb .badge{ position:absolute; left:6px; bottom:6px; font-size:11px; padding:1px 6px; border-radius:999px; background:rgba(0,0,0,.45); border:1px solid rgba(255,255,255,.16); }
        .draftRow{ display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
        .draftTags{ display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
        .spanAll{ grid-column: 1 / -1; }
        .topBlock{ display:flex; flex-direction:column; }
        .topGrow{ flex:1; min-height:0; }
        .sidebarCol{ display:flex; flex-direction:column; align-self:stretch; min-height:0; }
        .sidebarCol .topBlock{ flex:0 0 auto; }
        .sidebarCol .teamBlock{
            flex:1 1 auto;
            display:flex;
            flex-direction:column;
            min-height:0;
        }
        .sidebarCol .teamBlock .list{ flex:1 1 auto; }
        @media (max-width: 1200px){ .wrap{ grid-template-columns: 1fr; } .spanAll{ grid-column: 1; } .grid{ grid-template-columns: repeat(6, minmax(0, 1fr)); } }
        .ruleList{ display:grid; gap:10px; }
        .ruleItem{ padding:10px; border-radius:12px; border:1px solid rgba(255,255,255,.10); background:rgba(255,255,255,.03); }
        .ruleItem strong{ display:block; font-size:13px; margin-bottom:4px; }
        .ruleItem div{ font-size:12px; color:var(--muted); line-height:1.55; white-space:pre-wrap; }
        .toastCenter{
            position: fixed;
            left: 50%;
            top: 22%;
            transform: translate(-50%, -50%);
            padding: 10px 14px;
            border-radius: 14px;
            border: 1px solid rgba(255,255,255,.14);
            background: rgba(17,27,46,.92);
            color: var(--text);
            font-weight: 700;
            font-size: 42px;
            letter-spacing: .02em;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.35s ease;
            z-index: 9999;
        }
        .toastCenter.show{ opacity: 1; }
        .toastCenter.error{
            border-color: rgba(248,113,113,.55);
            background: rgba(127,29,29,.92);
            color: #ffe4e6;
        }
        .yourTurnBanner{
            position: fixed;
            left: 50%;
            top: 40%;
            z-index: 10000;
            pointer-events: none;
            padding: 22px 40px;
            border-radius: 16px;
            font-weight: 800;
            font-size: clamp(24px, 4.2vw, 40px);
            letter-spacing: .08em;
            color: #f8fafc;
            background: linear-gradient(155deg, #1d4ed8 0%, #0f172a 50%, #1e1b4b 100%);
            border: 2px solid rgba(125, 211, 252, 0.95);
            box-shadow:
                0 24px 56px rgba(0,0,0,.55),
                0 0 48px rgba(37, 99, 235, .4),
                inset 0 1px 0 rgba(255,255,255,.12);
            text-shadow: 0 2px 10px rgba(0,0,0,.5);
            opacity: 0;
            transform: translate(-50%, calc(-50% + 28px));
            transition:
                opacity 0.5s cubic-bezier(0.22, 1, 0.36, 1),
                transform 0.55s cubic-bezier(0.22, 1, 0.36, 1);
        }
        .yourTurnBanner.visible{
            opacity: 1;
            transform: translate(-50%, -50%);
        }
    </style>
</head>
<body>
<div class="wrap">
    <aside class="panel sidebarCol">
        <div class="topBlock">
            <p class="title" id="youTitle" style="margin:0;">未参加</p>
            <div class="row" style="justify-content:flex-start; align-items:center; margin-top:10px;">
                <button class="btn" id="shareBtn">共有</button>
                <button class="btn" id="leaveBtn">退出する</button>
            </div>
            <div class="hr"></div>
            <p class="title">ドラフト</p>
            <div class="draftRow">
                <button class="btn" id="joinBtn">参加</button>
                <button class="btn primary" id="startBtn">開始（左右リーダー参加で可能）</button>
                <div class="draftTags">
                    <span class="tag" id="phaseTag">フェイズ: -</span>
                    <span class="tag" id="turnTag">ターン: -</span>
                </div>
            </div>
            <div class="toast" id="toast"></div>
            <div class="topGrow"></div>
            <div class="hr"></div>
        </div>
        <div class="teamBlock">
            <p class="title">左チーム</p>
            <div class="list">
                <div>
                    <div class="small">BAN</div>
                    <div class="imgrow" id="leftBansRow"></div>
                </div>
                <div>
                    <div class="small">PICK</div>
                    <div class="imgrow" id="leftPicksRow"></div>
                </div>
            </div>
        </div>
    </aside>

    <main class="panel">
        <p class="title">キャラクター</p>
        <div class="small">クリックでヒーローを選択します。自分の番以外は操作できません</div>
        <div class="hr"></div>
        <div class="grid" id="grid">
            @foreach($images as $img)
                <button class="card" data-id="{{ $img['id'] }}" type="button">
                    <img src="{{ $img['src'] }}" alt="character {{ $img['id'] }}" loading="lazy">
                </button>
            @endforeach
        </div>
    </main>

    <aside class="panel sidebarCol">
        <div class="topBlock">
            <p class="statusBig turnLine turnLine--neutral" id="nowTurnTitle">現在の手番: -</p>
            <div class="hr"></div>
            <div class="statusBig" id="turnTimer">残り時間: -</div>
            <div class="hr"></div>
            <p class="title">カミドロー</p>
            <div class="imgrow" id="kamiRow"></div>
            <div class="topGrow"></div>
            <div class="hr"></div>
        </div>
        <div class="teamBlock">
            <p class="title">右チーム</p>
            <div class="list">
                <div>
                    <div class="small">BAN</div>
                    <div class="imgrow" id="rightBansRow"></div>
                </div>
                <div>
                    <div class="small">PICK</div>
                    <div class="imgrow" id="rightPicksRow"></div>
                </div>
            </div>
        </div>
    </aside>

    <aside class="panel">
        <p class="title">ルール / 操作説明</p>
        <div class="ruleList">
            <div class="ruleItem">
                <strong>参加</strong>
                <div>参加できるのは左右リーダーの2名のみです。
「参加」から両チームのリーダー参加後、「開始」で始まります。</div>
            </div>
            <div class="ruleItem">
                <strong>操作</strong>
                <div>中央のヒーロー画像をクリックして選択します。
自分の番以外は操作できません。</div>
            </div>
            <div class="ruleItem">
                <strong>枠数</strong>
                <div>BAN：各チーム3枠
PICK：各チーム4枠</div>
            </div>
            <div class="ruleItem">
                <strong>順番（固定）</strong>
                <div>1) 左 BAN1・左 BAN2
2) 右 BAN1・右 BAN2
3) 左 BAN3・左 PICK1
4) 右 BAN3・右 PICK1
5) 左 PICK2・左 PICK3
6) 右 PICK2・右 PICK3
7) 左 PICK4
8) 右 PICK4</div>
            </div>
            <div class="ruleItem">
                <strong>注意</strong>
                <div>時間内に選択できなかった場合は未選択で次の番になります</div>
            </div>
        </div>
    </aside>

    <section class="panel spanAll">
        <p class="title">参加者</p>
        <div class="list" id="players"></div>
    </section>
</div>

<div class="yourTurnBanner" id="yourTurnBanner" aria-live="polite"></div>
<div class="toastCenter" id="toastCenter">リンクをコピーしました</div>

<script>
(() => {
  const ROOM_UUID = @json($roomUuid);
  const tokenKey = `moba_draft.player_token.${ROOM_UUID}`;
  const KAMI_DEFAULT_SRC = @json($kamiDefaultSrc);

  const escapeHtml = (s) => String(s ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');

  const el = (id) => document.getElementById(id);
  const toastEl = el('toast');
  const toastCenterEl = el('toastCenter');
  const leaveBtn = el('leaveBtn');
  const shareBtn = el('shareBtn');
  const joinBtn = el('joinBtn');
  const startBtn = el('startBtn');
  const grid = el('grid');

  const FETCH_TIMEOUT_MS = 20000;

  let state = {
    room: null,
    draft: null,
    acting: false,
  };

  /** 手番変更の中央トーストを同一状態で二度出さない（多重ポーリング対策） */
  let lastDraftEventToastSig = null;

  let refreshInFlight = null;

  /** 現在の手番表示の色（自チーム＝青 / 相手チーム＝赤 / それ以外＝通常） */
  function syncTurnLineStyle(){
    const title = el('nowTurnTitle');
    if (!title) return;
    title.classList.remove('turnLine--neutral', 'turnLine--mine', 'turnLine--opp');
    const you = state.room?.you || null;
    const d = state.draft?.draft || null;
    if (!d || d.status !== 'running' || d.kami_lock_until || !d.next_action || !you) {
      title.classList.add('turnLine--neutral');
      return;
    }
    if (you.team === d.next_action.team) title.classList.add('turnLine--mine');
    else title.classList.add('turnLine--opp');
  }

  function setToast(msg, isError=false){
    toastEl.textContent = msg || '';
    toastEl.classList.toggle('error', !!isError);
  }

  let toastCenterTimer = null;
  function showCenterToast(message, isError=false, durationMs=2000){
    if (toastCenterTimer) clearTimeout(toastCenterTimer);
    toastCenterEl.textContent = message;
    toastCenterEl.classList.toggle('error', !!isError);
    toastCenterEl.classList.add('show');
    toastCenterTimer = setTimeout(() => {
      toastCenterEl.classList.remove('show');
    }, durationMs);
  }

  let yourTurnBannerTimer = null;
  let yourTurnBannerHideTimer = null;
  function showYourTurnBanner(actionType){
    const b = el('yourTurnBanner');
    if (!b) return;
    const label = actionType === 'ban' ? 'BAN' : 'PICK';
    const msg = `貴方の${label}です`;
    if (yourTurnBannerTimer) clearTimeout(yourTurnBannerTimer);
    if (yourTurnBannerHideTimer) clearTimeout(yourTurnBannerHideTimer);
    b.textContent = msg;
    b.classList.remove('visible');
    void b.offsetWidth;
    requestAnimationFrame(() => b.classList.add('visible'));
    yourTurnBannerTimer = setTimeout(() => {
      b.classList.remove('visible');
      yourTurnBannerHideTimer = setTimeout(() => { b.textContent = ''; }, 560);
    }, 2600);
  }

  function isMyActingTurn(you, d){
    return !!(d && d.status === 'running' && d.next_action && !d.kami_lock_until
      && you && you.is_leader && you.team === d.next_action.team);
  }

  function headers(extra={}){
    const h = { ...extra };
    const legacy = localStorage.getItem(tokenKey);
    if (legacy) h['X-Player-Token'] = legacy;
    return h;
  }

  async function api(path, opts={}){
    const controller = new AbortController();
    const tid = setTimeout(() => controller.abort(), FETCH_TIMEOUT_MS);
    let res;
    try {
      res = await fetch(path, {
        ...opts,
        credentials: 'same-origin',
        headers: headers(opts.headers || {}),
        signal: controller.signal,
      });
    } catch (e) {
      if (e && e.name === 'AbortError') {
        throw new Error('通信がタイムアウトしました。ネットワークを確認してください。');
      }
      throw e;
    } finally {
      clearTimeout(tid);
    }
    const text = await res.text();
    let json = null;
    try { json = text ? JSON.parse(text) : null; } catch(e) {}
    if (!res.ok) {
      const message = (json && (json.message || json.error)) ? (json.message || json.error) : `${res.status} ${res.statusText}`;
      throw new Error(message);
    }
    if (json?.you && localStorage.getItem(tokenKey)) {
      localStorage.removeItem(tokenKey);
    }
    return json;
  }

  function render(){
    const you = state.room?.you || null;
    if (!you) {
      el('youTitle').textContent = 'あなた:未参加';
    } else {
      const teamJa = you.team === 'left' ? '左チーム' : '右チーム';
      const roleJa = you.is_leader ? 'リーダー' : 'メンバー';
      el('youTitle').textContent = `あなた:${teamJa}/${roleJa}`;
    }

    // players
    const playersEl = el('players');
    playersEl.innerHTML = '';
    const players = state.room?.room?.players || [];
    players.forEach(p => {
      const div = document.createElement('div');
      div.className = 'pill';
      if ((p.seat_number || 0) <= 2) {
        const teamJa = p.team === 'left' ? '左チーム' : '右チーム';
        const roleJa = p.is_leader ? 'リーダー' : 'メンバー';
        div.innerHTML = `<strong>${escapeHtml(p.display_name)}</strong><span>（${teamJa}${roleJa}）</span>`;
      } else {
        div.innerHTML = `<strong>${escapeHtml(p.display_name)}</strong>`;
      }
      playersEl.appendChild(div);
    });

    // draft
    const d = state.draft?.draft || null;
    const roleOf = (id) => {
      const s = String(id || '');
      const n = parseInt(s, 10);
      if (!Number.isFinite(n)) return 'damage';
      const technical = new Set([1, 3, 8, 11, 19, 20, 21, 33, 34]);
      const tank = new Set([5, 7, 12, 13, 17, 23, 24, 25, 27]);
      if (technical.has(n)) return 'technical';
      if (tank.has(n)) return 'tank';
      return 'damage';
    };
    const phaseJa = (p) => {
      if (!p) return '-';
      if (p === 'ban') return 'BAN';
      if (p === 'pick') return 'PICK';
      if (p === 'kami') return 'カミドロー';
      if (p === 'done') return '完了';
      return p;
    };
    el('phaseTag').textContent = `フェイズ: ${phaseJa(d?.phase)}`;
    if (!d) {
      el('nowTurnTitle').textContent = '現在の手番: -';
      el('turnTimer').textContent = '残り時間: -';
      el('turnTag').textContent = 'ターン: -';
    } else if (d.status === 'completed') {
      el('nowTurnTitle').textContent = '現在の手番: 終了';
      el('turnTimer').textContent = '残り時間: -';
      el('turnTag').textContent = 'ターン: -';
    } else if (d.status === 'lobby') {
      el('nowTurnTitle').textContent = '現在の手番: 開始前';
      el('turnTimer').textContent = '残り時間: -';
      el('turnTag').textContent = 'ターン: -';
    } else if (d.status !== 'running') {
      el('nowTurnTitle').textContent = '現在の手番: -';
      el('turnTimer').textContent = '残り時間: -';
      el('turnTag').textContent = 'ターン: -';
    } else if (d?.next_action) {
      const typeJa = d.next_action.type === 'ban' ? 'BAN' : 'PICK';
      let turnLine;
      let tagLine;
      if (!you) {
        turnLine = `現在の手番: ${typeJa}`;
        tagLine = 'ターン: -';
      } else if (you.team === d.next_action.team) {
        turnLine = `現在の手番: 貴方のチームの${typeJa}`;
        tagLine = 'ターン: 貴方のチーム';
      } else {
        turnLine = `現在の手番: 相手のチームの${typeJa}`;
        tagLine = 'ターン: 相手のチーム';
      }
      el('turnTag').textContent = tagLine;
      el('nowTurnTitle').textContent = turnLine;
    } else if (d.kami_lock_until) {
      el('turnTag').textContent = 'ターン: -';
      el('nowTurnTitle').textContent = '現在の手番: （カミドロー表示中）';
    } else {
      el('turnTag').textContent = 'ターン: -';
      el('nowTurnTitle').textContent = '現在の手番: -';
      el('turnTimer').textContent = '残り時間: -';
    }

    const selected = new Set([...(d?.left_bans || []), ...(d?.right_bans || []), ...(d?.left_picks || []), ...(d?.right_picks || [])].map(String));

    const renderThumbRow = (rowId, ids, badge) => {
      const row = el(rowId);
      row.innerHTML = '';
      (ids || []).forEach((id) => {
        const wrap = document.createElement('div');
        wrap.className = 'thumb';
        if (String(id) === '__SKIP__') {
          wrap.innerHTML = `<div style="width:100%;height:100%;background:#000"></div>`;
        } else if (/^\d+$/.test(String(id))) {
          const sid = String(id);
          wrap.innerHTML = `<img src="/images/characters/${sid}.webp" alt="${escapeHtml(sid)}" loading="lazy">`;
        } else {
          wrap.innerHTML = `<div class="small" style="padding:4px;">?</div>`;
        }
        row.appendChild(wrap);
      });
      if (!ids || ids.length === 0) {
        const empty = document.createElement('div');
        empty.className = 'small';
        empty.textContent = '-';
        row.appendChild(empty);
      }
    };

    const renderKamiRow = (items) => {
      const row = el('kamiRow');
      row.innerHTML = '';
      (items || []).forEach((it) => {
        const wrap = document.createElement('div');
        wrap.className = 'thumb';
        const src = String(it.src || '');
        if (!src.startsWith('/images/')) return;
        wrap.innerHTML = `<img src="${escapeHtml(src)}" alt="${escapeHtml(it.name || '')}" loading="lazy">`;
        row.appendChild(wrap);
      });
      if (!items || items.length === 0) {
        if (KAMI_DEFAULT_SRC) {
          const wrap = document.createElement('div');
          wrap.className = 'thumb';
          wrap.innerHTML = `<img src="${KAMI_DEFAULT_SRC}" alt="0" loading="lazy">`;
          row.appendChild(wrap);
        } else {
          const empty = document.createElement('div');
          empty.className = 'small';
          // no hint when started
          if (!d || d.status !== 'running') {
            empty.textContent = '開始で2枚表示';
            row.appendChild(empty);
          }
        }
      }
    };

    renderThumbRow('leftBansRow', d?.left_bans);
    renderThumbRow('rightBansRow', d?.right_bans);
    renderThumbRow('leftPicksRow', d?.left_picks);
    renderThumbRow('rightPicksRow', d?.right_picks);
    renderKamiRow(d?.kami_draw);

    // enable/disable grid
    const next = d?.next_action || null;
    const canAct = !!(you && you.is_leader && next && you.team === next.team);
    const myTeamPicks = you?.team === 'left' ? (d?.left_picks || []) : (d?.right_picks || []);
    const myRoles = (myTeamPicks || []).filter(x => x !== '__SKIP__').map(roleOf);
    const tankTaken = myRoles.includes('tank');
    const techTaken = myRoles.includes('technical');
    const damageCount = myRoles.filter(r => r === 'damage').length;
    grid.querySelectorAll('.card').forEach(btn => {
      const id = String(btn.dataset.id || '');
      const isTaken = selected.has(id);
      let roleBlocked = false;
      if (canAct && next?.type === 'pick') {
        const r = roleOf(id);
        if (r === 'tank' && tankTaken) roleBlocked = true;
        if (r === 'technical' && techTaken) roleBlocked = true;
        if (r === 'damage' && damageCount >= 2) roleBlocked = true;
      }
      const enabled = !state.acting && canAct && !isTaken && !roleBlocked;
      btn.setAttribute('aria-disabled', enabled ? 'false' : 'true');
      btn.disabled = !enabled;
    });

    startBtn.disabled = state.acting || !(you && you.is_leader) || (d && d.status === 'completed');
    joinBtn.disabled = state.acting || !!you;

    syncTurnLineStyle();
  }

  function updateTimer(){
    const d = state.draft?.draft || null;
    if (!d || d.status !== 'running') {
      return;
    }
    if (d.kami_lock_until) {
      const until = Date.parse(d.kami_lock_until);
      if (Number.isNaN(until)) return;
      const now = Date.now();
      const remainingMs = until - now;
      const remaining = Math.max(0, Math.ceil(remainingMs / 1000));
      el('turnTimer').textContent = `カミドロー終了まで: ${remaining}秒`;
      return;
    }
    if (!d.turn_started_at) {
      return;
    }
    const started = Date.parse(d.turn_started_at);
    if (Number.isNaN(started)) return;
    const now = Date.now();
    const remainingMs = 30000 - (now - started);
    const remaining = Math.max(0, Math.ceil(remainingMs / 1000));
    el('turnTimer').textContent = `残り時間: ${remaining}秒`;
  }

  function refreshAll(){
    if (refreshInFlight) return refreshInFlight;
    refreshInFlight = (async () => {
      try {
        state.room = await api(`/api/rooms/${ROOM_UUID}`);
        const prev = state.draft?.draft || null;
        state.draft = await api(`/api/rooms/${ROOM_UUID}/draft`);
        render();
        updateTimer();

        const cur = state.draft?.draft || null;
        const prevKey = prev ? `${prev.status}:${prev.turn_index}` : null;
        const curKey = cur ? `${cur.status}:${cur.turn_index}` : null;
        const you = state.room?.you || null;

        if (prevKey && curKey && prevKey !== curKey) {
          if (isMyActingTurn(you, cur)) {
            const ver = cur?.version != null ? String(cur.version) : '';
            const sig = `yourTurn:${curKey}:v${ver}`;
            if (lastDraftEventToastSig !== sig) {
              lastDraftEventToastSig = sig;
              showYourTurnBanner(cur.next_action.type);
            }
          }
        } else if (
          prev && cur
          && prev.status === 'running'
          && cur.status === 'running'
          && prev.kami_lock_until
          && !cur.kami_lock_until
          && cur.next_action
        ) {
          if (isMyActingTurn(you, cur)) {
            const ver = cur?.version != null ? String(cur.version) : '';
            const sig = `yourTurn_kami:${curKey}:v${ver}`;
            if (lastDraftEventToastSig !== sig) {
              lastDraftEventToastSig = sig;
              showYourTurnBanner(cur.next_action.type);
            }
          }
        }
      } finally {
        refreshInFlight = null;
      }
    })();
    return refreshInFlight;
  }

  async function join(){
    await api(`/api/rooms/${ROOM_UUID}/join`, { method:'POST' });
    await refreshAll();
    setToast(`入室しました: ${state.room?.you?.display_name || ''}`);
  }

  async function leave(){
    await api(`/api/rooms/${ROOM_UUID}/leave`, { method:'POST' });
    localStorage.removeItem(tokenKey);
    // Always return to top after leaving
    window.location.href = '/';
  }

  async function start(){
    if (state.acting) return;
    state.acting = true;
    render();
    try {
      await api(`/api/rooms/${ROOM_UUID}/draft/start`, { method:'POST' });
      showCenterToast('カミドローが決定しました', false, 2000);
      await refreshAll();
    } finally {
      state.acting = false;
      render();
    }
  }

  async function act(characterId){
    if (state.acting) return;
    const d = state.draft?.draft;
    const next = d?.next_action;
    if (!next) return;
    const endpoint = next.type === 'ban' ? 'ban' : 'pick';
    const expectedVersion = d?.version;
    if (!expectedVersion) return;

    state.acting = true;
    render();
    try {
      await api(`/api/rooms/${ROOM_UUID}/draft/${endpoint}`, {
        method:'POST',
        headers:{ 'Content-Type':'application/json' },
        body: JSON.stringify({ character: String(characterId), expected_version: expectedVersion }),
      });
      await refreshAll();
    } catch (e) {
      showCenterToast(e.message || '操作できません', true, 1000);
      await refreshAll().catch(() => {});
    } finally {
      state.acting = false;
      render();
    }
  }

  leaveBtn.addEventListener('click', async () => {
    try {
      setToast('');
      if (state.room?.you) {
        await leave();
      } else {
        window.location.href = '/';
      }
    } catch (e) {
      setToast(e.message, true);
    }
  });
  shareBtn.addEventListener('click', async () => {
    try {
      const url = window.location.href;
      if (navigator.clipboard && window.isSecureContext) {
        await navigator.clipboard.writeText(url);
      } else {
        const ta = document.createElement('textarea');
        ta.value = url;
        ta.style.position = 'fixed';
        ta.style.left = '-9999px';
        document.body.appendChild(ta);
        ta.focus();
        ta.select();
        document.execCommand('copy');
        document.body.removeChild(ta);
      }
      showCenterToast('リンクをコピーしました');
    } catch (e) {
      setToast('コピーに失敗しました', true);
    }
  });
  joinBtn.addEventListener('click', async () => {
    try { setToast(''); await join(); } catch(e){ setToast(e.message, true); }
  });
  startBtn.addEventListener('click', async () => {
    try { setToast(''); await start(); } catch(e){ setToast(e.message, true); }
  });

  grid.addEventListener('click', async (ev) => {
    const btn = ev.target.closest('.card');
    if (!btn) return;
    const id = btn.dataset.id;
    try { setToast(''); await act(id); } catch(e){ setToast(e.message, true); }
  });

  refreshAll().catch((e) => { setToast(e.message, true); render(); });
  setInterval(() => refreshAll().catch(() => {}), 1200);
  setInterval(() => updateTimer(), 250);
})();
</script>
</body>
</html>

