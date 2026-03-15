<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }
include_once 'config/db.php';
$conn = getDB();

// Auto-migration: add missing columns
$conn->query("ALTER TABLE notes ADD COLUMN IF NOT EXISTS file_size BIGINT DEFAULT 0");
$conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS last_login DATETIME DEFAULT NULL");
$conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS is_online TINYINT(1) NOT NULL DEFAULT 0");
$categories = $conn->query("SELECT * FROM categories ORDER BY name");
$userName = $_SESSION['user_name'];
$userRole = $_SESSION['user_role'];
$userId   = $_SESSION['user_id'];
$isAdmin  = ($userRole === 'admin');
$activePage = 'upload';

// User storage info
$myBytes = $conn->query("SELECT COALESCE(SUM(file_size),0) as b FROM notes WHERE uploaded_by=$userId")->fetch_assoc()['b'];
$LIMIT_USER = 1 * 1024 * 1024 * 1024;
$usedMB     = round($myBytes/1024/1024, 1);
$usedPct    = min(100, round($myBytes/$LIMIT_USER*100, 1));
$remainMB   = max(0, round(($LIMIT_USER-$myBytes)/1024/1024, 1));
$conn->close();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudyVault — Upload Notes</title>
    <?php include_once 'config/theme.php'; ?>
    <style>
        body { display:flex; }
        .page-wrap { display:flex;width:100%; }
        .page-body { flex:1;display:flex;flex-direction:column;margin-left:var(--sidebar-w); }
        .topbar { position:sticky;top:0;z-index:50;background:var(--surface);border-bottom:1px solid var(--border);padding:14px 34px;display:flex;align-items:center;gap:14px;box-shadow:var(--shadow-xs); }
        .topbar-title { font-family:'Bricolage Grotesque',sans-serif;font-weight:800;font-size:20px;flex:1;color:var(--text); }
        .page-main { padding:28px 34px; }
        .upload-layout { display:grid;grid-template-columns:1fr 320px;gap:24px; }

        /* Form Card */
        .form-card { background:var(--card);border:1px solid var(--border);border-radius:var(--radius-xl);padding:30px;box-shadow:var(--shadow-sm); }
        .form-section-title { font-family:'Bricolage Grotesque',sans-serif;font-weight:700;font-size:16px;color:var(--text);margin-bottom:20px;padding-bottom:12px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:8px; }

        select.form-input { padding-left:40px;cursor:pointer;appearance:none;background-image:url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");background-repeat:no-repeat;background-position:right 12px center;background-size:16px;padding-right:36px; }
        textarea.form-input { padding:12px 14px;resize:vertical;min-height:80px; }

        /* Drop Zone */
        .drop-zone { border:2px dashed var(--border);border-radius:var(--radius-lg);padding:36px 24px;text-align:center;cursor:pointer;transition:all .2s;position:relative;background:var(--bg); }
        .drop-zone:hover,.drop-zone.dragover { border-color:var(--accent);background:rgba(91,79,207,.04); }
        .drop-zone input[type="file"] { position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%; }
        .dz-ico { font-size:36px;margin-bottom:10px; }
        .dz-title { font-family:'Bricolage Grotesque',sans-serif;font-weight:700;font-size:15px;color:var(--text);margin-bottom:5px; }
        .dz-sub { font-size:13px;color:var(--muted);margin-bottom:12px; }
        .dz-types { display:flex;justify-content:center;gap:7px;flex-wrap:wrap; }
        .dz-type { padding:3px 10px;background:rgba(91,79,207,.08);border:1px solid rgba(91,79,207,.18);border-radius:20px;font-size:12px;color:var(--accent);font-weight:700; }
        .file-info { display:none; }
        .file-info.show { display:flex;align-items:center;gap:12px;background:var(--success-bg);border:1.5px solid rgba(5,150,105,.25);border-radius:var(--radius);padding:12px 16px;margin-top:12px; }
        .fi-ico { font-size:22px; }
        .fi-name { font-size:13.5px;font-weight:700;color:var(--success); }
        .fi-size { font-size:12px;color:var(--muted); }

        /* Upload btn */
        .upload-btn {
            width:100%;padding:14px;background:linear-gradient(135deg,var(--accent),var(--accent-h));
            border:none;border-radius:var(--radius);color:#fff;
            font-family:'Bricolage Grotesque',sans-serif;font-weight:700;font-size:16px;
            cursor:pointer;box-shadow:0 4px 18px rgba(91,79,207,.3);
            transition:all .2s;display:flex;align-items:center;justify-content:center;gap:8px;margin-top:8px;
        }
        .upload-btn:hover { box-shadow:0 8px 24px rgba(91,79,207,.45);transform:translateY(-1px); }
        .upload-btn:disabled { opacity:.65;cursor:not-allowed;transform:none; }

        /* Progress */
        .progress-wrap { margin-top:14px;display:none; }
        .progress-wrap.show { display:block; }
        .prog-bar { height:6px;background:var(--border);border-radius:6px;overflow:hidden;margin-bottom:5px; }
        .prog-fill { height:100%;background:linear-gradient(90deg,var(--accent),var(--accent-2));border-radius:6px;width:0%;transition:width .3s; }
        .prog-lbl { font-size:12px;color:var(--muted);text-align:center; }

        /* Side Cards */
        .side-card { background:var(--card);border:1px solid var(--border);border-radius:var(--radius-xl);padding:24px;box-shadow:var(--shadow-sm);margin-bottom:18px; }
        .side-title { font-family:'Bricolage Grotesque',sans-serif;font-weight:700;font-size:15px;color:var(--text);margin-bottom:16px;display:flex;align-items:center;gap:7px; }
        .tip-item { display:flex;gap:10px;margin-bottom:12px; }
        .tip-ico { font-size:15px;flex-shrink:0;margin-top:1px; }
        .tip-txt { font-size:13px;color:var(--text-3);line-height:1.55; }
        .tip-txt strong { color:var(--text);display:block;margin-bottom:1px; }
        .rule-item { font-size:12.5px;color:var(--text-3);margin-bottom:7px;display:flex;align-items:flex-start;gap:7px; }
        .rule-item::before { content:'✓';color:var(--accent-3);font-weight:800;flex-shrink:0; }

        /* Storage card */
        .storage-card { background:linear-gradient(135deg,rgba(91,79,207,.06),rgba(8,145,178,.04));border:1.5px solid rgba(91,79,207,.15); }
        .storage-num { font-family:'Bricolage Grotesque',sans-serif;font-weight:800;font-size:22px;color:var(--accent);margin-bottom:3px; }
        .stor-bar-bg { height:8px;background:var(--border);border-radius:8px;overflow:hidden;margin:10px 0 6px; }
        .stor-bar-fill { height:100%;border-radius:8px;transition:width .8s;background:linear-gradient(90deg,var(--accent),var(--accent-2)); }

        @media(max-width:900px){.upload-layout{grid-template-columns:1fr}}
        @media(max-width:768px){.page-body{margin-left:0}}
    </style>
</head>
<body>
<div class="page-wrap">
    <?php include 'config/sidebar.php'; ?>
    <div class="page-body">
        <div class="topbar">
            <div style="flex:1"><div class="topbar-title">⬆️ Upload Notes</div></div>
            <button class="theme-toggle" onclick="toggleTheme()">🌙</button>
        </div>
        <div class="page-main">
            <div class="upload-layout">
                <!-- Form -->
                <div class="form-card anim-up">
                    <div class="form-section-title">📝 Note Details</div>
                    <div class="alert alert-error" id="errMsg" style="margin-bottom:18px"></div>
                    <div class="alert alert-success" id="okMsg" style="margin-bottom:18px"></div>

                    <div class="form-group">
                        <label class="form-label">Note Title *</label>
                        <div class="input-wrap"><span class="input-icon">📌</span><input type="text" id="title" class="form-input" placeholder="e.g. Chapter 5 — Linked Lists" /></div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Subject *</label>
                        <div class="input-wrap"><span class="input-icon">📖</span><input type="text" id="subject" class="form-input" placeholder="e.g. Data Structures, Web Tech" /></div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Category</label>
                        <div class="input-wrap">
                            <span class="input-icon">🏷️</span>
                            <select id="category" class="form-input">
                                <option value="">— Select a category —</option>
                                <?php while($cat=$categories->fetch_assoc()): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Upload File * <span style="font-size:11px;color:var(--muted)">(max 100 MB)</span></label>
                        <div class="drop-zone" id="dropZone">
                            <input type="file" id="fileInput" accept=".pdf,.doc,.docx,.ppt,.pptx,.txt,.png,.jpg,.jpeg" onchange="onFileSelect(this)" />
                            <div class="dz-ico">📁</div>
                            <div class="dz-title">Drop your file here</div>
                            <div class="dz-sub">or click to browse</div>
                            <div class="dz-types">
                                <span class="dz-type">PDF</span><span class="dz-type">DOC</span>
                                <span class="dz-type">PPT</span><span class="dz-type">TXT</span><span class="dz-type">IMG</span>
                            </div>
                        </div>
                        <div class="file-info" id="fileInfo">
                            <span class="fi-ico">📄</span>
                            <div style="flex:1">
                                <div class="fi-name" id="fiName">—</div>
                                <div class="fi-size" id="fiSize">—</div>
                            </div>
                            <span style="cursor:pointer;color:var(--muted);font-size:18px" onclick="clearFile()">✕</span>
                        </div>
                    </div>

                    <div class="progress-wrap" id="progWrap">
                        <div class="prog-bar"><div class="prog-fill" id="progFill"></div></div>
                        <div class="prog-lbl" id="progLbl">Uploading...</div>
                    </div>

                    <button class="upload-btn" id="uploadBtn" onclick="doUpload()">⬆️ Upload Notes</button>
                </div>

                <!-- Sidebar -->
                <div>
                    <!-- Storage Usage -->
                    <div class="side-card storage-card anim-up anim-up-1">
                        <div class="side-title">💾 Your Storage</div>
                        <div class="storage-num"><?= $usedMB ?> MB <span style="font-size:15px;font-weight:500;color:var(--text-3)">/ 1 GB used</span></div>
                        <div class="stor-bar-bg"><div class="stor-bar-fill" style="width:<?= $usedPct ?>%;<?= $usedPct>85?'background:var(--danger)':'' ?>"></div></div>
                        <div style="font-size:12px;color:var(--muted)"><?= $remainMB ?> MB remaining · <?= $usedPct ?>% used</div>
                    </div>

                    <!-- Tips -->
                    <div class="side-card anim-up anim-up-2">
                        <div class="side-title">💡 Upload Tips</div>
                        <div class="tip-item"><span class="tip-ico">📄</span><div class="tip-txt"><strong>Use PDF format</strong>Best compatibility across devices.</div></div>
                        <div class="tip-item"><span class="tip-ico">🏷️</span><div class="tip-txt"><strong>Clear title</strong>Helps others find your notes easily.</div></div>
                        <div class="tip-item"><span class="tip-ico">📚</span><div class="tip-txt"><strong>Right category</strong>Keeps the library organized.</div></div>
                        <div class="tip-item"><span class="tip-ico">⚡</span><div class="tip-txt"><strong>Max 100 MB per file</strong>Compress large files before uploading.</div></div>
                    </div>

                    <!-- Rules -->
                    <div class="side-card anim-up anim-up-3" style="background:rgba(5,150,105,.04);border-color:rgba(5,150,105,.15)">
                        <div class="side-title" style="color:var(--accent-3)">📋 Upload Rules</div>
                        <div class="rule-item">Only academic content allowed</div>
                        <div class="rule-item">No copyrighted or inappropriate material</div>
                        <div class="rule-item">Allowed: PDF, DOC, PPT, TXT, IMG</div>
                        <div class="rule-item">Max file size: 100 MB</div>
                        <div class="rule-item">Max storage per user: 1 GB</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="toast" id="sv-toast"></div>
<script>
const dz = document.getElementById('dropZone');
dz.addEventListener('dragover', e=>{e.preventDefault();dz.classList.add('dragover');});
dz.addEventListener('dragleave', ()=>dz.classList.remove('dragover'));
dz.addEventListener('drop', e=>{
    e.preventDefault();dz.classList.remove('dragover');
    const f=e.dataTransfer.files[0];
    if(f){document.getElementById('fileInput').files=e.dataTransfer.files;showFileInfo(f);}
});
function onFileSelect(inp){if(inp.files[0])showFileInfo(inp.files[0]);}
function showFileInfo(f){
    document.getElementById('fiName').textContent=f.name;
    document.getElementById('fiSize').textContent=(f.size/1024/1024).toFixed(2)+' MB';
    document.getElementById('fileInfo').classList.add('show');
}
function clearFile(){document.getElementById('fileInput').value='';document.getElementById('fileInfo').classList.remove('show');}
function showMsg(type,txt){
    ['errMsg','okMsg'].forEach(id=>{document.getElementById(id).classList.remove('show');document.getElementById(id).textContent='';});
    const el=document.getElementById(type==='error'?'errMsg':'okMsg');el.textContent=txt;el.classList.add('show');el.scrollIntoView({behavior:'smooth',block:'nearest'});
}
function doUpload(){
    const title=document.getElementById('title').value.trim();
    const subject=document.getElementById('subject').value.trim();
    const category=document.getElementById('category').value;
    const file=document.getElementById('fileInput').files[0];
    const btn=document.getElementById('uploadBtn');
    if(!title){showMsg('error','⚠️ Please enter a title.');return;}
    if(!subject){showMsg('error','⚠️ Please enter a subject.');return;}
    if(!file){showMsg('error','⚠️ Please select a file.');return;}
    if(file.size>100*1024*1024){showMsg('error','⚠️ File too large. Max size is 100 MB.');return;}
    const fd=new FormData();
    fd.append('title',title);fd.append('subject',subject);fd.append('category_id',category);fd.append('file',file);
    btn.disabled=true;btn.innerHTML='<span class="spinner"></span>Uploading...';
    document.getElementById('progWrap').classList.add('show');
    let prog=0;
    const iv=setInterval(()=>{prog+=Math.random()*18;if(prog>90){prog=90;clearInterval(iv);}document.getElementById('progFill').style.width=prog+'%';document.getElementById('progLbl').textContent='Uploading... '+Math.round(prog)+'%';},220);
    fetch('api/uploadAPI.php',{method:'POST',body:fd})
    .then(r=>r.json()).then(data=>{
        clearInterval(iv);document.getElementById('progFill').style.width='100%';document.getElementById('progLbl').textContent='Done!';
        if(data.status==='success'){
            showMsg('success','✅ Notes uploaded successfully!');
            document.getElementById('title').value='';document.getElementById('subject').value='';document.getElementById('category').value='';clearFile();
            setTimeout(()=>location.href='notes.php',1500);
        } else {showMsg('error','❌ '+(data.message||'Upload failed.'));}
        btn.disabled=false;btn.innerHTML='⬆️ Upload Notes';
    })
    .catch(()=>{clearInterval(iv);showMsg('error','❌ Server error.');btn.disabled=false;btn.innerHTML='⬆️ Upload Notes';document.getElementById('progWrap').classList.remove('show');});
}
</script>
</body>
</html>
