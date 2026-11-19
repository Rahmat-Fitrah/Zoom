<?php
require_once '../config/database.php';
require_once '../auth/auth_check.php';

if ($_SESSION['role'] !== 'admin') {
    require_once '../layouts/header.php';
    echo '<div class="alert alert-danger my-4 p-4 text-center shadow-sm">
        <h4 class="fw-bold"><i class="bi bi-shield-x"></i> Akses Ditolak</h4>
        <p>Anda hanya memiliki hak akses untuk melihat daftar pengguna. Anda tidak memiliki izin untuk menambah akun pengguna baru.</p>
        <a href="index.php" class="btn btn-primary mt-2"><i class="bi bi-arrow-left"></i> Kembali ke Daftar Pengguna</a>
    </div>';
    require_once '../layouts/footer.php';
    exit();
}

// Ambil daftar jurusan dari database
$jurusan_list = $conn->query("SELECT * FROM jurusan ORDER BY nama_jurusan ASC");

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'user';
    $jurusan = trim($_POST['jurusan'] ?? '');

    // Jika role admin, jurusan bisa dikosongkan
    if ($role === 'admin' && empty($jurusan)) {
        $jurusan = null;
    }
    
    if (empty($nama) || empty($username) || empty($password)) {
        $error = 'Nama, Username, dan Password wajib diisi!';
    } else {
        // Cek username duplikat
        $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $check->bind_param("s", $username);
        $check->execute();
        $check->store_result();
        
        if ($check->num_rows > 0) {
            $error = 'Username sudah digunakan, silakan pilih username lain!';
        } else {
            $password_hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (nama, username, password, role, jurusan) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $nama, $username, $password_hashed, $role, $jurusan);
            $stmt->execute();
            
            header("Location: index.php");
            exit();
        }
    }
}

require_once '../layouts/header.php';
?>
<div class="card shadow-sm col-md-8 mx-auto">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Tambah User Baru</h5>
        <a href="index.php" class="btn btn-sm btn-outline-secondary">Kembali</a>
    </div>
    <div class="card-body">
        <?php if ($error): ?>
            <div class="alert alert-danger py-2"><?= $error ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label fw-semibold">Nama Lengkap *</label>
                <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>" required placeholder="Masukkan nama lengkap">
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Username *</label>
                <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required placeholder="Username untuk login">
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Password *</label>
                <input type="password" name="password" class="form-control" required placeholder="Password">
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Role *</label>
                    <select name="role" id="roleSelect" class="form-select" required>
                        <option value="user" <?= (isset($_POST['role']) && $_POST['role'] === 'user') ? 'selected' : '' ?>>User (Terkunci ke Jurusan)</option>
                        <option value="admin" <?= (isset($_POST['role']) && $_POST['role'] === 'admin') ? 'selected' : '' ?>>Admin (Akses Penuh Semua Jurusan)</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3" id="jurusanWrapper">
                    <label class="form-label fw-semibold">Jurusan Terdaftar</label>
                    <select name="jurusan" class="form-select">
                        <option value="">-- Bebas (Pilih saat Login) --</option>
                        <?php if ($jurusan_list): while ($j = $jurusan_list->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($j['nama_jurusan']) ?>" <?= (isset($_POST['jurusan']) && $_POST['jurusan'] === $j['nama_jurusan']) ? 'selected' : '' ?>>
                                [<?= htmlspecialchars($j['kode_jurusan']) ?>] <?= htmlspecialchars($j['nama_jurusan']) ?>
                            </option>
                        <?php endwhile; endif; ?>
                    </select>
                    <div class="form-text small">Jika diisi, user ini hanya bisa masuk dan melihat jurusan ini.</div>
                </div>
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Simpan User</button>
                <a href="index.php" class="btn btn-secondary ms-1">Batal</a>
            </div>
        </form>
    </div>
</div>
<?php require_once '../layouts/footer.php'; ?>
