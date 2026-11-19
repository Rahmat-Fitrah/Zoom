<?php
require_once '../config/database.php';
require_once '../auth/auth_check.php';

$is_admin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');
$user_jurusan = $_SESSION['jurusan'] ?? '';

if ($is_admin) {
    // Admin melihat seluruh user
    $q = $conn->query("SELECT * FROM users ORDER BY id ASC");
} else {
    // User biasa: hanya melihat daftar user dari jurusannya sendiri (hanya lihat / read-only)
    if (!empty($user_jurusan)) {
        $stmt = $conn->prepare("SELECT * FROM users WHERE jurusan = ? ORDER BY id ASC");
        $stmt->bind_param("s", $user_jurusan);
        $stmt->execute();
        $q = $stmt->get_result();
    } else {
        $q = $conn->query("SELECT * FROM users WHERE role = 'user' ORDER BY id ASC");
    }
}

require_once '../layouts/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h3 class="mb-0">
            <?= $is_admin ? 'Manajemen User' : 'Daftar Pengguna' ?>
        </h3>
        <small class="text-muted">
            <?php if ($is_admin): ?>
                Kelola akun pengguna dan pembagian hak akses jurusan
            <?php else: ?>
                Daftar pengguna terdaftar untuk <strong>Jurusan <?= htmlspecialchars($user_jurusan) ?></strong> <span class="badge bg-secondary ms-1">Mode Hanya Lihat</span>
            <?php endif; ?>
        </small>
    </div>
    <?php if ($is_admin): ?>
        <a href="tambah.php" class="btn btn-primary"><i class="bi bi-person-plus me-1"></i> Tambah User</a>
    <?php endif; ?>
</div>

<?php if (!$is_admin): ?>
    <div class="alert alert-info py-2 d-flex align-items-center mb-3 shadow-sm">
        <i class="bi bi-info-circle-fill me-2 fs-5"></i>
        <div>
            Anda memiliki akses <strong>hanya lihat (read-only)</strong> pada halaman ini. Anda dapat melihat daftar sesama pengguna di Jurusan <strong><?= htmlspecialchars($user_jurusan) ?></strong>, tetapi tidak dapat menambah, mengubah, atau menghapus akun orang lain.
        </div>
    </div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th width="40">No</th>
                        <th>Nama</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Jurusan</th>
                        <th>Dibuat Pada</th>
                        <?php if ($is_admin): ?>
                            <th width="120">Aksi</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($q && $q->num_rows > 0): $no=1; while($r = $q->fetch_assoc()): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td class="fw-semibold">
                            <?= htmlspecialchars($r['nama']) ?>
                            <?php if ($r['id'] == $_SESSION['user_id']): ?>
                                <span class="badge bg-success ms-1"><i class="bi bi-person-check"></i> Anda</span>
                            <?php endif; ?>
                        </td>
                        <td><code><?= htmlspecialchars($r['username']) ?></code></td>
                        <td>
                            <?php if ($r['role'] === 'admin'): ?>
                                <span class="badge bg-danger">Admin</span>
                            <?php else: ?>
                                <span class="badge bg-info text-dark">User</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($r['jurusan'])): ?>
                                <span class="badge bg-primary"><i class="bi bi-building me-1"></i><?= htmlspecialchars($r['jurusan']) ?></span>
                            <?php else: ?>
                                <?php if ($r['role'] === 'admin'): ?>
                                    <span class="badge bg-secondary">Akses Semua Jurusan</span>
                                <?php else: ?>
                                    <span class="badge bg-light text-muted border">Pilih saat Login</span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                        <td><?= date('d-m-Y H:i', strtotime($r['created_at'])) ?></td>
                        <?php if ($is_admin): ?>
                        <td>
                            <a href="edit.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-warning" title="Edit User"><i class="bi bi-pencil"></i></a>
                            <?php if ($r['id'] != $_SESSION['user_id']): ?>
                                <a href="hapus.php?id=<?= $r['id'] ?>" onclick="return confirm('Hapus user <?= htmlspecialchars($r['username']) ?>?')" class="btn btn-sm btn-danger" title="Hapus User"><i class="bi bi-trash"></i></a>
                            <?php endif; ?>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endwhile; else: ?>
                    <tr>
                        <td colspan="<?= $is_admin ? 7 : 6 ?>" class="text-center py-4 text-muted">
                            <i class="bi bi-people fs-3 d-block mb-1"></i>
                            Tidak ada data pengguna yang ditemukan.
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require_once '../layouts/footer.php'; ?>
