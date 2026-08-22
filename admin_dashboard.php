<?php
/**
 * Admin Dashboard - Panel Manajemen Pengguna & Reset Password
 * Website Absensi Sekolah (QR Code)
 */
require_once __DIR__ . '/includes/auth.php';
requireRole('admin');

$currentUser = getCurrentUser();
$pdo = getDBConnection();
autoInitializeDatabase($pdo);

// Fetch stats summary
$totalUsers = $pdo->query("SELECT COUNT(*) FROM `users`")->fetchColumn();
$totalGuru = $pdo->query("SELECT COUNT(*) FROM `users` WHERE `role` = 'guru'")->fetchColumn();
$totalSiswa = $pdo->query("SELECT COUNT(*) FROM `users` WHERE `role` = 'siswa'")->fetchColumn();
$totalAdmin = $pdo->query("SELECT COUNT(*) FROM `users` WHERE `role` = 'admin'")->fetchColumn();

// Fetch available classes for filter/options
$stmtClasses = $pdo->query("SELECT DISTINCT `class_name` FROM `users` WHERE `class_name` IS NOT NULL AND `class_name` != '' ORDER BY `class_name` ASC");
$availableClasses = $stmtClasses->fetchAll(PDO::FETCH_COLUMN);
$availableClasses = array_values(array_diff($availableClasses, ['10 IPA 1', '10 IPA 2']));
if (empty($availableClasses)) {
    $availableClasses = ['X TKR', 'X KKR', 'XI TKR', 'XI BDPO', 'XII TKR', 'XII BDPO'];
}

