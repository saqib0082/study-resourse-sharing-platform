<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: admin_login.php"); exit(); }
if ($_SESSION['user_role'] !== 'admin') { header("Location: dashboard.php"); exit(); }

include_once 'config/db.php';
$conn = getDB();

// Auto-migration: ensure last_login & is_online columns exist
$conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS last_login DATETIME DEFAULT NULL");
$conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS is_online TINYINT(1) NOT NULL DEFAULT 0");

$adminName  = $_SESSION['user_name'];
$adminEmail = $_SESSION['user_email'] ?? '';
$userId     = $_SESSION['user_id'];
$userName   = $adminName;
$userRole   = 'admin';
$isAdmin    = true;
$activePage = 'admin';

$totalUsers     = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='student'")->fetch_assoc()['c'];
$totalAdmins    = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='admin'")->fetch_assoc()['c'];
$totalNotes     = $conn->query("SELECT COUNT(*) as c FROM notes")->fetch_assoc()['c'];
$totalDownloads = $conn->query("SELECT COALESCE(SUM(downloads),0) as c FROM notes")->fetch_assoc()['c'];
$totalCats      = $conn->query("SELECT COUNT(*) as c FROM categories")->fetch_assoc()['c'];
$newToday       = $conn->query("SELECT COUNT(*) as c FROM users WHERE DATE(created_at)=CURDATE()")->fetch_assoc()['c'];
$onlineCount    = $conn->query("SELECT COUNT(*) as c FROM users WHERE is_online=1")->fetch_assoc()['c'];

// Platform storage
$storageRes     = $conn->query("SELECT COALESCE(SUM(file_size),0) as b FROM notes");
$platformBytes  = $storageRes ? $storageRes->fetch_assoc()['b'] : 0;
$LIMIT_TOTAL    = 60 * 1024 * 1024 * 1024; // 60GB
$platformGB     = round($platformBytes / 1024 / 1024 / 1024, 2);
$platformPct    = min(100, round($platformBytes / $LIMIT_TOTAL * 100, 1));

