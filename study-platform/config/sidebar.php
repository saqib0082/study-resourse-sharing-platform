<?php
/**
 * StudyVault — Shared Sidebar
 * Requires these vars from including page:
 *   $activePage  — string: 'dashboard'|'notes'|'upload'|'admin'|'search'|'mine'
 *   $userName    — string
 *   $userRole    — string: 'student'|'admin'
 *   $isAdmin     — bool
 */
?>
<aside class="sidebar">
    <!-- Logo -->
    <div class="sidebar-logo">
        <div class="logo-mark">📚</div>
        <span class="logo-text">StudyVault</span>
        <?php if ($isAdmin): ?>
        <span style="margin-left:auto;background:rgba(8,145,178,.12);color:var(--accent-2);border:1px solid rgba(8,145,178,.25);border-radius:6px;padding:2px 8px;font-size:10px;font-weight:800;letter-spacing:.5px">ADMIN</span>
        <?php endif; ?>
    </div>

    <!-- Main Nav -->
    <div class="nav-section">Main</div>
    <a href="dashboard.php" class="nav-item <?= $activePage==='dashboard'?'active':'' ?>">
        <span class="ni">🏠</span> Dashboard
    </a>
    <a href="notes.php" class="nav-item <?= $activePage==='notes'?'active':'' ?>">
        <span class="ni">📄</span> Notes Library
    </a>
    <a href="upload.php" class="nav-item <?= $activePage==='upload'?'active':'' ?>">
        <span class="ni">⬆️</span> Upload Notes
    </a>

    <!-- Explore Nav -->
    <div class="nav-section">Explore</div>
    <a href="notes.php?search=1" class="nav-item <?= $activePage==='search'?'active':'' ?>">
        <span class="ni">🔍</span> Search Notes
    </a>
    <a href="notes.php?filter=mine" class="nav-item <?= $activePage==='mine'?'active':'' ?>">
        <span class="ni">📁</span> My Uploads
    </a>

    <?php if ($isAdmin): ?>
    <!-- Admin Nav -->
    <div class="nav-section">Admin</div>
    <a href="admin.php" class="nav-item admin-nav <?= $activePage==='admin'?'active':'' ?>">
        <span class="ni">🛡️</span> Admin Panel
    </a>
    <a href="admin.php?tab=users" class="nav-item admin-nav">
        <span class="ni">👥</span> Manage Users
    </a>
    <a href="admin.php?tab=notes" class="nav-item admin-nav">
        <span class="ni">📋</span> Content Moderation
    </a>
    <?php endif; ?>

    <!-- Bottom: User + Logout -->
    <div class="sidebar-bottom">
        <div class="user-chip">
            <div class="user-avatar"><?= strtoupper(substr($userName,0,1)) ?></div>
            <div class="user-info">
                <div class="user-name"><?= htmlspecialchars($userName) ?></div>
                <div class="user-role-badge <?= $isAdmin?'admin':'' ?>"><?= $isAdmin ? '🛡️ Administrator' : '👤 Student' ?></div>
            </div>
        </div>
        <a href="api/logoutAPI.php" class="btn-logout">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
            Logout
        </a>
        <button class="theme-toggle" onclick="toggleTheme()" style="width:100%;border-radius:var(--radius-sm);height:36px">🌙</button>
    </div>
</aside>
