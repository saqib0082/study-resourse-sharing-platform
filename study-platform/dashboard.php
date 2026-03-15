<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }

include_once 'config/db.php';
$conn = getDB();

// Auto-migration: add missing columns if they don't exist
$conn->query("ALTER TABLE notes ADD COLUMN IF NOT EXISTS file_size BIGINT DEFAULT 0");
$conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS last_login DATETIME DEFAULT NULL");
$conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS is_online TINYINT(1) NOT NULL DEFAULT 0");

$userId   = $_SESSION['user_id'];
$userName = $_SESSION['user_name'];
$userRole = $_SESSION['user_role'];
$isAdmin  = ($userRole === 'admin');
$activePage = 'dashboard';

$totalNotes     = $conn->query("SELECT COUNT(*) as c FROM notes")->fetch_assoc()['c'];
$myUploads      = $conn->query("SELECT COUNT(*) as c FROM notes WHERE uploaded_by=$userId")->fetch_assoc()['c'];
$totalDownloads = $conn->query("SELECT COALESCE(SUM(downloads),0) as c FROM notes")->fetch_assoc()['c'];
$totalCats      = $conn->query("SELECT COUNT(*) as c FROM categories")->fetch_assoc()['c'];
$myDownloads    = $conn->query("SELECT COALESCE(SUM(downloads),0) as c FROM notes WHERE uploaded_by=$userId")->fetch_assoc()['c'];
$totalUsers     = $isAdmin ? $conn->query("SELECT COUNT(*) as c FROM users WHERE role='student'")->fetch_assoc()['c'] : 0;
$newToday       = $isAdmin ? $conn->query("SELECT COUNT(*) as c FROM users WHERE DATE(created_at)=CURDATE()")->fetch_assoc()['c'] : 0;

// User storage usage (for storage bar)
$myStorageRes = $conn->query("SELECT COALESCE(SUM(file_size),0) as bytes FROM notes WHERE uploaded_by=$userId");
$myStorageBytes = $myStorageRes ? $myStorageRes->fetch_assoc()['bytes'] : 0;
$LIMIT_USER_BYTES = 1 * 1024 * 1024 * 1024; // 1GB
$storagePercent = min(100, round($myStorageBytes / $LIMIT_USER_BYTES * 100, 1));
$storageMB = round($myStorageBytes / 1024 / 1024, 1);