$pageTitle = 'Dashboard Admin - EduScanQR';
include __DIR__ . '/includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

    <!-- Welcome Hero Banner -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 text-white p-8 shadow-xl border border-slate-800">
        <div class="absolute top-0 right-0 -mr-12 -mt-12 w-64 h-64 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="space-y-2">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/20 border border-emerald-400/30 text-emerald-300 text-xs font-semibold">
                    <i data-lucide="shield-check" class="w-4 h-4 text-emerald-400"></i>
                    Panel Administrator
                </div>
                <h1 class="text-3xl font-extrabold tracking-tight">Manajemen Pengguna & Password</h1>
                <p class="text-slate-300 text-sm max-w-xl">Kelola akun guru, siswa, dan administrator. Anda dapat mereset password, mengedit profil, serta menambah atau menghapus akun.</p>
            </div>
            <div>
                <button onclick="openAddUserModal()" class="px-6 py-3.5 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-bold rounded-2xl shadow-lg shadow-emerald-600/30 transition transform hover:-translate-y-0.5 flex items-center gap-2 text-sm">
                    <i data-lucide="user-plus" class="w-5 h-5"></i>
                    Tambah Pengguna Baru
                </button>
            </div>
        </div>
    </div>

    <!-- Quick Stats Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-5">
        <div class="glass-card p-5 rounded-3xl border border-slate-200/80 flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-blue-100 text-blue-600 flex items-center justify-center flex-shrink-0">
                <i data-lucide="users" class="w-6 h-6"></i>
            </div>
            <div>
                <div class="text-2xl font-extrabold text-slate-900"><?= number_format($totalUsers) ?></div>
                <div class="text-xs text-slate-500 font-medium">Total Akun</div>
            </div>
        </div>

        <div class="glass-card p-5 rounded-3xl border border-slate-200/80 flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-purple-100 text-purple-600 flex items-center justify-center flex-shrink-0">
                <i data-lucide="user-check" class="w-6 h-6"></i>
            </div>
            <div>
                <div class="text-2xl font-extrabold text-slate-900"><?= number_format($totalGuru) ?></div>
                <div class="text-xs text-slate-500 font-medium">Guru / Pengajar</div>
            </div>
        </div>

        <div class="glass-card p-5 rounded-3xl border border-slate-200/80 flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-indigo-100 text-indigo-600 flex items-center justify-center flex-shrink-0">
                <i data-lucide="graduation-cap" class="w-6 h-6"></i>
            </div>
            <div>
                <div class="text-2xl font-extrabold text-slate-900"><?= number_format($totalSiswa) ?></div>
                <div class="text-xs text-slate-500 font-medium">Siswa Terdaftar</div>
            </div>
        </div>

        <div class="glass-card p-5 rounded-3xl border border-slate-200/80 flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-emerald-100 text-emerald-600 flex items-center justify-center flex-shrink-0">
                <i data-lucide="shield" class="w-6 h-6"></i>
            </div>
            <div>
                <div class="text-2xl font-extrabold text-slate-900"><?= number_format($totalAdmin) ?></div>
                <div class="text-xs text-slate-500 font-medium">Administrator</div>
            </div>
        </div>
    </div>

    <!-- Main Table Container -->
    <div class="glass-card rounded-3xl border border-slate-200/80 overflow-hidden shadow-sm space-y-4">
        
        <!-- Filter & Search Toolbar -->
        <div class="p-6 border-b border-slate-200/80 space-y-4">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                        <i data-lucide="list-tree" class="w-5 h-5 text-emerald-600"></i>
                        Daftar Pengguna Sistem
                    </h3>
                    <p class="text-xs text-slate-500">Cari pengguna dan gunakan tombol <b>🔑 Reset Password</b> untuk mengubah kata sandi</p>
                </div>

                <div class="flex items-center gap-3 flex-wrap">
                    <!-- Search Input -->
                    <div class="relative min-w-[240px]">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                            <i data-lucide="search" class="w-4 h-4"></i>
                        </div>
                        <input type="text" id="searchInput" onkeyup="debounceLoadUsers()" placeholder="Cari nama, NIP/NIS, email..." class="w-full pl-9 pr-4 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-xs font-medium focus:outline-none focus:border-emerald-500 focus:bg-white transition">
                    </div>

                    <!-- Role Filter -->
                    <select id="roleFilter" onchange="loadUsers()" class="px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-xs font-bold focus:outline-none focus:border-emerald-500">
                        <option value="">Semua Role</option>
                        <option value="guru">Guru</option>
                        <option value="siswa">Siswa</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Table View -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-100/70 text-slate-500 text-xs font-bold uppercase tracking-wider">
                        <th class="py-3.5 px-6">Pengguna</th>
                        <th class="py-3.5 px-6">NIP / NIS</th>
                        <th class="py-3.5 px-6">Email</th>
                        <th class="py-3.5 px-6 text-center">Hak Akses</th>
                        <th class="py-3.5 px-6">Kelas</th>
                        <th class="py-3.5 px-6 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody id="userTableBody" class="divide-y divide-slate-200/80 text-sm">
                    <!-- Loaded via JavaScript -->
                </tbody>
            </table>
        </div>

    </div>

</div>

