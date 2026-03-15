<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header($_SESSION['user_role']==='admin' ? "Location: admin.php" : "Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudyVault — Admin Login</title>
    <?php include_once 'config/theme.php'; ?>
    <style>
        body { display:flex;align-items:center;justify-content:center;min-height:100vh;padding:24px; }
        .auth-shell {
            display:grid;grid-template-columns:1fr 1fr;
            width:100%;max-width:960px;min-height:560px;
            background:var(--card);border:1px solid var(--border);
            border-radius:var(--radius-xl);box-shadow:var(--shadow-xl);overflow:hidden;
            animation:fadeUp .6s cubic-bezier(.22,1,.36,1) both;
        }
        .auth-deco {
            background:linear-gradient(145deg,#1e3a5f 0%,#0891b2 100%);
            padding:52px 46px;display:flex;flex-direction:column;justify-content:space-between;
            position:relative;overflow:hidden;
        }
        [data-theme="dark"] .auth-deco { background:linear-gradient(145deg,#0f2035 0%,#065f7a 100%); }
        .auth-deco::before { content:'';position:absolute;inset:0;background:url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E"); }
        .auth-deco::after { content:'';position:absolute;width:340px;height:340px;background:rgba(255,255,255,.06);border-radius:50%;bottom:-120px;right:-100px; }
        .deco-logo { display:flex;align-items:center;gap:10px;position:relative;z-index:1; }
        .deco-logo-mark { width:38px;height:38px;background:rgba(255,255,255,.18);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px; }
        .deco-logo-name { font-family:'Bricolage Grotesque',sans-serif;font-weight:800;font-size:20px;color:#fff; }
        .deco-badge { display:inline-flex;align-items:center;gap:7px;margin-top:10px;background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.2);border-radius:8px;padding:6px 13px;width:fit-content; }
        .deco-badge-dot { width:7px;height:7px;background:#34d399;border-radius:50%;animation:pulse 2s infinite; }
        .deco-badge-text { font-size:11px;font-weight:800;color:rgba(255,255,255,.9);letter-spacing:.5px; }
        .deco-headline { font-family:'Bricolage Grotesque',sans-serif;font-weight:800;font-size:28px;color:#fff;line-height:1.25;margin-bottom:10px; }
        .deco-sub { font-size:14px;color:rgba(255,255,255,.7);line-height:1.7; }
        .deco-perms { display:flex;flex-direction:column;gap:9px;position:relative;z-index:1; }
        .deco-perm { display:flex;align-items:center;gap:10px;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.15);border-radius:9px;padding:10px 13px;font-size:13px;color:rgba(255,255,255,.85); }
        .deco-perm-ico { font-size:14px;flex-shrink:0; }

        .auth-form { padding:52px 48px;display:flex;flex-direction:column;justify-content:center; }
        .form-header { display:flex;align-items:center;gap:11px;margin-bottom:7px; }
        .form-ico { width:40px;height:40px;background:linear-gradient(135deg,#0891b2,#0369a1);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;box-shadow:0 4px 12px rgba(8,145,178,.3); }
        .auth-heading { font-family:'Bricolage Grotesque',sans-serif;font-weight:800;font-size:25px;color:var(--text); }
        .auth-subheading { font-size:13.5px;color:var(--text-3);margin-bottom:28px;margin-top:5px;line-height:1.6; }
        .auth-subheading a { color:var(--accent-2);font-weight:700; }
        .auth-subheading a:hover { text-decoration:underline; }

        .submit-btn {
            width:100%;padding:13px;margin-top:6px;
            background:linear-gradient(135deg,#0891b2,#0369a1);
            border:none;border-radius:var(--radius);color:#fff;
            font-family:'Bricolage Grotesque',sans-serif;font-weight:700;font-size:15px;
            cursor:pointer;box-shadow:0 4px 18px rgba(8,145,178,.3);
            transition:all .2s;display:flex;align-items:center;justify-content:center;gap:8px;
        }
        .submit-btn:hover { box-shadow:0 8px 24px rgba(8,145,178,.45);transform:translateY(-1px); }
        .submit-btn:disabled { opacity:.65;cursor:not-allowed;transform:none; }
        .back-row { text-align:center;margin-top:18px;font-size:13px;color:var(--text-3); }
        .back-row a { color:var(--accent);font-weight:700; }
        .top-right { position:absolute;top:20px;right:20px; }
        @media(max-width:700px){.auth-shell{grid-template-columns:1fr;max-width:440px;}.auth-deco{display:none;}.auth-form{padding:40px 28px;}}
    </style>
</head>
<body>
    <div class="top-right"><button class="theme-toggle" onclick="toggleTheme()">🌙</button></div>
    <div class="auth-shell">
        <div class="auth-deco">
            <div>
                <div class="deco-logo"><div class="deco-logo-mark">📚</div><div class="deco-logo-name">StudyVault</div></div>
                <div class="deco-badge"><div class="deco-badge-dot"></div><div class="deco-badge-text">ADMIN PORTAL</div></div>
            </div>
            <div style="position:relative;z-index:1">
                <div class="deco-headline">Admin<br>Control Center</div>
                <div class="deco-sub">Secure access for administrators. Manage users, content, and platform settings.</div>
            </div>
            <div class="deco-perms">
                <div class="deco-perm"><span class="deco-perm-ico">👥</span> Manage users & assign roles</div>
                <div class="deco-perm"><span class="deco-perm-ico">🗑️</span> Delete or moderate any content</div>
                <div class="deco-perm"><span class="deco-perm-ico">📊</span> View platform statistics</div>
                <div class="deco-perm"><span class="deco-perm-ico">⬆️</span> Promote students to admin</div>
            </div>
        </div>
        <div class="auth-form">
            <div class="form-header">
                <div class="form-ico">🛡️</div>
                <div class="auth-heading">Admin Login</div>
            </div>
            <div class="auth-subheading">Restricted to administrators only.<br>Regular user? <a href="login.php">Student login →</a></div>

            <div class="alert alert-error"   id="errMsg" style="margin-bottom:18px"></div>
            <div class="alert alert-success" id="okMsg"  style="margin-bottom:18px"></div>
            <div class="alert alert-warning" id="warnMsg" style="margin-bottom:18px"></div>

            <div class="form-group">
                <label class="form-label">Admin Email</label>
                <div class="input-wrap"><span class="input-icon">✉️</span><input type="email" id="email" class="form-input" placeholder="admin@studyvault.com" autocomplete="username" /></div>
            </div>
            <div class="form-group">
                <label class="form-label">Password</label>
                <div class="input-wrap"><span class="input-icon">🔒</span><input type="password" id="pw" class="form-input" placeholder="Enter admin password" autocomplete="current-password" /></div>
            </div>
            <button class="submit-btn" id="loginBtn" onclick="doAdminLogin()">🛡️ Login as Admin</button>
            <div class="back-row"><a href="login.php">← Back to student login</a></div>
        </div>
    </div>
<script>
function showMsg(type,text){
    ['errMsg','okMsg','warnMsg'].forEach(id=>{document.getElementById(id).classList.remove('show');document.getElementById(id).textContent='';});
    const map={error:'errMsg',success:'okMsg',warning:'warnMsg'};
    const el=document.getElementById(map[type]);if(el){el.textContent=text;el.classList.add('show');}
}
function doAdminLogin(){
    const email=document.getElementById('email').value.trim();
    const password=document.getElementById('pw').value;
    const btn=document.getElementById('loginBtn');
    if(!email||!password){showMsg('error','⚠️ Please fill in all fields.');return;}
    btn.disabled=true;btn.innerHTML='<span class="spinner"></span>Verifying credentials...';
    fetch('api/loginAPI.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({email,password})})
    .then(r=>r.json())
    .then(data=>{
        if(data.status==='success'){
            if(data.user&&data.user.role==='admin'){
                showMsg('success','✅ Admin verified! Loading control panel...');
                setTimeout(()=>location.href='admin.php',900);
            } else {
                // Not admin — clear the session then show error
                fetch('api/logoutAPI.php',{method:'GET',credentials:'same-origin'}).finally(()=>{
                    showMsg('warning','⛔ Access denied. This account does not have admin privileges.');
                    btn.disabled=false;btn.innerHTML='🛡️ Login as Admin';
                });
            }
        } else {
            showMsg('error','❌ '+(data.message||'Invalid credentials.'));
            btn.disabled=false;btn.innerHTML='🛡️ Login as Admin';
        }
    })
    .catch(()=>{showMsg('error','❌ Server error. Please try again.');btn.disabled=false;btn.innerHTML='🛡️ Login as Admin';});
}
document.addEventListener('keydown',e=>{if(e.key==='Enter')doAdminLogin();});
</script>
</body>
</html>