$recentNotes = $conn->query(
    "SELECT n.id,n.title,n.subject,n.upload_date,n.downloads,n.file_name,u.name as uploader
     FROM notes n JOIN users u ON n.uploaded_by=u.id
     ORDER BY n.upload_date DESC LIMIT 8"
);
$myRecent = $conn->query(
    "SELECT id,title,subject,upload_date,downloads,file_name
     FROM notes WHERE uploaded_by=$userId ORDER BY upload_date DESC LIMIT 4"
);
$conn->close();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudyVault — Dashboard</title>
    <?php include_once 'config/theme.php'; ?>
    <style>
        body { display:flex; }

        /* ── Top Header ── */
        .topbar {
            position:sticky; top:0; z-index:50;
            background:var(--surface);
            border-bottom:1px solid var(--border);
            padding:14px 36px;
            display:flex; align-items:center; gap:16px;
            box-shadow:var(--shadow-xs);
            transition:background .3s, border-color .3s;
        }
        .topbar-title { font-family:'Bricolage Grotesque',sans-serif; font-weight:800; font-size:20px; color:var(--text); flex:1; }
        .topbar-title span { background:linear-gradient(90deg,var(--accent),var(--accent-2)); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text; }
        .topbar-sub { font-size:13px; color:var(--muted); margin-top:1px; }

        /* ── Page Layout ── */
        .page-wrap { display:flex; width:100%; }
        .page-body { flex:1; display:flex; flex-direction:column; margin-left:var(--sidebar-w); }
        .page-main { padding:28px 34px; flex:1; }

        /* ── Admin Banner ── */
        .admin-banner {
            display:flex; align-items:center; gap:14px;
            background:linear-gradient(135deg,rgba(8,145,178,.07),rgba(91,79,207,.04));
            border:1.5px solid rgba(8,145,178,.2);
            border-radius:var(--radius-lg);
            padding:16px 22px; margin-bottom:24px;
            animation:fadeUp .5s .05s both;
        }
        .ab-icon { font-size:26px; }
        .ab-title { font-family:'Bricolage Grotesque',sans-serif;font-weight:700;font-size:15px;color:var(--accent-2); }
        .ab-sub { font-size:12.5px;color:var(--text-3);margin-top:2px; }
        .ab-btn { margin-left:auto; }

        /* ── Stats Grid ── */
        .stats-grid { display:grid; gap:18px; margin-bottom:26px; }
        .stats-grid.s4 { grid-template-columns:repeat(4,1fr); }
        .stats-grid.s6 { grid-template-columns:repeat(6,1fr); }

        .stat-card {
            background:var(--card); border:1px solid var(--border);
            border-radius:var(--radius-lg); padding:20px 22px;
            transition:all .2s; cursor:default;
        }
        .stat-card:hover { transform:translateY(-2px); box-shadow:var(--shadow); border-color:var(--border2); }
        .sc-top { display:flex;justify-content:space-between;align-items:center;margin-bottom:14px; }
        .sc-ico { width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px; }
        .sc-tag { font-size:10.5px;font-weight:800;padding:3px 9px;border-radius:20px; }
        .sc-num { font-family:'Bricolage Grotesque',sans-serif;font-weight:800;font-size:32px;line-height:1;margin-bottom:3px; }
        .sc-lbl { font-size:12px;color:var(--text-3); }

        /* Storage bar */
        .storage-bar-wrap { margin-top:6px; }
        .storage-bar { height:5px;background:var(--border);border-radius:5px;overflow:hidden;margin-bottom:4px; }
        .storage-fill { height:100%;border-radius:5px;transition:width .8s;background:linear-gradient(90deg,var(--accent),var(--accent-2)); }

        /* ── Two col layout ── */
        .two-col { display:grid;grid-template-columns:1fr 320px;gap:22px; }

        /* ── Section Title ── */
        .sec-hd { display:flex;justify-content:space-between;align-items:center;margin-bottom:14px; }
        .sec-hd h2 { font-family:'Bricolage Grotesque',sans-serif;font-weight:700;font-size:16px;color:var(--text); }
        .sec-hd a  { font-size:12.5px;font-weight:700;color:var(--accent);text-decoration:none; }
        .sec-hd a:hover { text-decoration:underline; }

        /* ── Quick Actions ── */
        .qa-list { display:flex;flex-direction:column;gap:10px; }
        .qa {
            display:flex;align-items:center;gap:13px;
            padding:14px 16px;
            background:var(--card);border:1px solid var(--border);
            border-radius:var(--radius-lg);
            text-decoration:none;color:var(--text);
            transition:all .18s;
        }
        .qa:hover { transform:translateX(4px);box-shadow:var(--shadow-sm); }
        .qa.qa-blue:hover  { border-color:rgba(91,79,207,.3);background:rgba(91,79,207,.04); }
        .qa.qa-teal:hover  { border-color:rgba(8,145,178,.3);background:rgba(8,145,178,.04); }
        .qa.qa-green:hover { border-color:rgba(5,150,105,.3);background:rgba(5,150,105,.04); }
        .qa.qa-amber:hover { border-color:rgba(217,119,6,.3);background:rgba(217,119,6,.04); }
        .qa-ico { width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0; }
        .qa-txt .qa-t { font-family:'Bricolage Grotesque',sans-serif;font-weight:700;font-size:13.5px;color:var(--text);margin-bottom:2px; }
        .qa-txt .qa-s { font-size:12px;color:var(--text-3); }
        .qa-arr { margin-left:auto;color:var(--muted);font-size:18px;font-weight:300; }

        /* ── Recent Table ── */
        .note-name-cell { display:flex;align-items:center;gap:8px;font-weight:700;font-size:13.5px;color:var(--text); }
        .note-ico { width:30px;height:30px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:14px;flex-shrink:0; }
        .note-title-txt { max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap; }

        /* ── My Uploads Panel ── */
        .mu-panel { background:var(--card);border:1px solid var(--border);border-radius:var(--radius-lg); }
        .mu-header { padding:16px 18px;border-bottom:1px solid var(--border); }
        .mu-title { font-family:'Bricolage Grotesque',sans-serif;font-weight:700;font-size:15px;color:var(--text); }
        .mu-item { padding:13px 18px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:12px;transition:background .15s; }
        .mu-item:last-child { border-bottom:none; }
        .mu-item:hover { background:var(--bg); }
        .mu-ico { width:34px;height:34px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:15px;flex-shrink:0; }
        .mu-info { flex:1;overflow:hidden; }
        .mu-name { font-size:13px;font-weight:700;color:var(--text);overflow:hidden;text-overflow:ellipsis;white-space:nowrap; }
        .mu-meta { font-size:11.5px;color:var(--text-3);margin-top:1px; }
        .mu-empty { padding:38px;text-align:center;color:var(--muted);font-size:13.5px; }

        .dl-link { padding:6px 13px;background:rgba(91,79,207,.08);border:1px solid rgba(91,79,207,.18);border-radius:8px;color:var(--accent);font-size:12px;font-weight:700;text-decoration:none;transition:all .2s;white-space:nowrap; }
        .dl-link:hover { background:var(--accent);color:#fff;border-color:var(--accent); }

        @media(max-width:1100px){.stats-grid.s6{grid-template-columns:repeat(3,1fr)}}
        @media(max-width:900px){.two-col{grid-template-columns:1fr}.stats-grid.s4{grid-template-columns:repeat(2,1fr)}}
        @media(max-width:768px){.page-body{margin-left:0}}
    </style>
</head>
<body>
<div class="page-wrap">
    <?php include 'config/sidebar.php'; ?>

    <div class="page-body">
        <!-- Topbar -->
        <div class="topbar">
            <div style="flex:1">
                <div class="topbar-title">Hello, <span><?= htmlspecialchars(explode(' ',$userName)[0]) ?></span> <?= $isAdmin?'🛡️':'👋' ?></div>
                <div class="topbar-sub"><?= date('l, F j, Y') ?> · <?= $isAdmin?'Administrator':'Student Dashboard' ?></div>
            </div>
            <a href="upload.php" class="btn btn-primary" style="font-size:13px;padding:9px 18px">⬆️ Upload</a>
            <a href="notes.php"  class="btn btn-secondary" style="font-size:13px;padding:9px 18px">📚 Library</a>
            <button class="theme-toggle" onclick="toggleTheme()">🌙</button>
        </div>

        <div class="page-main">
            <?php if ($isAdmin): ?>
            <!-- Admin Banner -->
            <div class="admin-banner anim-up">
                <div class="ab-icon">🛡️</div>
                <div>
                    <div class="ab-title">Administrator Access Active</div>
                    <div class="ab-sub"><?= $totalUsers ?> students · <?= $totalNotes ?> notes · <?= $newToday ?> new registrations today</div>
                </div>
                <a href="admin.php" class="btn btn-primary ab-btn" style="font-size:13px;padding:9px 18px">Open Admin Panel →</a>
            </div>
            <?php endif; ?>

            <!-- Stats -->
            <?php if ($isAdmin): ?>
            <div class="stats-grid s6">
                <div class="stat-card anim-up anim-up-1" style="border-top:3px solid var(--accent)">
                    <div class="sc-top"><div class="sc-ico" style="background:rgba(91,79,207,.1)">👨‍🎓</div><span class="sc-tag" style="background:rgba(91,79,207,.1);color:var(--accent)">Students</span></div>
                    <div class="sc-num" style="color:var(--accent)"><?= $totalUsers ?></div><div class="sc-lbl">Registered Students</div>
                </div>
                <div class="stat-card anim-up anim-up-2" style="border-top:3px solid var(--accent-2)">
                    <div class="sc-top"><div class="sc-ico" style="background:rgba(8,145,178,.1)">🆕</div><span class="sc-tag" style="background:rgba(8,145,178,.1);color:var(--accent-2)">Today</span></div>
                    <div class="sc-num" style="color:var(--accent-2)"><?= $newToday ?></div><div class="sc-lbl">New Today</div>
                </div>
                <div class="stat-card anim-up anim-up-3" style="border-top:3px solid var(--accent-3)">
                    <div class="sc-top"><div class="sc-ico" style="background:rgba(5,150,105,.1)">📄</div><span class="sc-tag" style="background:rgba(5,150,105,.1);color:var(--accent-3)">Platform</span></div>
                    <div class="sc-num" style="color:var(--accent-3)"><?= $totalNotes ?></div><div class="sc-lbl">Total Notes</div>
                </div>
                <div class="stat-card anim-up anim-up-4" style="border-top:3px solid var(--accent-4)">
                    <div class="sc-top"><div class="sc-ico" style="background:rgba(217,119,6,.1)">📥</div><span class="sc-tag" style="background:rgba(217,119,6,.1);color:var(--accent-4)">All-time</span></div>
                    <div class="sc-num" style="color:var(--accent-4)"><?= $totalDownloads ?></div><div class="sc-lbl">Total Downloads</div>
                </div>
                <div class="stat-card anim-up anim-up-5" style="border-top:3px solid var(--accent)">
                    <div class="sc-top"><div class="sc-ico" style="background:rgba(91,79,207,.1)">📤</div><span class="sc-tag" style="background:rgba(91,79,207,.1);color:var(--accent)">Mine</span></div>
                    <div class="sc-num" style="color:var(--accent)"><?= $myUploads ?></div><div class="sc-lbl">My Uploads</div>
                </div>
                <div class="stat-card anim-up anim-up-6" style="border-top:3px solid var(--accent-2)">
                    <div class="sc-top"><div class="sc-ico" style="background:rgba(8,145,178,.1)">🏷️</div><span class="sc-tag" style="background:rgba(8,145,178,.1);color:var(--accent-2)">Active</span></div>
                    <div class="sc-num" style="color:var(--accent-2)"><?= $totalCats ?></div><div class="sc-lbl">Categories</div>
                </div>
            </div>
            <?php else: ?>
            <div class="stats-grid s4">
                <div class="stat-card anim-up anim-up-1" style="border-top:3px solid var(--accent)">
                    <div class="sc-top"><div class="sc-ico" style="background:rgba(91,79,207,.1)">📄</div><span class="sc-tag" style="background:rgba(91,79,207,.1);color:var(--accent)">All</span></div>
                    <div class="sc-num" style="color:var(--accent)"><?= $totalNotes ?></div><div class="sc-lbl">Notes Available</div>
                </div>
                <div class="stat-card anim-up anim-up-2" style="border-top:3px solid var(--accent-2)">
                    <div class="sc-top"><div class="sc-ico" style="background:rgba(8,145,178,.1)">📤</div><span class="sc-tag" style="background:rgba(8,145,178,.1);color:var(--accent-2)">Mine</span></div>
                    <div class="sc-num" style="color:var(--accent-2)"><?= $myUploads ?></div><div class="sc-lbl">My Uploads</div>
                </div>
                <div class="stat-card anim-up anim-up-3" style="border-top:3px solid var(--accent-3)">
                    <div class="sc-top"><div class="sc-ico" style="background:rgba(5,150,105,.1)">📥</div><span class="sc-tag" style="background:rgba(5,150,105,.1);color:var(--accent-3)">My Notes</span></div>
                    <div class="sc-num" style="color:var(--accent-3)"><?= $myDownloads ?></div><div class="sc-lbl">Downloads on My Notes</div>
                </div>
                <div class="stat-card anim-up anim-up-4" style="border-top:3px solid var(--accent-4)">
                    <div class="sc-top"><div class="sc-ico" style="background:rgba(217,119,6,.1)">💾</div><span class="sc-tag" style="background:rgba(217,119,6,.1);color:var(--accent-4)"><?= $storagePercent ?>%</span></div>
                    <div class="sc-num" style="color:var(--accent-4)"><?= $storageMB ?> MB</div>
                    <div class="sc-lbl">Storage Used (1 GB limit)</div>
                    <div class="storage-bar-wrap">
                        <div class="storage-bar"><div class="storage-fill" style="width:<?= $storagePercent ?>%;<?= $storagePercent>85?'background:var(--danger)':'' ?>"></div></div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Two Column -->
            <div class="two-col anim-up" style="animation-delay:.22s">
                <!-- Recent Notes -->
                <div>
                    <div class="sec-hd"><h2>📋 Recently Added Notes</h2><a href="notes.php">View all →</a></div>
                    <div class="tbl-wrap">
                        <table>
                            <thead><tr><th>Title</th><th>Subject</th><th>By</th><th>DL</th><th>Date</th><th></th></tr></thead>
                            <tbody>
                                <?php if ($recentNotes->num_rows > 0): while($r=$recentNotes->fetch_assoc()):
                                    $ext=strtolower(pathinfo($r['file_name'],PATHINFO_EXTENSION));
                                    $icos=['pdf'=>'📕','doc'=>'📘','docx'=>'📘','ppt'=>'📙','pptx'=>'📙','txt'=>'📃','png'=>'🖼️','jpg'=>'🖼️'];
                                    $ic=$icos[$ext]??'📄';
                                    $ibgs=['pdf'=>'rgba(220,38,38,.08)','doc'=>'rgba(91,79,207,.08)','docx'=>'rgba(91,79,207,.08)','ppt'=>'rgba(217,119,6,.08)','pptx'=>'rgba(217,119,6,.08)'];
                                    $ibg=$ibgs[$ext]??'rgba(107,114,128,.08)';
                                ?>
                                <tr>
                                    <td>
                                        <div class="note-name-cell">
                                            <div class="note-ico" style="background:<?= $ibg ?>"><?= $ic ?></div>
                                            <span class="note-title-txt" title="<?= htmlspecialchars($r['title']) ?>"><?= htmlspecialchars($r['title']) ?></span>
                                        </div>
                                    </td>
                                    <td><span class="badge badge-purple"><?= htmlspecialchars($r['subject']) ?></span></td>
                                    <td style="font-size:12.5px;color:var(--text-3)"><?= htmlspecialchars(explode(' ',$r['uploader'])[0]) ?></td>
                                    <td style="font-size:12px;color:var(--text-3)">📥<?= $r['downloads'] ?></td>
                                    <td style="font-size:12px;color:var(--muted)"><?= date('M j',strtotime($r['upload_date'])) ?></td>
                                    <td><a href="api/downloadAPI.php?id=<?= $r['id'] ?>" class="dl-link">↓</a></td>
                                </tr>
                                <?php endwhile; else: ?>
                                <tr class="empty-row"><td colspan="6">📭 No notes yet. <a href="upload.php" style="color:var(--accent)">Upload first!</a></td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Right Col -->
                <div>
                    <div class="sec-hd"><h2>⚡ Quick Actions</h2></div>
                    <div class="qa-list" style="margin-bottom:22px">
                        <a href="upload.php" class="qa qa-blue">
                            <div class="qa-ico" style="background:rgba(91,79,207,.1)">⬆️</div>
                            <div class="qa-txt"><div class="qa-t">Upload Notes</div><div class="qa-s">Share PDFs, slides, docs</div></div>
                            <span class="qa-arr">›</span>
                        </a>
                        <a href="notes.php" class="qa qa-teal">
                            <div class="qa-ico" style="background:rgba(8,145,178,.1)">📚</div>
                            <div class="qa-txt"><div class="qa-t">Browse Library</div><div class="qa-s">Find study resources</div></div>
                            <span class="qa-arr">›</span>
                        </a>
                        <a href="notes.php?search=1" class="qa qa-green">
                            <div class="qa-ico" style="background:rgba(5,150,105,.1)">🔍</div>
                            <div class="qa-txt"><div class="qa-t">Search Notes</div><div class="qa-s">Find by subject or keyword</div></div>
                            <span class="qa-arr">›</span>
                        </a>
                        <?php if ($isAdmin): ?>
                        <a href="admin.php" class="qa qa-amber">
                            <div class="qa-ico" style="background:rgba(217,119,6,.1)">🛡️</div>
                            <div class="qa-txt"><div class="qa-t">Admin Panel</div><div class="qa-s">Manage users & content</div></div>
                            <span class="qa-arr">›</span>
                        </a>
                        <?php else: ?>
                        <a href="notes.php?filter=mine" class="qa qa-amber">
                            <div class="qa-ico" style="background:rgba(217,119,6,.1)">📁</div>
                            <div class="qa-txt"><div class="qa-t">My Uploads</div><div class="qa-s">View your shared notes</div></div>
                            <span class="qa-arr">›</span>
                        </a>
                        <?php endif; ?>
                    </div>

                    <div class="sec-hd"><h2>📁 My Recent Uploads</h2><a href="notes.php?filter=mine">See all →</a></div>
                    <div class="mu-panel">
                        <?php if ($myRecent->num_rows > 0): while($mu=$myRecent->fetch_assoc()):
                            $ext=strtolower(pathinfo($mu['file_name'],PATHINFO_EXTENSION));
                            $bgs=['pdf'=>'rgba(220,38,38,.08)','doc'=>'rgba(91,79,207,.08)','docx'=>'rgba(91,79,207,.08)','ppt'=>'rgba(217,119,6,.08)','pptx'=>'rgba(217,119,6,.08)'];
                            $ics=['pdf'=>'📕','doc'=>'📘','docx'=>'📘','ppt'=>'📙','pptx'=>'📙','txt'=>'📃'];
                            $bg=$bgs[$ext]??'rgba(107,114,128,.08)'; $ic=$ics[$ext]??'📄';
                        ?>
                        <div class="mu-item">
                            <div class="mu-ico" style="background:<?= $bg ?>"><?= $ic ?></div>
                            <div class="mu-info">
                                <div class="mu-name" title="<?= htmlspecialchars($mu['title']) ?>"><?= htmlspecialchars($mu['title']) ?></div>
                                <div class="mu-meta">📥 <?= $mu['downloads'] ?> dl · <?= date('M j',strtotime($mu['upload_date'])) ?></div>
                            </div>
                            <a href="api/downloadAPI.php?id=<?= $mu['id'] ?>" class="dl-link">↓</a>
                        </div>
                        <?php endwhile; else: ?>
                        <div class="mu-empty">📭 No uploads yet.<br><a href="upload.php" style="color:var(--accent);font-weight:700">Upload your first note →</a></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="toast" id="sv-toast"></div>
</body>
</html>