<!-- Modal Reset / Edit Password -->
<div id="resetPasswordModal" class="fixed inset-0 z-50 hidden bg-slate-950/70 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-100 relative animate-in fade-in zoom-in duration-200">
        <button onclick="closeResetPasswordModal()" class="absolute top-5 right-5 text-slate-400 hover:text-slate-600 p-1">
            <i data-lucide="x" class="w-5 h-5"></i>
        </button>

        <div class="flex items-center gap-3 mb-6">
            <div class="w-11 h-11 rounded-2xl bg-amber-100 text-amber-600 flex items-center justify-center flex-shrink-0">
                <i data-lucide="key-round" class="w-6 h-6"></i>
            </div>
            <div>
                <h3 class="text-lg font-bold text-slate-900">Reset Password Pengguna</h3>
                <p class="text-xs text-slate-500" id="resetUserSubtitle">Ubah password akun pengguna</p>
            </div>
        </div>

        <form id="resetPasswordForm" onsubmit="handleResetPassword(event)" class="space-y-4">
            <input type="hidden" id="resetUserId">

            <div class="p-3.5 bg-slate-50 rounded-2xl border border-slate-200/80 space-y-1">
                <div class="text-[11px] font-bold text-slate-400 uppercase">Target Pengguna</div>
                <div id="resetUserName" class="font-extrabold text-slate-900 text-sm"></div>
                <div id="resetUserMeta" class="text-xs text-slate-500 font-mono"></div>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Password Baru <span class="text-red-500">*</span></label>
                <div class="relative">
                    <input type="password" id="newPasswordInput" required placeholder="Masukkan password baru..." class="w-full pl-4 pr-10 py-3 rounded-2xl bg-slate-50 border border-slate-200 text-sm focus:outline-none focus:border-emerald-500">
                    <button type="button" onclick="toggleModalPass('newPasswordInput')" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600">
                        <i data-lucide="eye" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>

            <!-- Quick Password Presets -->
            <div class="space-y-1.5 pt-1">
                <div class="text-[11px] font-bold uppercase text-slate-500">Quick Set (Preset Bawaan):</div>
                <div class="flex items-center gap-2 flex-wrap">
                    <button type="button" onclick="setQuickPass('guru123')" class="px-3 py-1.5 rounded-xl bg-purple-50 hover:bg-purple-100 text-purple-700 font-bold text-xs border border-purple-200 transition">guru123</button>
                    <button type="button" onclick="setQuickPass('siswa123')" class="px-3 py-1.5 rounded-xl bg-blue-50 hover:bg-blue-100 text-blue-700 font-bold text-xs border border-blue-200 transition">siswa123</button>
                    <button type="button" onclick="setQuickPass('admin123')" class="px-3 py-1.5 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-bold text-xs border border-emerald-200 transition">admin123</button>
                </div>
            </div>

            <div class="pt-4 flex gap-3">
                <button type="button" onclick="closeResetPasswordModal()" class="flex-1 py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-2xl text-xs transition">Batal</button>
                <button type="submit" id="btnSubmitReset" class="flex-1 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-2xl text-xs shadow-md shadow-emerald-500/20 transition flex items-center justify-center gap-2">
                    <i data-lucide="check" class="w-4 h-4"></i>
                    <span>Simpan Password</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Data User -->
<div id="editUserModal" class="fixed inset-0 z-50 hidden bg-slate-950/70 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-100 relative animate-in fade-in zoom-in duration-200">
        <button onclick="closeEditUserModal()" class="absolute top-5 right-5 text-slate-400 hover:text-slate-600 p-1">
            <i data-lucide="x" class="w-5 h-5"></i>
        </button>

        <div class="flex items-center gap-3 mb-6">
            <div class="w-11 h-11 rounded-2xl bg-blue-100 text-blue-600 flex items-center justify-center flex-shrink-0">
                <i data-lucide="user-cog" class="w-6 h-6"></i>
            </div>
            <div>
                <h3 class="text-lg font-bold text-slate-900">Edit Data Pengguna</h3>
                <p class="text-xs text-slate-500">Perbarui identitas, email, dan role pengguna</p>
            </div>
        </div>

        <form id="editUserForm" onsubmit="handleSaveEditUser(event)" class="space-y-4">
            <input type="hidden" id="editUserId">

            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Nama Lengkap <span class="text-red-500">*</span></label>
                <input type="text" id="editName" required class="w-full px-4 py-2.5 rounded-2xl bg-slate-50 border border-slate-200 text-sm focus:outline-none focus:border-emerald-500">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-600 mb-1">NIP / NIS <span class="text-red-500">*</span></label>
                    <input type="text" id="editIdentityNumber" required class="w-full px-4 py-2.5 rounded-2xl bg-slate-50 border border-slate-200 text-sm focus:outline-none focus:border-emerald-500">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Email <span class="text-red-500">*</span></label>
                    <input type="email" id="editEmail" required class="w-full px-4 py-2.5 rounded-2xl bg-slate-50 border border-slate-200 text-sm focus:outline-none focus:border-emerald-500">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Role / Hak Akses <span class="text-red-500">*</span></label>
                    <select id="editRole" onchange="toggleEditClassInput()" required class="w-full px-4 py-2.5 rounded-2xl bg-slate-50 border border-slate-200 text-sm focus:outline-none focus:border-emerald-500">
                        <option value="siswa">Siswa</option>
                        <option value="guru">Guru</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div id="editClassBox">
                    <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Kelas (Siswa)</label>
                    <select id="editClassName" class="w-full px-4 py-2.5 rounded-2xl bg-slate-50 border border-slate-200 text-sm focus:outline-none focus:border-emerald-500">
                        <option value="">-- Pilih Kelas --</option>
                        <?php foreach ($availableClasses as $cls): ?>
                            <option value="<?= htmlspecialchars($cls) ?>"><?= htmlspecialchars($cls) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="pt-4 flex gap-3">
                <button type="button" onclick="closeEditUserModal()" class="flex-1 py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-2xl text-xs transition">Batal</button>
                <button type="submit" id="btnSubmitEdit" class="flex-1 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-2xl text-xs shadow-md shadow-emerald-500/20 transition flex items-center justify-center gap-2">
                    <i data-lucide="check" class="w-4 h-4"></i>
                    <span>Simpan Perubahan</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Tambah User Baru -->
