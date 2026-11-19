<?php
require_once '../config/database.php';
require_once '../auth/auth_check.php';

if ($_SESSION['role'] !== 'admin') {
    require_once '../layouts/header.php';
    echo '<div class="alert alert-danger my-4 p-4 text-center shadow-sm">
        <h4 class="fw-bold"><i class="bi bi-shield-x"></i> Akses Ditolak</h4>
        <p>Anda hanya memiliki hak akses untuk melihat daftar pengguna. Anda tidak memiliki izin untuk mengubah akun pengguna.</p>
        <a href="index.php" class="btn btn-primary mt-2"><i class="bi bi-arrow-left"></i> Kembali ke Daftar Pengguna</a>
    </div>';
    require_once '../layouts/footer.php';
    exit();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();

if (!$user_data) {
    die("User tidak ditemukan.");
}

// Ambil daftar jurusan dari database
$jurusan_list = $conn->query("SELECT * FROM jurusan ORDER BY nama_jurusan ASC");

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $role = $_POST['role'] ?? 'user';
    $jurusan = trim($_POST['jurusan'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($nama) || empty($username)) {
        $error = 'Nama dan Username wajib diisi!';
    } else {
        // Cek username duplikat pada user lain
        $check = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $check->bind_param("si", $username, $id);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = 'Username sudah digunakan oleh akun lain!';
        } else {
            if (!empty($password)) {
                $password_hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt_up = $conn->prepare("UPDATE users SET nama = ?, username = ?, password = ?, role = ?, jurusan = ? WHERE id = ?");
                $stmt_up->bind_param("sssssi", $nama, $username, $password_hashed, $role, $jurusan, $id);
            } else {
                $stmt_up = $conn->prepare("UPDATE users SET nama = ?, username = ?, role = ?, jurusan = ? WHERE id = ?");
                $stmt_up->bind_param("ssssi", $nama, $username, $role, $jurusan, $id);
            }
            
            if ($stmt_up->execute()) {
                header("Location: index.php?msg=updated");
                exit();
            } else {
                $error = 'Gagal memperbarui data user.';
            }
        }
    }
}

require_once '../layouts/header.php';
?>
<div class="card shadow-sm col-md-8 mx-auto">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Edit User</h5>
        <a href="index.php" class="btn btn-sm btn-outline-secondary">Kembali</a>
    </div>
    <div class="card-body">
        <?php if ($error): ?>
            <div class="alert alert-danger py-2"><?= $error ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label fw-semibold">Nama Lengkap *</label>
                <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($user_data['nama']) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Username *</label>
                <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($user_data['username']) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Password Baru <small class="text-muted fw-normal">(Kosongkan jika tidak ingin mengubah password)</small></label>
                <input type="password" name="password" class="form-control" placeholder="Masukkan password baru jika ingin diganti">
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Role *</label>
                    <select name="role" class="form-select" required>
                        <option value="user" <?= ($user_data['role'] === 'user') ? 'selected' : '' ?>>User (Terkunci ke Jurusan)</option>
                        <option value="admin" <?= ($user_data['role'] === 'admin') ? 'selected' : '' ?>>Admin (Akses Penuh Semua Jurusan)</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Jurusan Terdaftar</label>
                    <select name="jurusan" class="form-select">
                        <option value="">-- Bebas (Pilih saat Login) --</option>
                        <?php if ($jurusan_list): while ($j = $jurusan_list->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($j['nama_jurusan']) ?>" <?= ($user_data['jurusan'] === $j['nama_jurusan']) ? 'selected' : '' ?>>
                                [<?= htmlspecialchars($j['kode_jurusan']) ?>] <?= htmlspecialchars($j['nama_jurusan']) ?>
                            </option>
                        <?php endwhile; endif; ?>
                    </select>
                    <div class="form-text small">User hanya bisa melihat data jadwal untuk jurusan yang ditentukan.</div>
                </div>
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Simpan Perubahan</button>
                <a href="index.php" class="btn btn-secondary ms-1">Batal</a>
            </div>
        </form>
    </div>
</div>
<?php require_once '../layouts/footer.php'; ?>
