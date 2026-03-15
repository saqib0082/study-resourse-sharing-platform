<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php"); exit();
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudyVault — Sign In</title>
    <?php include_once 'config/theme.php'; ?>
    <style>
        body { display:flex; align-items:center; justify-content:center; min-height:100vh; padding:24px; }

        .auth-shell {
            display:grid; grid-template-columns:1fr 1fr;
            width:100%; max-width:960px; min-height:580px;
            background:var(--card);
            border:1px solid var(--border);
            border-radius:var(--radius-xl);
            box-shadow:var(--shadow-xl);
            overflow:hidden;
            animation:fadeUp .6s cubic-bezier(.22,1,.36,1) both;
        }

        /* Left decorative panel */
        .auth-deco {
            background:linear-gradient(145deg, #5b4fcf 0%, #0891b2 100%);
            padding:52px 46px;
            display:flex; flex-direction:column; justify-content:space-between;
            position:relative; overflow:hidden;
        }
        [data-theme="dark"] .auth-deco {
            background:linear-gradient(145deg, #3d3294 0%, #065f7a 100%);
        }
        .auth-deco::before {
            content:'';
            position:absolute; inset:0;
            background:url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none'%3E%3Cg fill='%23ffffff' fill-opacity='0.06'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }
        .auth-deco::after {
            content:'';
            position:absolute;
            width:320px; height:320px;
            background:rgba(255,255,255,.08);
            border-radius:50%;
            bottom:-100px; right:-80px;
        }
        .deco-logo { display:flex; align-items:center; gap:10px; position:relative; z-index:1; }
        .deco-logo-mark { width:38px;height:38px; background:rgba(255,255,255,.2); border-radius:10px; display:flex;align-items:center;justify-content:center; font-size:18px; }
        .deco-logo-name { font-family:'Bricolage Grotesque',sans-serif; font-weight:800; font-size:20px; color:#fff; }

        .deco-center { position:relative; z-index:1; }
        .deco-headline { font-family:'Bricolage Grotesque',sans-serif; font-weight:800; font-size:32px; color:#fff; line-height:1.2; margin-bottom:12px; }
        .deco-sub { font-size:14px; color:rgba(255,255,255,.7); line-height:1.7; }

        .deco-features { position:relative; z-index:1; display:flex; flex-direction:column; gap:10px; }
        .deco-feat { display:flex; align-items:center; gap:11px; background:rgba(255,255,255,.12); border-radius:10px; padding:11px 14px; font-size:13px; color:rgba(255,255,255,.85); }
        .deco-feat-dot { width:7px;height:7px; background:#fff; border-radius:50%; opacity:.8; flex-shrink:0; }

        /* Right form panel */
        .auth-form { padding:52px 48px; display:flex; flex-direction:column; justify-content:center; }
        .auth-heading { font-family:'Bricolage Grotesque',sans-serif; font-weight:800; font-size:26px; color:var(--text); margin-bottom:5px; }
        .auth-subheading { font-size:13.5px; color:var(--text-3); margin-bottom:30px; }
        .auth-subheading a { color:var(--accent); font-weight:700; }
        .auth-subheading a:hover { text-decoration:underline; }

        .submit-btn {
            width:100%; padding:13px; margin-top:6px;
            background:linear-gradient(135deg,var(--accent),var(--accent-h));
            border:none; border-radius:var(--radius); color:#fff;
            font-family:'Bricolage Grotesque',sans-serif; font-weight:700; font-size:15px;
            cursor:pointer; box-shadow:0 4px 18px rgba(91,79,207,.32);
            transition:all .2s; display:flex; align-items:center; justify-content:center; gap:8px;
        }
        .submit-btn:hover { box-shadow:0 8px 24px rgba(91,79,207,.45); transform:translateY(-1px); }
        .submit-btn:disabled { opacity:.65; cursor:not-allowed; transform:none; }

        .divider { display:flex; align-items:center; gap:12px; margin:18px 0; color:var(--muted); font-size:12px; }
        .divider::before, .divider::after { content:''; flex:1; height:1px; background:var(--border); }

        .admin-link-row { text-align:center; font-size:13px; color:var(--text-3); }
        .admin-link-row a { color:var(--accent-2); font-weight:700; }

        .top-right { position:absolute; top:20px; right:20px; }

        @media(max-width:700px){
            .auth-shell{grid-template-columns:1fr; max-width:440px;}
            .auth-deco{display:none;}
            .auth-form{padding:40px 28px;}
        }
    </style>
</head>
<body>
    <div class="top-right"><button class="theme-toggle" onclick="toggleTheme()">🌙</button></div>

    <div class="auth-shell">
        <!-- Deco Panel -->
        <div class="auth-deco">
            <div class="deco-logo">
                <div class="deco-logo-mark">📚</div>
                <div class="deco-logo-name">StudyVault</div>
            </div>
            <div class="deco-center">
                <div class="deco-headline">Share Knowledge,<br>Grow Together</div>
                <div class="deco-sub">Your hub for notes, PDFs, slides, and study resources — organized and always accessible.</div>
            </div>
            <div class="deco-features">
                <div class="deco-feat"><span class="deco-feat-dot"></span> Upload & share notes instantly</div>
                <div class="deco-feat"><span class="deco-feat-dot"></span> Search by subject or keyword</div>
                <div class="deco-feat"><span class="deco-feat-dot"></span> Download resources for free</div>
                <div class="deco-feat"><span class="deco-feat-dot"></span> Organized by categories</div>
            </div>
        </div>

        <!-- Form -->
        <div class="auth-form">
            <div class="auth-heading">Welcome back 👋</div>
            <div class="auth-subheading">Sign in to access your study resources.</div>

            <div class="alert alert-error" id="errMsg" style="margin-bottom:18px"></div>
            <div class="alert alert-success" id="okMsg" style="margin-bottom:18px"></div>

            <div class="form-group">
                <label class="form-label">Email Address</label>
                <div class="input-wrap">
                    <span class="input-icon">✉️</span>
                    <input type="email" id="email" class="form-input" placeholder="you@example.com" autocomplete="username" />
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Password</label>
                <div class="input-wrap">
                    <span class="input-icon">🔒</span>
                    <input type="password" id="password" class="form-input" placeholder="Enter your password" autocomplete="current-password" />
                </div>
            </div>

            <button class="submit-btn" id="loginBtn" onclick="doLogin()">Sign In</button>

            <div class="divider">or</div>
            <div class="admin-link-row">Administrator? <a href="admin_login.php">Admin Login →</a></div>
        </div>
    </div>

<script>
function showMsg(type, text) {
    ['errMsg','okMsg'].forEach(id => { document.getElementById(id).classList.remove('show'); document.getElementById(id).textContent=''; });
    const el = document.getElementById(type==='error'?'errMsg':'okMsg');
    el.textContent = text; el.classList.add('show');
}

function doLogin() {
    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;
    const btn = document.getElementById('loginBtn');
    if (!email || !password) { showMsg('error','⚠️ Please fill in all fields.'); return; }

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner"></span>Signing in...';

    fetch('api/loginAPI.php', {
        method:'POST', headers:{'Content-Type':'application/json'},
        body:JSON.stringify({email, password})
    })
    .then(r=>r.json())
    .then(data=>{
        if (data.status==='success') {
            showMsg('success','✅ Welcome back! Redirecting...');
            setTimeout(()=>location.href='dashboard.php', 900);
        } else {
            showMsg('error','❌ '+(data.message||'Invalid email or password.'));
            btn.disabled=false; btn.innerHTML='Sign In';
        }
    })
    .catch(()=>{ showMsg('error','❌ Server error.'); btn.disabled=false; btn.innerHTML='Sign In'; });
}
document.addEventListener('keydown', e=>{ if(e.key==='Enter') doLogin(); });
</script>
</body>
</html>
