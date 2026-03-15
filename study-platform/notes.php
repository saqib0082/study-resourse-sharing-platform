<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }
include_once 'config/db.php';
$conn = getDB();

// Auto-migration: add missing columns
$conn->query("ALTER TABLE notes ADD COLUMN IF NOT EXISTS file_size BIGINT DEFAULT 0");
$conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS last_login DATETIME DEFAULT NULL");
$conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS is_online TINYINT(1) NOT NULL DEFAULT 0");

$userName = $_SESSION['user_name'];
$userRole = $_SESSION['user_role'];
$userId   = $_SESSION['user_id'];
$isAdmin  = ($userRole === 'admin');

$search   = isset($_GET['q'])       ? trim($_GET['q'])    : '';
$catFilter= isset($_GET['cat'])     ? intval($_GET['cat']): 0;
$myFilter = isset($_GET['filter'])  && $_GET['filter']==='mine';
$activePage = $myFilter ? 'mine' : (isset($_GET['search']) ? 'search' : 'notes');

$categories = $conn->query("SELECT * FROM categories ORDER BY name");

$where='WHERE 1=1'; $params=[]; $types='';
if(!empty($search)){$where.=" AND (n.title LIKE ? OR n.subject LIKE ?)";$like="%$search%";$params[]=$like;$params[]=$like;$types.='ss';}
if($catFilter>0){$where.=" AND n.category_id=?";$params[]=$catFilter;$types.='i';}
if($myFilter){$where.=" AND n.uploaded_by=?";$params[]=$userId;$types.='i';}