$usersResult = $conn->query(
    "SELECT u.id,u.name,u.email,u.role,u.created_at,u.last_login,u.is_online,
            COUNT(n.id) as nc,COALESCE(SUM(n.file_size),0) as bytes
     FROM users u LEFT JOIN notes n ON n.uploaded_by=u.id
     GROUP BY u.id ORDER BY u.created_at DESC"
);
$notesResult = $conn->query(
    "SELECT n.id,n.title,n.subject,n.file_name,n.file_size,n.downloads,n.upload_date,u.name as uploader,c.name as category
     FROM notes n JOIN users u ON n.uploaded_by=u.id
     LEFT JOIN categories c ON n.category_id=c.id ORDER BY n.upload_date DESC"
);
$catsResult  = $conn->query(
    "SELECT c.id,c.name,COUNT(n.id) as nc FROM categories c
     LEFT JOIN notes n ON n.category_id=c.id GROUP BY c.id ORDER BY c.name"
);
$conn->close();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudyVault — Admin Panel</title>
    <?php include_once 'config/theme.php'; ?>
    <style>
        body { display:flex; }
        .page-wrap { display:flex;width:100%; }
        .page-body { flex:1;display:flex;flex-direction:column;margin-left:var(--sidebar-w); }

        /* Topbar */
        .topbar {
            position:sticky;top:0;z-index:50;
            background:var(--surface);border-bottom:1px solid var(--border);
            padding:14px 34px;display:flex;align-items:center;gap:14px;
            box-shadow:var(--shadow-xs);transition:background .3s;
        }
        .topbar-title { font-family:'Bricolage Grotesque',sans-serif;font-weight:800;font-size:20px;flex:1; }
        .topbar-title .admin-chip { display:inline-flex;align-items:center;gap:6px;background:rgba(8,145,178,.1);border:1px solid rgba(8,145,178,.2);border-radius:8px;padding:2px 10px;font-size:11px;font-weight:800;color:var(--accent-2);vertical-align:middle;margin-left:10px; }
        .admin-dot { width:7px;height:7px;background:var(--accent-2);border-radius:50%;animation:pulse 2s infinite; }

        .page-main { padding:28px 34px; }

        /* Stats */
        .stats-grid { display:grid;grid-template-columns:repeat(6,1fr);gap:16px;margin-bottom:26px; }
        .stat-card {
            background:var(--card);border:1px solid var(--border);
            border-radius:var(--radius-lg);padding:18px 20px;
            transition:all .2s;cursor:default;
        }
        .stat-card:hover { transform:translateY(-2px);box-shadow:var(--shadow); }
        .sc-top { display:flex;justify-content:space-between;align-items:center;margin-bottom:12px; }
        .sc-ico { width:38px;height:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:17px; }
        .sc-num { font-family:'Bricolage Grotesque',sans-serif;font-weight:800;font-size:28px;line-height:1;margin-bottom:2px; }
        .sc-lbl { font-size:11.5px;color:var(--text-3); }

        /* Storage bar */
        .storage-bar { height:4px;background:var(--border);border-radius:4px;overflow:hidden;margin-top:6px; }
        .storage-fill { height:100%;border-radius:4px;background:linear-gradient(90deg,var(--accent-2),var(--accent));transition:width .8s; }

        /* Tabs */
        .tabs-bar { display:flex;gap:6px;background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-lg);padding:5px;width:fit-content;margin-bottom:22px; }
        .tab-btn { padding:9px 22px;border-radius:var(--radius);font-size:13.5px;font-weight:700;cursor:pointer;border:none;color:var(--text-3);background:transparent;transition:all .18s;display:inline-flex;align-items:center;gap:7px; }
        .tab-btn.active { background:var(--card);color:var(--accent);box-shadow:var(--shadow-sm);border:1px solid var(--border); }
        .tab-btn:hover:not(.active) { color:var(--text);background:var(--bg); }

        /* Section hidden */
        .sec-hidden { display:none; }

        /* Role badge */
        .role-badge { display:inline-flex;align-items:center;gap:5px;padding:4px 11px;border-radius:20px;font-size:11.5px;font-weight:700; }
        .rb-admin   { background:rgba(8,145,178,.1);color:var(--accent-2);border:1px solid rgba(8,145,178,.2); }
        .rb-student { background:rgba(91,79,207,.08);color:var(--accent);border:1px solid rgba(91,79,207,.15); }

        /* User cell */
        .u-cell { display:flex;align-items:center;gap:10px; }
        .u-av { width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-family:'Bricolage Grotesque',sans-serif;font-weight:800;font-size:13px;color:#fff;flex-shrink:0; }
        .u-nm { font-weight:700;font-size:13.5px;color:var(--text); }
        .u-em { font-size:11.5px;color:var(--muted); }

        /* Action buttons */
        .acts { display:flex;gap:6px;flex-wrap:wrap; }
        .act { padding:6px 12px;border-radius:8px;font-size:12px;font-weight:700;cursor:pointer;border:none;transition:all .18s; }
        .act-promote { background:rgba(8,145,178,.08);color:var(--accent-2);border:1px solid rgba(8,145,178,.2); }
        .act-promote:hover { background:var(--accent-2);color:#fff; }
        .act-demote  { background:rgba(91,79,207,.08);color:var(--accent);border:1px solid rgba(91,79,207,.2); }
        .act-demote:hover { background:var(--accent);color:#fff; }
        .act-delete  { background:var(--danger-bg);color:var(--danger);border:1px solid rgba(220,38,38,.2); }
        .act-delete:hover { background:var(--danger);color:#fff; }
        .act-dl { background:rgba(5,150,105,.08);color:var(--accent-3);border:1px solid rgba(5,150,105,.2);text-decoration:none; }
        .act-dl:hover { background:var(--accent-3);color:#fff; }

        .note-name-cell { font-weight:700;max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:var(--text); }
        .file-ext { display:inline-block;padding:2px 8px;border-radius:5px;font-size:11px;font-weight:800;background:rgba(5,150,105,.08);color:var(--accent-3); }
        .storage-cell { font-size:12px;color:var(--text-3); }

        /* Modal */
        .modal-bg { position:fixed;inset:0;background:rgba(0,0,0,.35);backdrop-filter:blur(4px);z-index:500;display:none;align-items:center;justify-content:center; }
        .modal-bg.show { display:flex; }
        .modal { background:var(--card);border:1px solid var(--border);border-radius:var(--radius-xl);padding:32px;width:100%;max-width:440px;box-shadow:var(--shadow-xl);animation:fadeUp .3s both; }
        .modal-title { font-family:'Bricolage Grotesque',sans-serif;font-weight:800;font-size:19px;color:var(--text);margin-bottom:8px; }
        .modal-body { font-size:13.5px;color:var(--text-3);margin-bottom:24px;line-height:1.6; }
        .modal-btns { display:flex;gap:10px;justify-content:flex-end; }

        @media(max-width:1400px){.stats-grid{grid-template-columns:repeat(3,1fr)}}
        @media(max-width:900px){.stats-grid{grid-template-columns:repeat(2,1fr)}}
        @media(max-width:768px){.page-body{margin-left:0}}

        /* Create User Form */
        #createUserForm .form-input { font-size:13px; padding:10px 12px 10px 38px; }
        #createUserForm select.form-input { padding-left:12px; }
        #createUserForm .input-wrap { position:relative; }
        #createUserForm .input-icon { position:absolute;left:11px;top:50%;transform:translateY(-50%);font-size:14px;pointer-events:none; }
        .btn-primary { background:linear-gradient(135deg,var(--accent),var(--accent-h));color:#fff;border:none;border-radius:var(--radius);font-weight:700;cursor:pointer;transition:all .2s; }
        .btn-primary:hover { opacity:.88;transform:translateY(-1px); }
    </style>
</head>
<body>
<div class="page-wrap">
    <?php include 'config/sidebar.php'; ?>
    <div class="page-body">
        <!-- Topbar -->
        <div class="topbar">
            <div style="flex:1">
                <div class="topbar-title">
                    Admin Panel
                    <span class="admin-chip"><span class="admin-dot"></span>ACTIVE</span>
                </div>
            </div>
            <span style="font-size:13px;color:var(--muted)"><?= htmlspecialchars($adminEmail) ?></span>
            <a href="autofix.php" class="btn btn-secondary" style="font-size:13px;padding:9px 16px" target="_blank">🔧 System Check</a>
            <button class="theme-toggle" onclick="toggleTheme()">🌙</button>
        </div>

        <div class="page-main">
            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card anim-up anim-up-1" style="border-top:3px solid var(--accent)">
                    <div class="sc-top"><div class="sc-ico" style="background:rgba(91,79,207,.08)">👨‍🎓</div></div>
                    <div class="sc-num" style="color:var(--accent)"><?= $totalUsers ?></div><div class="sc-lbl">Students</div>
                </div>
                <div class="stat-card anim-up anim-up-2" style="border-top:3px solid var(--accent-2)">
                    <div class="sc-top"><div class="sc-ico" style="background:rgba(8,145,178,.08)">🛡️</div></div>
                    <div class="sc-num" style="color:var(--accent-2)"><?= $totalAdmins ?></div><div class="sc-lbl">Admins</div>
                </div>
                <div class="stat-card anim-up anim-up-3" style="border-top:3px solid var(--accent-3)">
                    <div class="sc-top"><div class="sc-ico" style="background:rgba(5,150,105,.08)">📄</div></div>
                    <div class="sc-num" style="color:var(--accent-3)"><?= $totalNotes ?></div><div class="sc-lbl">Total Notes</div>
                </div>
                <div class="stat-card anim-up anim-up-4" style="border-top:3px solid var(--accent-4)">
                    <div class="sc-top"><div class="sc-ico" style="background:rgba(217,119,6,.08)">📥</div></div>
                    <div class="sc-num" style="color:var(--accent-4)"><?= $totalDownloads ?></div><div class="sc-lbl">Total Downloads</div>
                </div>
                <div class="stat-card anim-up anim-up-5" style="border-top:3px solid var(--accent-3)">
                    <div class="sc-top"><div class="sc-ico" style="background:rgba(5,150,105,.08)">🆕</div></div>
                    <div class="sc-num" style="color:var(--accent-3)"><?= $newToday ?></div><div class="sc-lbl">New Today</div>
                </div>
                <div class="stat-card anim-up anim-up-6" style="border-top:3px solid var(--accent)">
                    <div class="sc-top"><div class="sc-ico" style="background:rgba(91,79,207,.08)">💾</div><span style="font-size:11px;font-weight:700;color:var(--muted)"><?= $platformPct ?>%</span></div>
                    <div class="sc-num" style="color:var(--accent);font-size:22px"><?= $platformGB ?> GB</div>
                    <div class="sc-lbl">Server Storage (60 GB max)</div>
                    <div class="storage-bar"><div class="storage-fill" style="width:<?= $platformPct ?>%;<?= $platformPct>85?'background:var(--danger)':'' ?>"></div></div>
                </div>
            </div>

            <!-- Tabs -->
            <div class="tabs-bar anim-up" style="animation-delay:.25s">
                <button class="tab-btn active" onclick="showTab('overview',this)">📊 Overview</button>
                <button class="tab-btn" onclick="showTab('users',this)">👥 User Management (<?= $totalUsers+$totalAdmins ?>)</button>
                <button class="tab-btn" onclick="showTab('notes',this)">📄 Notes (<?= $totalNotes ?>)</button>
                <button class="tab-btn" onclick="showTab('cats',this)">🏷️ Categories</button>
            </div>

            <!-- ══ OVERVIEW ══ -->
            <div id="tab-overview" class="anim-up" style="animation-delay:.3s">
                <div class="tbl-wrap">
                    <div class="tbl-header">
                        <div class="tbl-title">👥 Latest Registered Users</div>
                        <button class="btn btn-secondary" style="font-size:12.5px;padding:7px 14px" onclick="document.querySelector('[onclick*=users]').click()">View All →</button>
                    </div>
                    <table><thead><tr><th>#</th><th>User</th><th>Role</th><th>Notes</th><th>Storage</th><th>Joined</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php $usersResult->data_seek(0); $i=1; while($u=$usersResult->fetch_assoc()):
                            if($i>10) break;
                            $colors=['#5b4fcf','#0891b2','#059669','#d97706','#dc2626','#7c3aed'];
                            $c=$colors[abs(crc32($u['name']))%count($colors)];
                            $uMB=round($u['bytes']/1024/1024,1);
                        ?>
                        <tr id="ur-<?= $u['id'] ?>">
                            <td style="color:var(--muted);font-size:12px"><?= $i++ ?></td>
                            <td><div class="u-cell"><div class="u-av" style="background:<?= $c ?>"><?= strtoupper(substr($u['name'],0,1)) ?></div><div><div class="u-nm"><?= htmlspecialchars($u['name']) ?></div><div class="u-em"><?= htmlspecialchars($u['email']) ?></div></div></div></td>
                            <td><span class="role-badge <?= $u['role']==='admin'?'rb-admin':'rb-student' ?>" id="rb-<?= $u['id'] ?>"><?= $u['role']==='admin'?'🛡️ Admin':'👤 Student' ?></span></td>
                            <td style="font-size:13px;color:var(--text-3)">📄 <?= $u['nc'] ?></td>
                            <td class="storage-cell">💾 <?= $uMB ?> MB</td>
                            <td style="font-size:12px;color:var(--muted)"><?= date('M j, Y',strtotime($u['created_at'])) ?></td>
                            <td>
                                <?php if($u['id']!=$_SESSION['user_id']): ?>
                                <div class="acts">
                                    <?php if($u['role']==='student'): ?><button class="act act-promote" onclick="changeRole(<?=$u['id']?>,'admin','<?=addslashes($u['name'])?>')">⬆ Promote</button>
                                    <?php else: ?><button class="act act-demote" onclick="changeRole(<?=$u['id']?>,'student','<?=addslashes($u['name'])?>')">⬇ Demote</button><?php endif; ?>
                                    <button class="act act-delete" onclick="confirmDel(<?=$u['id']?>,'<?=addslashes($u['name'])?>')">🗑</button>
                                </div>
                                <?php else: ?><span style="font-size:12px;color:var(--muted);font-style:italic">You</span><?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody></table>
                </div>
            </div>

            <!-- ══ USERS ══ -->
            <!-- ══ USER MANAGEMENT ══ -->
            <div id="tab-users" class="sec-hidden">

                <!-- Create New User Card -->
                <div class="tbl-wrap" style="margin-bottom:22px">
                    <div class="tbl-header">
                        <div class="tbl-title">➕ Create New User <span style="font-weight:400;color:var(--muted);font-size:13px">— Only admins can create accounts</span></div>
                        <button class="btn btn-secondary" style="font-size:12.5px;padding:7px 14px" onclick="toggleCreateForm()">✏️ New User</button>
                    </div>
                    <div id="createUserForm" style="display:none;padding:20px 0 6px">
                        <div style="display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:12px;align-items:end">
                            <div class="form-group" style="margin:0">
                                <label class="form-label" style="font-size:12px">Full Name</label>
                                <div class="input-wrap"><span class="input-icon">👤</span><input type="text" id="cu_name" class="form-input" placeholder="e.g. Ali Hassan"></div>
                            </div>
                            <div class="form-group" style="margin:0">
                                <label class="form-label" style="font-size:12px">Email Address</label>
                                <div class="input-wrap"><span class="input-icon">✉️</span><input type="email" id="cu_email" class="form-input" placeholder="user@example.com"></div>
                            </div>
                            <div class="form-group" style="margin:0">
                                <label class="form-label" style="font-size:12px">Password</label>
                                <div class="input-wrap"><span class="input-icon">🔒</span><input type="password" id="cu_pass" class="form-input" placeholder="Min. 6 characters"></div>
                            </div>
                            <div class="form-group" style="margin:0">
                                <label class="form-label" style="font-size:12px">Role</label>
                                <select id="cu_role" class="form-input" style="padding-left:12px">
                                    <option value="student">👤 Student</option>
                                    <option value="admin">🛡️ Admin</option>
                                </select>
                            </div>
                        </div>
                        <div style="display:flex;align-items:center;gap:12px;margin-top:14px">
                            <button class="btn btn-primary" id="cuBtn" onclick="createUser()" style="padding:10px 24px;font-size:13.5px">✅ Create Account</button>
                            <button class="btn btn-secondary" onclick="toggleCreateForm()" style="padding:10px 18px;font-size:13px">Cancel</button>
                            <span id="cuMsg" style="font-size:13px;font-weight:600"></span>
                        </div>
                    </div>
                </div>

                <!-- Users Table -->
                <div class="tbl-wrap">
                    <div class="tbl-header">
                        <div class="tbl-title">👥 All Users
                            <span style="font-weight:400;color:var(--muted);font-size:13px">(<?= $totalUsers+$totalAdmins ?> total)</span>
                            <span style="display:inline-flex;align-items:center;gap:5px;margin-left:10px;background:rgba(5,150,105,.08);border:1px solid rgba(5,150,105,.2);border-radius:8px;padding:2px 10px;font-size:11px;font-weight:800;color:var(--accent-3)">
                                <span style="width:7px;height:7px;background:var(--accent-3);border-radius:50%;display:inline-block;animation:pulse 2s infinite"></span>
                                <?= $onlineCount ?> Online
                            </span>
                        </div>
                        <input type="text" class="tbl-search" placeholder="🔍 Search users..." oninput="filterTbl('utbody',this.value,[1,2])">
                    </div>
                    <table>
                        <thead><tr><th>#</th><th>User</th><th>Role</th><th>Status</th><th>Notes</th><th>Storage</th><th>Last Login</th><th>Joined</th><th>Actions</th></tr></thead>
                        <tbody id="utbody">
                        <?php $usersResult->data_seek(0); $i=1; while($u=$usersResult->fetch_assoc()):
                            $colors=['#5b4fcf','#0891b2','#059669','#d97706','#dc2626','#7c3aed'];
                            $c=$colors[abs(crc32($u['name']))%count($colors)];
                            $uMB=round($u['bytes']/1024/1024,1);
                            $uPct=min(100,round($u['bytes']/(1024*1024*1024)*100,1));
                            $lastLogin = $u['last_login'] ? date('M j, Y g:i A', strtotime($u['last_login'])) : 'Never';
                            $isOnline  = $u['is_online'] == 1;
                        ?>
                        <tr id="ua-<?= $u['id'] ?>">
                            <td style="color:var(--muted);font-size:12px"><?= $i++ ?></td>
                            <td>
                                <div class="u-cell">
                                    <div class="u-av" style="background:<?= $c ?>;position:relative">
                                        <?= strtoupper(substr($u['name'],0,1)) ?>
                                        <?php if($isOnline): ?>
                                        <span style="position:absolute;bottom:-1px;right:-1px;width:10px;height:10px;background:#22c55e;border-radius:50%;border:2px solid var(--card)"></span>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <div class="u-nm"><?= htmlspecialchars($u['name']) ?></div>
                                        <div class="u-em"><?= htmlspecialchars($u['email']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><span class="role-badge <?= $u['role']==='admin'?'rb-admin':'rb-student' ?>" id="rba-<?= $u['id'] ?>"><?= $u['role']==='admin'?'🛡️ Admin':'👤 Student' ?></span></td>
                            <td>
                                <?php if($isOnline): ?>
                                <span style="display:inline-flex;align-items:center;gap:5px;background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.25);border-radius:20px;padding:3px 10px;font-size:11.5px;font-weight:700;color:#16a34a">
                                    <span style="width:6px;height:6px;background:#22c55e;border-radius:50%;animation:pulse 2s infinite;display:inline-block"></span> Online
                                </span>
                                <?php else: ?>
                                <span style="display:inline-flex;align-items:center;gap:5px;background:var(--surface);border:1px solid var(--border);border-radius:20px;padding:3px 10px;font-size:11.5px;font-weight:700;color:var(--muted)">
                                    <span style="width:6px;height:6px;background:var(--muted);border-radius:50%;display:inline-block"></span> Offline
                                </span>
                                <?php endif; ?>
                            </td>
                            <td style="font-size:13px;color:var(--text-3)">📄 <?= $u['nc'] ?></td>
                            <td>
                                <div style="font-size:12px;color:var(--text-3);margin-bottom:3px"><?= $uMB ?> MB (<?= $uPct ?>%)</div>
                                <div style="height:4px;background:var(--border);border-radius:4px;width:90px;overflow:hidden"><div style="height:100%;width:<?= $uPct ?>%;background:<?= $uPct>85?'var(--danger)':'var(--accent)' ?>;border-radius:4px;transition:width .6s"></div></div>
                            </td>
                            <td style="font-size:12px;color:var(--text-3)"><?= $lastLogin ?></td>
                            <td style="font-size:12px;color:var(--muted)"><?= date('M j, Y',strtotime($u['created_at'])) ?></td>
                            <td>
                                <?php if($u['id']!=$_SESSION['user_id']): ?>
                                <div class="acts">
                                    <?php if($u['role']==='student'): ?>
                                    <button class="act act-promote" onclick="changeRole(<?=$u['id']?>,'admin','<?=addslashes($u['name'])?>')">⬆ Promote</button>
                                    <?php else: ?>
                                    <button class="act act-demote" onclick="changeRole(<?=$u['id']?>,'student','<?=addslashes($u['name'])?>')">⬇ Demote</button>
                                    <?php endif; ?>
                                    <button class="act act-delete" onclick="confirmDel(<?=$u['id']?>,'<?=addslashes($u['name'])?>')">🗑 Delete</button>
                                </div>
                                <?php else: ?><span style="font-size:12px;color:var(--muted);font-style:italic">— (You)</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ══ NOTES ══ -->
            <div id="tab-notes" class="sec-hidden">
                <div class="tbl-wrap">
                    <div class="tbl-header">
                        <div class="tbl-title">📄 All Notes <span style="font-weight:400;color:var(--muted);font-size:13px">(<?= $totalNotes ?> total)</span></div>
                        <input type="text" class="tbl-search" placeholder="🔍 Search notes..." oninput="filterTbl('ntbody',this.value,[1,2])">
                    </div>
                    <table><thead><tr><th>#</th><th>Title</th><th>Subject</th><th>Uploaded By</th><th>Size</th><th>Downloads</th><th>Date</th><th>Actions</th></tr></thead>
                    <tbody id="ntbody">
                        <?php $notesResult->data_seek(0); $j=1; while($n=$notesResult->fetch_assoc()):
                            $ext=strtolower(pathinfo($n['file_name'],PATHINFO_EXTENSION));
                            $nkb=round(($n['file_size']??0)/1024,1);
                        ?>
                        <tr id="nr-<?= $n['id'] ?>">
                            <td style="color:var(--muted);font-size:12px"><?= $j++ ?></td>
                            <td><div class="note-name-cell" title="<?= htmlspecialchars($n['title']) ?>"><?= htmlspecialchars($n['title']) ?></div></td>
                            <td><span class="badge badge-purple"><?= htmlspecialchars($n['subject']) ?></span><?php if($n['category']): ?><br><span class="badge badge-teal" style="margin-top:3px"><?= htmlspecialchars($n['category']) ?></span><?php endif; ?></td>
                            <td style="font-size:13px;font-weight:600;color:var(--text-2)"><?= htmlspecialchars($n['uploader']) ?></td>
                            <td class="storage-cell"><span class="file-ext"><?= strtoupper($ext) ?></span> <?= $nkb ?> KB</td>
                            <td style="font-size:13px;color:var(--text-3)">📥 <?= $n['downloads'] ?></td>
                            <td style="font-size:12px;color:var(--muted)"><?= date('M j, Y',strtotime($n['upload_date'])) ?></td>
                            <td>
                                <div class="acts">
                                    <a href="api/downloadAPI.php?id=<?= $n['id'] ?>" class="act act-dl">📥 Download</a>
                                    <button class="act act-delete" onclick="deleteNote(<?=$n['id']?>,'<?=addslashes($n['title'])?>')">🗑 Delete</button>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php if($totalNotes==0): ?><tr class="empty-row"><td colspan="8">📭 No notes yet.</td></tr><?php endif; ?>
                    </tbody></table>
                </div>
            </div>

            <!-- ══ CATEGORIES ══ -->
            <div id="tab-cats" class="sec-hidden">
                <div class="tbl-wrap">
                    <div class="tbl-header"><div class="tbl-title">🏷️ Categories</div></div>
                    <table><thead><tr><th>#</th><th>Category Name</th><th>Notes Count</th></tr></thead>
                    <tbody><?php $k=1;while($cat=$catsResult->fetch_assoc()): ?>
                        <tr><td style="color:var(--muted);font-size:12px"><?= $k++ ?></td><td style="font-weight:700;color:var(--text)"><?= htmlspecialchars($cat['name']) ?></td><td><span class="badge badge-purple">📄 <?= $cat['nc'] ?></span></td></tr>
                    <?php endwhile; ?></tbody>
                </table></div>
            </div>
        </div>
    </div>
</div>

<!-- Delete User Modal -->
<div class="modal-bg" id="delModal">
    <div class="modal">
        <div class="modal-title">🗑️ Delete User</div>
        <div class="modal-body" id="delModalBody">Are you sure?</div>
        <div class="modal-btns">
            <button class="btn btn-secondary" onclick="document.getElementById('delModal').classList.remove('show')">Cancel</button>
            <button class="btn btn-danger" id="delModalBtn">Yes, Delete</button>
        </div>
    </div>
</div>
<div class="toast" id="sv-toast"></div>

<script>
// Tabs
function showTab(name, btn) {
    ['overview','users','notes','cats'].forEach(t=>{
        document.getElementById('tab-'+t).classList.toggle('sec-hidden', t!==name);
    });
    document.querySelectorAll('.tab-btn').forEach(b=>b.classList.remove('active'));
    btn.classList.add('active');
}

// Toggle Create User Form
function toggleCreateForm() {
    const f = document.getElementById('createUserForm');
    f.style.display = f.style.display === 'none' ? 'block' : 'none';
    if (f.style.display === 'block') {
        document.getElementById('cu_name').focus();
        document.getElementById('cuMsg').textContent = '';
    }
}

// Create User (Admin)
function createUser() {
    const name  = document.getElementById('cu_name').value.trim();
    const email = document.getElementById('cu_email').value.trim();
    const pass  = document.getElementById('cu_pass').value;
    const role  = document.getElementById('cu_role').value;
    const btn   = document.getElementById('cuBtn');
    const msg   = document.getElementById('cuMsg');

    if (!name || !email || !pass) { msg.style.color='var(--danger)'; msg.textContent='⚠️ All fields are required.'; return; }
    if (pass.length < 6) { msg.style.color='var(--danger)'; msg.textContent='⚠️ Password must be at least 6 characters.'; return; }

    btn.disabled = true; btn.textContent = 'Creating...';
    msg.textContent = '';

    fetch('api/createUserAPI.php', {
        method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({name, email, password: pass, role})
    })
    .then(r=>r.json())
    .then(d=>{
        if (d.status === 'success') {
            msg.style.color = 'var(--accent-3)';
            msg.textContent = '✅ ' + d.message;
            document.getElementById('cu_name').value = '';
            document.getElementById('cu_email').value = '';
            document.getElementById('cu_pass').value = '';
            document.getElementById('cu_role').value = 'student';
            showToast(d.message);
            setTimeout(() => location.reload(), 1400);
        } else {
            msg.style.color = 'var(--danger)';
            msg.textContent = '❌ ' + (d.message || 'Failed to create user.');
            btn.disabled = false; btn.textContent = '✅ Create Account';
        }
    })
    .catch(()=>{ msg.style.color='var(--danger)'; msg.textContent='❌ Server error.'; btn.disabled=false; btn.textContent='✅ Create Account'; });
}

// Table Filter
function filterTbl(tbodyId, q, cols) {
    q=q.toLowerCase();
    document.querySelectorAll('#'+tbodyId+' tr').forEach(r=>{
        const txt=cols.map(i=>r.cells[i]?r.cells[i].textContent:'').join(' ').toLowerCase();
        r.style.display=txt.includes(q)?'':'none';
    });
}

// Change Role
function changeRole(id, role, name) {
    if(!confirm(`Change ${name}'s role to ${role}?`)) return;
    fetch('api/updateRoleAPI.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id,role})})
    .then(r=>r.json()).then(d=>{
        if(d.status==='success'){showToast('Role updated to '+role);setTimeout(()=>location.reload(),1200);}
        else showToast(d.message||'Failed','error');
    }).catch(()=>showToast('Server error','error'));
}

// Delete User
let pendingDelId=null;
function confirmDel(id, name) {
    pendingDelId=id;
    document.getElementById('delModalBody').textContent=`Delete "${name}"? All their notes and files will be permanently removed.`;
    document.getElementById('delModal').classList.add('show');
    const btn=document.getElementById('delModalBtn');
    btn.textContent='Yes, Delete'; btn.disabled=false;
    btn.onclick=function(){
        btn.textContent='Deleting...'; btn.disabled=true;
        fetch('api/deleteUserAPI.php',{method:'DELETE',headers:{'Content-Type':'application/json'},body:JSON.stringify({id:pendingDelId})})
        .then(r=>r.json()).then(d=>{
            document.getElementById('delModal').classList.remove('show');
            if(d.status==='success'){
                showToast('User deleted.');
                ['ur-','ua-'].forEach(pfx=>{const el=document.getElementById(pfx+pendingDelId);if(el){el.style.opacity='0';el.style.transition='opacity .3s';setTimeout(()=>el.remove(),300);}});
            } else showToast(d.message||'Failed','error');
        }).catch(()=>{showToast('Server error','error');document.getElementById('delModal').classList.remove('show');});
    };
}

// Delete Note
function deleteNote(id, title) {
    if(!confirm(`Delete note "${title}"? Cannot be undone.`)) return;
    fetch('api/deleteNoteAPI.php',{method:'DELETE',headers:{'Content-Type':'application/json'},body:JSON.stringify({id})})
    .then(r=>r.json()).then(d=>{
        if(d.status==='success'){showToast('Note deleted.');const row=document.getElementById('nr-'+id);if(row){row.style.opacity='0';row.style.transition='opacity .3s';setTimeout(()=>row.remove(),300);}}
        else showToast(d.message||'Failed','error');
    }).catch(()=>showToast('Server error','error'));
}

// URL param tab
const urlTab=new URLSearchParams(location.search).get('tab');
if(urlTab){const btn=document.querySelector(`[onclick*="'${urlTab}'"]`);if(btn)btn.click();}
</script>
</body>
</html>