<div id="addUserModal" class="fixed inset-0 z-50 hidden bg-slate-950/70 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-100 relative animate-in fade-in zoom-in duration-200">
        <button onclick="closeAddUserModal()" class="absolute top-5 right-5 text-slate-400 hover:text-slate-600 p-1">
            <i data-lucide="x" class="w-5 h-5"></i>
        </button>

        <div class="flex items-center gap-3 mb-6">
            <div class="w-11 h-11 rounded-2xl bg-emerald-100 text-emerald-600 flex items-center justify-center flex-shrink-0">
                <i data-lucide="user-plus" class="w-6 h-6"></i>
            </div>
            <div>
                <h3 class="text-lg font-bold text-slate-900">Tambah Pengguna Baru</h3>
                <p class="text-xs text-slate-500">Buat akun guru, siswa, atau administrator baru</p>
            </div>
        </div>

        <form id="addUserForm" onsubmit="handleCreateUser(event)" class="space-y-4">
            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Nama Lengkap <span class="text-red-500">*</span></label>
                <input type="text" id="addName" required placeholder="Contoh: Ahmad Subagyo, S.Pd." class="w-full px-4 py-2.5 rounded-2xl bg-slate-50 border border-slate-200 text-sm focus:outline-none focus:border-emerald-500">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-600 mb-1">NIP / NIS <span class="text-red-500">*</span></label>
                    <input type="text" id="addIdentityNumber" required placeholder="NIP/NIS Unik" class="w-full px-4 py-2.5 rounded-2xl bg-slate-50 border border-slate-200 text-sm focus:outline-none focus:border-emerald-500">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Email <span class="text-red-500">*</span></label>
                    <input type="email" id="addEmail" required placeholder="email@sekolah.sch.id" class="w-full px-4 py-2.5 rounded-2xl bg-slate-50 border border-slate-200 text-sm focus:outline-none focus:border-emerald-500">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Password Awal <span class="text-red-500">*</span></label>
                <input type="text" id="addPassword" required value="siswa123" placeholder="Password untuk login" class="w-full px-4 py-2.5 rounded-2xl bg-slate-50 border border-slate-200 text-sm font-mono focus:outline-none focus:border-emerald-500">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Role / Hak Akses <span class="text-red-500">*</span></label>
                    <select id="addRole" onchange="toggleAddClassInput()" required class="w-full px-4 py-2.5 rounded-2xl bg-slate-50 border border-slate-200 text-sm focus:outline-none focus:border-emerald-500">
                        <option value="siswa" selected>Siswa</option>
                        <option value="guru">Guru</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div id="addClassBox">
                    <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Kelas (Siswa)</label>
                    <select id="addClassName" class="w-full px-4 py-2.5 rounded-2xl bg-slate-50 border border-slate-200 text-sm focus:outline-none focus:border-emerald-500">
                        <option value="">-- Pilih Kelas --</option>
                        <?php foreach ($availableClasses as $cls): ?>
                            <option value="<?= htmlspecialchars($cls) ?>"><?= htmlspecialchars($cls) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="pt-4 flex gap-3">
                <button type="button" onclick="closeAddUserModal()" class="flex-1 py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-2xl text-xs transition">Batal</button>
                <button type="submit" id="btnSubmitAdd" class="flex-1 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-2xl text-xs shadow-md shadow-emerald-500/20 transition flex items-center justify-center gap-2">
                    <i data-lucide="user-plus" class="w-4 h-4"></i>
                    <span>Tambah Akun</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    let cachedUsersList = [];
    let debounceTimer = null;

    document.addEventListener('DOMContentLoaded', () => {
        loadUsers();
    });

    function debounceLoadUsers() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            loadUsers();
        }, 300);
    }

    async function loadUsers() {
        const search = document.getElementById('searchInput').value.trim();
        const role = document.getElementById('roleFilter').value;
        const tbody = document.getElementById('userTableBody');

        tbody.innerHTML = `<tr><td colspan="6" class="py-8 text-center text-slate-400"><i data-lucide="loader-2" class="w-5 h-5 animate-spin mx-auto mb-2 text-emerald-500"></i>Memuat data pengguna...</td></tr>`;
        lucide.createIcons();

        try {
            const resp = await fetch(`api/admin_user_manage.php?action=get_users&search=${encodeURIComponent(search)}&role=${encodeURIComponent(role)}`);
            const result = await resp.json();

            if (!result.success) {
                tbody.innerHTML = `<tr><td colspan="6" class="py-6 text-center text-red-500 font-semibold">${result.message}</td></tr>`;
                return;
            }

            cachedUsersList = result.data;

            if (cachedUsersList.length === 0) {
                tbody.innerHTML = `<tr><td colspan="6" class="py-8 text-center text-slate-400 text-xs">Tidak ada data pengguna ditemukan.</td></tr>`;
                return;
            }

            tbody.innerHTML = cachedUsersList.map(u => {
                let roleBadge = '';
                if (u.role === 'admin') {
                    roleBadge = `<span class="px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-800 border border-emerald-200 text-[10px] font-extrabold uppercase tracking-wider">Admin</span>`;
                } else if (u.role === 'guru') {
                    roleBadge = `<span class="px-2.5 py-1 rounded-full bg-purple-100 text-purple-800 border border-purple-200 text-[10px] font-extrabold uppercase tracking-wider">Guru</span>`;
                } else {
                    roleBadge = `<span class="px-2.5 py-1 rounded-full bg-blue-100 text-blue-800 border border-blue-200 text-[10px] font-extrabold uppercase tracking-wider">Siswa</span>`;
                }

                return `
                    <tr class="hover:bg-slate-50/80 transition">
                        <td class="py-3.5 px-6">
                            <div class="flex items-center gap-3">
                                <img src="${escapeHtml(u.avatar || 'assets/photo/pfp.jpg')}" class="w-9 h-9 rounded-xl object-cover border border-slate-200 shadow-sm flex-shrink-0">
                                <div>
                                    <div class="font-bold text-slate-900 leading-tight">${escapeHtml(u.name)}</div>
                                    <div class="text-[11px] text-slate-400">ID #${u.id}</div>
                                </div>
                            </div>
                        </td>
                        <td class="py-3.5 px-6 font-mono text-xs font-semibold text-slate-700">${escapeHtml(u.identity_number)}</td>
                        <td class="py-3.5 px-6 text-xs text-slate-600">${escapeHtml(u.email)}</td>
                        <td class="py-3.5 px-6 text-center">${roleBadge}</td>
                        <td class="py-3.5 px-6 text-xs font-bold text-slate-700">${escapeHtml(u.class_name || '-')}</td>
                        <td class="py-3.5 px-6 text-right">
                            <div class="flex items-center justify-end gap-1.5">
                                <button onclick="openResetPasswordModal(${u.id})" class="px-2.5 py-1.5 rounded-xl bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 font-bold text-xs transition flex items-center gap-1" title="Reset / Ubah Password">
                                    <i data-lucide="key" class="w-3.5 h-3.5"></i> Reset Pass
                                </button>
                                <button onclick="openEditUserModal(${u.id})" class="p-1.5 rounded-xl bg-slate-100 hover:bg-blue-50 text-slate-600 hover:text-blue-600 transition" title="Edit Data">
                                    <i data-lucide="edit-3" class="w-4 h-4"></i>
                                </button>
                                <button onclick="confirmDeleteUser(${u.id}, '${escapeJs(u.name)}')" class="p-1.5 rounded-xl bg-slate-100 hover:bg-red-50 text-slate-600 hover:text-red-600 transition" title="Hapus User">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');

            lucide.createIcons();
        } catch (err) {
            tbody.innerHTML = `<tr><td colspan="6" class="py-6 text-center text-red-500 font-semibold">Gagal memuat data dari server.</td></tr>`;
        }
    }

    // Modal Reset Password Functions
    function openResetPasswordModal(userId) {
        const u = cachedUsersList.find(x => x.id == userId);
        if (!u) return;

        document.getElementById('resetUserId').value = u.id;
        document.getElementById('resetUserName').innerText = u.name;
        document.getElementById('resetUserMeta').innerText = `Role: ${u.role.toUpperCase()} | NIP/NIS: ${u.identity_number} | ${u.email}`;
        document.getElementById('newPasswordInput').value = '';
        document.getElementById('resetPasswordModal').classList.remove('hidden');
    }

    function closeResetPasswordModal() {
        document.getElementById('resetPasswordModal').classList.add('hidden');
    }

    function setQuickPass(val) {
        document.getElementById('newPasswordInput').value = val;
    }

    function toggleModalPass(inputId) {
        const input = document.getElementById(inputId);
        input.type = (input.type === 'password') ? 'text' : 'password';
    }

    async function handleResetPassword(e) {
        e.preventDefault();
        const userId = document.getElementById('resetUserId').value;
        const newPassword = document.getElementById('newPasswordInput').value;
        const btn = document.getElementById('btnSubmitReset');

        if (!newPassword || newPassword.length < 4) {
            alert('Password minimal 4 karakter.');
            return;
        }

        btn.disabled = true;
        btn.innerText = "Memproses...";

        try {
            const formData = new FormData();
            formData.append('action', 'reset_password');
            formData.append('user_id', userId);
            formData.append('new_password', newPassword);

            const resp = await fetch('api/admin_user_manage.php', {
                method: 'POST',
                body: formData
            });
            const result = await resp.json();

            if (result.success) {
                alert(result.message);
                closeResetPasswordModal();
            } else {
                alert(result.message || 'Gagal mereset password.');
            }
        } catch (err) {
            alert('Terjadi kesalahan jaringan.');
        } finally {
            btn.disabled = false;
            btn.innerHTML = `<i data-lucide="check" class="w-4 h-4"></i> <span>Simpan Password</span>`;
            lucide.createIcons();
        }
    }

    // Modal Edit User Functions
    function openEditUserModal(userId) {
        const u = cachedUsersList.find(x => x.id == userId);
        if (!u) return;

        document.getElementById('editUserId').value = u.id;
        document.getElementById('editName').value = u.name;
        document.getElementById('editIdentityNumber').value = u.identity_number;
        document.getElementById('editEmail').value = u.email;
        document.getElementById('editRole').value = u.role;
        document.getElementById('editClassName').value = u.class_name || '';

        toggleEditClassInput();
        document.getElementById('editUserModal').classList.remove('hidden');
    }

    function closeEditUserModal() {
        document.getElementById('editUserModal').classList.add('hidden');
    }

    function toggleEditClassInput() {
        const role = document.getElementById('editRole').value;
        const box = document.getElementById('editClassBox');
        if (role === 'siswa') {
            box.classList.remove('hidden');
        } else {
            box.classList.add('hidden');
        }
    }

    async function handleSaveEditUser(e) {
        e.preventDefault();
        const btn = document.getElementById('btnSubmitEdit');
        btn.disabled = true;
        btn.innerText = "Menyimpan...";

        const formData = new FormData();
        formData.append('action', 'update_user');
        formData.append('user_id', document.getElementById('editUserId').value);
        formData.append('name', document.getElementById('editName').value);
        formData.append('identity_number', document.getElementById('editIdentityNumber').value);
        formData.append('email', document.getElementById('editEmail').value);
        formData.append('role', document.getElementById('editRole').value);
        formData.append('class_name', document.getElementById('editClassName').value);

        try {
            const resp = await fetch('api/admin_user_manage.php', {
                method: 'POST',
                body: formData
            });
            const result = await resp.json();

            if (result.success) {
                alert(result.message);
                closeEditUserModal();
                loadUsers();
            } else {
                alert(result.message || 'Gagal mengubah data.');
            }
        } catch (err) {
            alert('Terjadi kesalahan koneksi.');
        } finally {
            btn.disabled = false;
            btn.innerHTML = `<i data-lucide="check" class="w-4 h-4"></i> <span>Simpan Perubahan</span>`;
            lucide.createIcons();
        }
    }

    // Modal Add User Functions
    function openAddUserModal() {
        document.getElementById('addUserForm').reset();
        document.getElementById('addRole').value = 'siswa';
        toggleAddClassInput();
        document.getElementById('addUserModal').classList.remove('hidden');
    }

    function closeAddUserModal() {
        document.getElementById('addUserModal').classList.add('hidden');
    }

    function toggleAddClassInput() {
        const role = document.getElementById('addRole').value;
        const box = document.getElementById('addClassBox');
        if (role === 'siswa') {
            box.classList.remove('hidden');
        } else {
            box.classList.add('hidden');
        }
    }

    async function handleCreateUser(e) {
        e.preventDefault();
        const btn = document.getElementById('btnSubmitAdd');
        btn.disabled = true;
        btn.innerText = "Memproses...";

        const formData = new FormData();
        formData.append('action', 'add_user');
        formData.append('name', document.getElementById('addName').value);
        formData.append('identity_number', document.getElementById('addIdentityNumber').value);
        formData.append('email', document.getElementById('addEmail').value);
        formData.append('password', document.getElementById('addPassword').value);
        formData.append('role', document.getElementById('addRole').value);
        formData.append('class_name', document.getElementById('addClassName').value);

        try {
            const resp = await fetch('api/admin_user_manage.php', {
                method: 'POST',
                body: formData
            });
            const result = await resp.json();

            if (result.success) {
                alert(result.message);
                closeAddUserModal();
                loadUsers();
            } else {
                alert(result.message || 'Gagal menambahkan user.');
            }
        } catch (err) {
            alert('Terjadi kesalahan jaringan.');
        } finally {
            btn.disabled = false;
            btn.innerHTML = `<i data-lucide="user-plus" class="w-4 h-4"></i> <span>Tambah Akun</span>`;
            lucide.createIcons();
        }
    }

    // Delete User Function
    async function confirmDeleteUser(userId, userName) {
        if (!confirm(`Apakah Anda yakin ingin menghapus akun user "${userName}"? Data absensi terkait user ini juga akan terhapus.`)) {
            return;
        }

        try {
            const formData = new FormData();
            formData.append('action', 'delete_user');
            formData.append('user_id', userId);

            const resp = await fetch('api/admin_user_manage.php', {
                method: 'POST',
                body: formData
            });
            const result = await resp.json();

            if (result.success) {
                alert(result.message);
                loadUsers();
            } else {
                alert(result.message || 'Gagal menghapus user.');
            }
        } catch (err) {
            alert('Terjadi kesalahan jaringan.');
        }
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }

    function escapeJs(str) {
        if (!str) return '';
        return String(str).replace(/'/g, "\\'");
    }
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