$sql="SELECT n.id,n.title,n.subject,n.file_name,n.file_size,n.downloads,n.upload_date,u.name as uploader,u.id as uid,c.name as category FROM notes n JOIN users u ON n.uploaded_by=u.id LEFT JOIN categories c ON n.category_id=c.id $where ORDER BY n.upload_date DESC";
$stmt=$conn->prepare($sql);
if(!empty($params)) $stmt->bind_param($types,...$params);
$stmt->execute();
$notes=$stmt->get_result();
$total=$notes->num_rows;
$conn->close();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudyVault — Notes Library</title>
    <?php include_once 'config/theme.php'; ?>
    <style>
        body { display:flex; }
        .page-wrap { display:flex;width:100%; }
        .page-body { flex:1;display:flex;flex-direction:column;margin-left:var(--sidebar-w); }
        .topbar { position:sticky;top:0;z-index:50;background:var(--surface);border-bottom:1px solid var(--border);padding:14px 34px;display:flex;align-items:center;gap:14px;box-shadow:var(--shadow-xs); }
        .topbar-title { font-family:'Bricolage Grotesque',sans-serif;font-weight:800;font-size:20px;flex:1;color:var(--text); }
        .page-main { padding:26px 34px; }

        /* Filter Bar */
        .filter-bar { background:var(--card);border:1px solid var(--border);border-radius:var(--radius-lg);padding:16px 20px;display:flex;gap:12px;align-items:center;margin-bottom:20px;flex-wrap:wrap;box-shadow:var(--shadow-sm); }
        .search-wrap { flex:1;min-width:200px;position:relative; }
        .search-ico { position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--muted);font-size:14px;pointer-events:none; }
        .search-inp { width:100%;background:var(--input-bg);border:1.5px solid var(--border);border-radius:var(--radius);color:var(--text);font-family:'Nunito',sans-serif;font-size:14px;padding:10px 14px 10px 36px;outline:none;transition:all .2s; }
        .search-inp:focus { border-color:var(--accent);box-shadow:var(--accent-glow); }
        .search-inp::placeholder { color:var(--muted); }
        .filter-sel { background:var(--input-bg);border:1.5px solid var(--border);border-radius:var(--radius);color:var(--text);font-family:'Nunito',sans-serif;font-size:14px;padding:10px 14px;outline:none;cursor:pointer;transition:all .2s;min-width:160px; }
        .filter-sel:focus { border-color:var(--accent); }
        .filter-submit { padding:10px 20px;background:var(--accent);border:none;border-radius:var(--radius);color:#fff;font-family:'Nunito',sans-serif;font-weight:700;font-size:14px;cursor:pointer;transition:all .18s;display:flex;align-items:center;gap:7px; }
        .filter-submit:hover { background:var(--accent-h); }
        .clear-link { padding:10px 16px;background:transparent;border:1.5px solid var(--border);border-radius:var(--radius);color:var(--text-3);font-size:13px;cursor:pointer;text-decoration:none;transition:all .18s;font-family:'Nunito',sans-serif;font-weight:600; }
        .clear-link:hover { border-color:var(--danger);color:var(--danger); }

        /* Tabs */
        .filter-tabs { display:flex;gap:8px;margin-bottom:20px;align-items:center;flex-wrap:wrap; }
        .ftab { padding:8px 18px;border-radius:20px;font-size:13px;font-weight:700;text-decoration:none;color:var(--text-3);border:1.5px solid var(--border);background:var(--card);transition:all .18s; }
        .ftab:hover { color:var(--text);border-color:var(--border2); }
        .ftab.active { background:rgba(91,79,207,.08);color:var(--accent);border-color:rgba(91,79,207,.25); }
        .results-count { margin-left:auto;font-size:12.5px;color:var(--muted);font-weight:600; }

        /* Notes Grid */
        .notes-grid { display:grid;grid-template-columns:repeat(auto-fill,minmax(290px,1fr));gap:18px; }
        .note-card {
            background:var(--card);border:1px solid var(--border);border-radius:var(--radius-lg);
            padding:20px;display:flex;flex-direction:column;gap:12px;
            box-shadow:var(--shadow-sm);transition:all .2s;
        }
        .note-card:hover { transform:translateY(-3px);box-shadow:var(--shadow);border-color:var(--border2); }
        .nc-top { display:flex;justify-content:space-between;align-items:flex-start;gap:10px; }
        .nc-ico { width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0; }
        .nc-badges { display:flex;gap:5px;flex-wrap:wrap; }
        .nc-title { font-family:'Bricolage Grotesque',sans-serif;font-weight:700;font-size:14.5px;color:var(--text);line-height:1.4; }
        .nc-meta { display:flex;gap:12px;font-size:12px;color:var(--text-3);flex-wrap:wrap; }
        .nc-meta span { display:flex;align-items:center;gap:3px; }
        .nc-footer { display:flex;justify-content:space-between;align-items:center;padding-top:10px;border-top:1px solid var(--border);margin-top:auto; }
        .nc-dl { font-size:12px;color:var(--muted); }
        .nc-btns { display:flex;gap:7px; }
        .dl-btn { padding:8px 15px;background:rgba(91,79,207,.08);border:1px solid rgba(91,79,207,.18);border-radius:9px;color:var(--accent);font-size:13px;font-weight:700;text-decoration:none;transition:all .2s; }
        .dl-btn:hover { background:var(--accent);color:#fff;border-color:var(--accent);transform:scale(1.04); }
        .del-btn { padding:7px 11px;background:var(--danger-bg);border:1px solid rgba(220,38,38,.2);border-radius:9px;color:var(--danger);font-size:12px;font-weight:700;cursor:pointer;transition:all .2s; }
        .del-btn:hover { background:var(--danger);color:#fff; }

        /* Empty State */
        .empty-state { text-align:center;padding:80px 40px;color:var(--muted); }
        .empty-ico { font-size:52px;margin-bottom:14px; }
        .empty-title { font-family:'Bricolage Grotesque',sans-serif;font-weight:800;font-size:20px;color:var(--text);margin-bottom:8px; }
        .empty-sub { font-size:14px;margin-bottom:24px; }

        @media(max-width:768px){.page-body{margin-left:0}.notes-grid{grid-template-columns:1fr}}

        /* Preview Button */
        .prev-btn { padding:8px 15px;background:rgba(5,150,105,.08);border:1px solid rgba(5,150,105,.22);border-radius:9px;color:#059669;font-size:13px;font-weight:700;cursor:pointer;text-decoration:none;transition:all .2s; }
        .prev-btn:hover { background:#059669;color:#fff;border-color:#059669;transform:scale(1.04); }

        /* Preview Modal */
        .preview-overlay { display:none;position:fixed;inset:0;background:rgba(0,0,0,.65);z-index:1000;align-items:center;justify-content:center;padding:20px; }
        .preview-overlay.open { display:flex; }
        .preview-modal {
            background:var(--card);border:1px solid var(--border);border-radius:var(--radius-lg);
            width:100%;max-width:900px;max-height:90vh;display:flex;flex-direction:column;
            box-shadow:0 25px 60px rgba(0,0,0,.35);animation:modalIn .25s ease;
        }
        @keyframes modalIn { from{opacity:0;transform:scale(.94) translateY(12px)} to{opacity:1;transform:scale(1) translateY(0)} }
        .pm-header { display:flex;align-items:center;gap:12px;padding:16px 20px;border-bottom:1px solid var(--border); }
        .pm-title { font-family:'Bricolage Grotesque',sans-serif;font-weight:800;font-size:16px;color:var(--text);flex:1;white-space:nowrap;overflow:hidden;text-overflow:ellipsis; }
        .pm-close { width:32px;height:32px;border-radius:8px;border:1px solid var(--border);background:var(--input-bg);color:var(--text-3);font-size:16px;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all .18s; }
        .pm-close:hover { background:var(--danger);color:#fff;border-color:var(--danger); }
        .pm-body { flex:1;overflow:auto;padding:0;min-height:300px;position:relative; }
        .pm-loading { display:flex;flex-direction:column;align-items:center;justify-content:center;height:300px;gap:12px;color:var(--muted);font-size:14px; }
        .pm-spinner { width:36px;height:36px;border:3px solid var(--border);border-top-color:var(--accent);border-radius:50%;animation:spin .7s linear infinite; }
        @keyframes spin { to{transform:rotate(360deg)} }
        .pm-iframe { width:100%;height:70vh;border:none;display:block; }
        .pm-img { max-width:100%;max-height:70vh;display:block;margin:auto;padding:16px;object-fit:contain; }
        .pm-txt { padding:20px 24px;white-space:pre-wrap;font-family:'Courier New',monospace;font-size:13px;color:var(--text);line-height:1.7;max-height:70vh;overflow:auto; }
        .pm-nopreview { display:flex;flex-direction:column;align-items:center;justify-content:center;height:280px;gap:10px;color:var(--muted); }
        .pm-nopreview span:first-child { font-size:48px; }
        .pm-nopreview strong { font-size:15px;color:var(--text); }
        .pm-footer { padding:12px 20px;border-top:1px solid var(--border);display:flex;justify-content:flex-end;gap:10px; }
        /* PDF viewer */
        .pm-pdf-wrap { overflow-y:auto;max-height:70vh;background:#525659;padding:12px;display:flex;flex-direction:column;align-items:center;gap:8px; }
        .pm-pdf-wrap canvas { max-width:100%;box-shadow:0 2px 12px rgba(0,0,0,.4);display:block; }
        .pm-pdf-nav { display:flex;align-items:center;gap:10px;padding:10px 16px;border-top:1px solid var(--border);background:var(--card); }
        .pm-pdf-nav button { padding:6px 14px;border-radius:7px;border:1px solid var(--border);background:var(--input-bg);color:var(--text);font-size:13px;font-weight:700;cursor:pointer;transition:all .18s; }
        .pm-pdf-nav button:hover:not(:disabled) { background:var(--accent);color:#fff;border-color:var(--accent); }
        .pm-pdf-nav button:disabled { opacity:.4;cursor:not-allowed; }
        .pm-pdf-nav span { font-size:13px;color:var(--text-3);font-weight:600; }
        /* Word/DOCX viewer */
        .pm-word-wrap { overflow-y:auto;max-height:70vh;padding:32px 40px;background:#fff;color:#111;font-family:Georgia,serif;font-size:15px;line-height:1.8; }
        .pm-word-wrap h1,.pm-word-wrap h2,.pm-word-wrap h3 { margin:16px 0 8px;font-family:Arial,sans-serif; }
        .pm-word-wrap p { margin:0 0 10px; }
        .pm-word-wrap table { border-collapse:collapse;width:100%;margin:12px 0; }
        .pm-word-wrap td,.pm-word-wrap th { border:1px solid #ccc;padding:6px 10px; }
    </style>
</head>
<body>
<div class="page-wrap">
    <?php include 'config/sidebar.php'; ?>
    <div class="page-body">
        <div class="topbar">
            <div style="flex:1"><div class="topbar-title">📚 Notes Library</div></div>
            <a href="upload.php" class="btn btn-primary" style="font-size:13px;padding:9px 18px">⬆️ Upload Notes</a>
            <button class="theme-toggle" onclick="toggleTheme()">🌙</button>
        </div>
        <div class="page-main">
            <!-- Filter Bar -->
            <form method="GET" action="notes.php">
                <div class="filter-bar">
                    <div class="search-wrap">
                        <span class="search-ico">🔍</span>
                        <input type="text" name="q" class="search-inp" placeholder="Search by title or subject..." value="<?= htmlspecialchars($search) ?>" id="searchInp" />
                    </div>
                    <select name="cat" class="filter-sel">
                        <option value="0">All Categories</option>
                        <?php $categories->data_seek(0); while($cat=$categories->fetch_assoc()): ?>
                        <option value="<?= $cat['id'] ?>" <?= $catFilter==$cat['id']?'selected':'' ?>><?= htmlspecialchars($cat['name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                    <button type="submit" class="filter-submit">🔍 Search</button>
                    <?php if(!empty($search)||$catFilter>0||$myFilter): ?>
                    <a href="notes.php" class="clear-link">✕ Clear</a>
                    <?php endif; ?>
                </div>
            </form>

            <!-- Tabs -->
            <div class="filter-tabs">
                <a href="notes.php" class="ftab <?= !$myFilter?'active':'' ?>">📚 All Notes</a>
                <a href="notes.php?filter=mine" class="ftab <?= $myFilter?'active':'' ?>">📁 My Uploads</a>
                <span class="results-count"><?= $total ?> note<?= $total!=1?'s':'' ?> found</span>
            </div>

            <!-- Notes Grid -->
            <?php if($total>0): ?>
            <div class="notes-grid" id="notesGrid">
                <?php $d=0; while($n=$notes->fetch_assoc()):
                    $ext=strtolower(pathinfo($n['file_name'],PATHINFO_EXTENSION));
                    $icos=['pdf'=>'📕','doc'=>'📘','docx'=>'📘','ppt'=>'📙','pptx'=>'📙','txt'=>'📃','png'=>'🖼️','jpg'=>'🖼️','jpeg'=>'🖼️'];
                    $ibgs=['pdf'=>'rgba(220,38,38,.08)','doc'=>'rgba(91,79,207,.08)','docx'=>'rgba(91,79,207,.08)','ppt'=>'rgba(217,119,6,.08)','pptx'=>'rgba(217,119,6,.08)','txt'=>'rgba(5,150,105,.08)'];
                    $ic=$icos[$ext]??'📄'; $ibg=$ibgs[$ext]??'rgba(107,114,128,.08)';
                    $d+=0.04;
                ?>
                <div class="note-card anim-up" style="animation-delay:<?= $d ?>s">
                    <div class="nc-top">
                        <div class="nc-ico" style="background:<?= $ibg ?>"><?= $ic ?></div>
                        <div class="nc-badges">
                            <span class="badge badge-purple"><?= htmlspecialchars($n['subject']) ?></span>
                            <?php if($n['category']): ?><span class="badge badge-teal"><?= htmlspecialchars($n['category']) ?></span><?php endif; ?>
                        </div>
                    </div>
                    <div class="nc-title"><?= htmlspecialchars($n['title']) ?></div>
                    <div class="nc-meta">
                        <span>👤 <?= htmlspecialchars($n['uploader']) ?></span>
                        <span>📅 <?= date('M j, Y',strtotime($n['upload_date'])) ?></span>
                        <span>📎 <?= strtoupper($ext) ?></span>
                    </div>
                    <div class="nc-footer">
                        <span class="nc-dl">📥 <?= $n['downloads'] ?> downloads</span>
                        <div class="nc-btns">
                            <?php if($isAdmin||$n['uid']==$userId): ?>
                            <button class="del-btn" onclick="delNote(<?= $n['id'] ?>,this)">🗑️</button>
                            <?php endif; ?>
                            <button class="prev-btn" onclick="openPreview(<?= $n['id'] ?>, '<?= htmlspecialchars(addslashes($n['title'])) ?>', '<?= strtoupper($ext) ?>')">👁️ Preview</button>
                            <a href="api/downloadAPI.php?id=<?= $n['id'] ?>" class="dl-btn">📥 Download</a>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <div class="empty-ico"><?= !empty($search)?'🔍':'📭' ?></div>
                <div class="empty-title"><?= !empty($search)?'No results found':'No notes yet' ?></div>
                <div class="empty-sub"><?= !empty($search)?"No notes match \"".htmlspecialchars($search)."\".":'Be the first to share study resources!' ?></div>
                <?php if(empty($search)): ?><a href="upload.php" class="btn btn-primary" style="display:inline-flex">⬆️ Upload First Note</a><?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<div class="toast" id="sv-toast"></div>

<!-- ===== PREVIEW MODAL ===== -->
<div class="preview-overlay" id="previewOverlay" onclick="closeOnBg(event)">
    <div class="preview-modal" id="previewModal">
        <div class="pm-header">
            <span id="pmTypeIco" style="font-size:20px">📄</span>
            <div class="pm-title" id="pmTitle">Preview</div>
            <button class="pm-close" onclick="closePreview()" title="Close">✕</button>
        </div>
        <div class="pm-body" id="pmBody">
            <div class="pm-loading"><div class="pm-spinner"></div><span>Loading preview...</span></div>
        </div>
        <div class="pm-footer">
            <a id="pmDownloadBtn" href="#" class="dl-btn" style="text-decoration:none">📥 Download</a>
        </div>
    </div>
</div>

<!-- PDF.js (Mozilla) — renders PDF fully in browser, no plugin needed -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<!-- mammoth.js — converts DOC/DOCX to HTML in browser -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/mammoth/1.6.0/mammoth.browser.min.js"></script>

<script>
// Set PDF.js worker
pdfjsLib.GlobalWorkerOptions.workerSrc =
    'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

// ── Delete Note ──────────────────────────────────────────────────
function delNote(id, btn) {
    if(!confirm('Delete this note? Cannot be undone.')) return;
    btn.disabled=true; btn.textContent='...';
    fetch('api/deleteNoteAPI.php',{method:'DELETE',headers:{'Content-Type':'application/json'},body:JSON.stringify({id})})
    .then(r=>r.json()).then(d=>{
        if(d.status==='success'){const card=btn.closest('.note-card');card.style.transition='all .3s';card.style.opacity='0';card.style.transform='scale(.9)';setTimeout(()=>card.remove(),300);showToast('Note deleted.');}
        else{showToast(d.message||'Failed','error');btn.disabled=false;btn.textContent='🗑️';}
    }).catch(()=>{showToast('Server error','error');btn.disabled=false;btn.textContent='🗑️';});
}

// ── Search Debounce ──────────────────────────────────────────────
let dTimer;
document.getElementById('searchInp').addEventListener('input',function(){
    clearTimeout(dTimer);
    dTimer=setTimeout(()=>{if(this.value.length===0||this.value.length>=2)this.closest('form').submit();},500);
});

// ── Preview Modal ────────────────────────────────────────────────
const typeIcos = {PDF:'📕',DOC:'📘',DOCX:'📘',PPT:'📙',PPTX:'📙',TXT:'📃',PNG:'🖼️',JPG:'🖼️',JPEG:'🖼️',GIF:'🖼️',WEBP:'🖼️'};

// PDF.js state
let pdfDoc = null, pdfPage = 1, pdfTotal = 0;

function openPreview(id, title, ext) {
    pdfDoc = null; pdfPage = 1; pdfTotal = 0;
    document.getElementById('pmTitle').textContent = title + ' (' + ext + ')';
    document.getElementById('pmTypeIco').textContent = typeIcos[ext] || '📄';
    document.getElementById('pmDownloadBtn').href = 'api/downloadAPI.php?id=' + id;
    document.getElementById('pmBody').innerHTML =
        '<div class="pm-loading"><div class="pm-spinner"></div><span>Loading preview...</span></div>';
    document.getElementById('previewOverlay').classList.add('open');
    document.body.style.overflow = 'hidden';

    fetch('api/previewAPI.php?id=' + id)
    .then(r => r.json())
    .then(data => {

        if (data.status === 'error') {
            showNoPreview(data.message); return;
        }

        // ── IMAGES ────────────────────────────────────────────────
        if (data.status === 'image') {
            const body = document.getElementById('pmBody');
            body.innerHTML = '';
            const img = document.createElement('img');
            img.className = 'pm-img';
            img.src = data.data_uri;
            img.alt = title;
            body.appendChild(img);
            return;
        }

        // ── TXT ───────────────────────────────────────────────────
        if (data.status === 'text') {
            document.getElementById('pmBody').innerHTML =
                '<div class="pm-txt">' + escHtml(data.content) + '</div>';
            return;
        }

        // ── PDF — rendered page by page with PDF.js ───────────────
        if (data.status === 'pdf') {
            // Convert data URI to Uint8Array for PDF.js
            const b64 = data.data_uri.split(',')[1];
            const binary = atob(b64);
            const bytes = new Uint8Array(binary.length);
            for (let i = 0; i < binary.length; i++) bytes[i] = binary.charCodeAt(i);

            pdfjsLib.getDocument({ data: bytes }).promise.then(pdf => {
                pdfDoc = pdf;
                pdfTotal = pdf.numPages;
                pdfPage = 1;

                // Build viewer structure
                document.getElementById('pmBody').innerHTML =
                    '<div class="pm-pdf-wrap" id="pdfWrap"></div>';

                // Insert nav bar into footer area
                const footer = document.getElementById('pmBody').closest('.preview-modal').querySelector('.pm-footer');
                footer.innerHTML =
                    '<div class="pm-pdf-nav" style="flex:1">' +
                    '<button id="pdfPrev" onclick="pdfChangePage(-1)" disabled>◀ Prev</button>' +
                    '<span id="pdfPageInfo">Page 1 / ' + pdfTotal + '</span>' +
                    '<button id="pdfNext" onclick="pdfChangePage(1)">Next ▶</button>' +
                    '</div>' +
                    '<a id="pmDownloadBtn" href="api/downloadAPI.php?id=' + id + '" class="dl-btn" style="text-decoration:none">📥 Download</a>';

                renderPdfPage(pdfPage);
            }).catch(() => showNoPreview('Could not render PDF. File may be corrupted.'));
            return;
        }

        // ── WORD (DOC / DOCX) — mammoth.js ────────────────────────
        if (data.status === 'word') {
            const b64 = data.data_uri.split(',')[1];
            const binary = atob(b64);
            const bytes = new Uint8Array(binary.length);
            for (let i = 0; i < binary.length; i++) bytes[i] = binary.charCodeAt(i);
            const arrayBuffer = bytes.buffer;

            mammoth.convertToHtml({ arrayBuffer: arrayBuffer })
            .then(result => {
                document.getElementById('pmBody').innerHTML =
                    '<div class="pm-word-wrap">' + result.value + '</div>';
            }).catch(() => showNoPreview('Could not render Word document.'));
            return;
        }

        // ── PPT / PPTX — no browser renderer ──────────────────────
        if (data.status === 'ppt') {
            showNoPreview('PowerPoint preview is not supported in browsers. Please download the file to view it.');
            return;
        }

        showNoPreview('Unknown file type.');
    })
    .catch(() => showNoPreview('Server error. Please try again.'));
}

// ── PDF page rendering ───────────────────────────────────────────
function renderPdfPage(pageNum) {
    pdfDoc.getPage(pageNum).then(page => {
        const wrap = document.getElementById('pdfWrap');
        wrap.innerHTML = '<div class="pm-loading"><div class="pm-spinner"></div><span>Rendering page ' + pageNum + '...</span></div>';

        const viewport = page.getViewport({ scale: 1.4 });
        const canvas = document.createElement('canvas');
        canvas.width  = viewport.width;
        canvas.height = viewport.height;
        const ctx = canvas.getContext('2d');

        page.render({ canvasContext: ctx, viewport: viewport }).promise.then(() => {
            wrap.innerHTML = '';
            wrap.appendChild(canvas);
            // Scroll back to top on page change
            wrap.scrollTop = 0;
        });

        // Update nav
        const info = document.getElementById('pdfPageInfo');
        if (info) info.textContent = 'Page ' + pageNum + ' / ' + pdfTotal;
        const prev = document.getElementById('pdfPrev');
        const next = document.getElementById('pdfNext');
        if (prev) prev.disabled = (pageNum <= 1);
        if (next) next.disabled = (pageNum >= pdfTotal);
    });
}

function pdfChangePage(delta) {
    const newPage = pdfPage + delta;
    if (newPage < 1 || newPage > pdfTotal) return;
    pdfPage = newPage;
    renderPdfPage(pdfPage);
}

// ── Helpers ──────────────────────────────────────────────────────
function showNoPreview(msg) {
    document.getElementById('pmBody').innerHTML =
        '<div class="pm-nopreview"><span>🚫</span><strong>Preview Not Available</strong>' +
        '<span style="font-size:13px;text-align:center;padding:0 20px">' + msg + '</span></div>';
}

function closePreview() {
    document.getElementById('previewOverlay').classList.remove('open');
    document.body.style.overflow = '';
    document.getElementById('pmBody').innerHTML = '';
    pdfDoc = null;
    // Restore normal footer
    const footer = document.querySelector('.pm-footer');
    if (footer) footer.innerHTML = '<a id="pmDownloadBtn" href="#" class="dl-btn" style="text-decoration:none">📥 Download</a>';
}

function closeOnBg(e) {
    if (e.target === document.getElementById('previewOverlay')) closePreview();
}

function escHtml(s) {
    return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

document.addEventListener('keydown', e => { if(e.key === 'Escape') closePreview(); });
</script>
</body>
</html>