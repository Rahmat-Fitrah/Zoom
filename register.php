<?php
require_once 'config/database.php';

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

// Ambil daftar jurusan dari database
$jurusan_list = $conn->query("SELECT * FROM jurusan ORDER BY nama_jurusan ASC");

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $jurusan = trim($_POST['jurusan'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    if (empty($nama) || empty($username) || empty($jurusan) || empty($password)) {
        $error = 'Semua bidang formulir wajib diisi!';
    } elseif ($password !== $password_confirm) {
        $error = 'Konfirmasi password tidak cocok!';
    } elseif (strlen($password) < 4) {
        $error = 'Password minimal terdiri dari 4 karakter!';
    } else {
        // Cek apakah username sudah digunakan
        $stmt_check = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt_check->bind_param("s", $username);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            $error = 'Username sudah terdaftar! Silakan gunakan username lain.';
        } else {
            // Hash password dan simpan user baru dengan role 'user'
            $password_hashed = password_hash($password, PASSWORD_DEFAULT);
            $role = 'user';

            $stmt_insert = $conn->prepare("INSERT INTO users (nama, username, password, role, jurusan) VALUES (?, ?, ?, ?, ?)");
            $stmt_insert->bind_param("sssss", $nama, $username, $password_hashed, $role, $jurusan);

            if ($stmt_insert->execute()) {
                header("Location: login.php?registered=1");
                exit();
            } else {
                $error = 'Gagal mendaftarkan akun. Silakan coba lagi.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun User - Sistem Jadwal Zoom</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #0d6efd 0%, #0dcaf0 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .register-card {
            width: 100%;
            max-width: 480px;
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            padding: 32px;
        }
        .brand-icon {
            width: 60px;
            height: 60px;
            background: #e7f1ff;
            color: #0d6efd;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            margin-bottom: 12px;
        }
    </style>
</head>
<body>
<div class="register-card">
    <div class="text-center mb-3">
        <div class="brand-icon">
            <i class="bi bi-person-plus-fill"></i>
        </div>
        <h3 class="fw-bold mb-1">Daftar Akun User</h3>
        <p class="text-muted small">Daftarkan akun Anda sesuai jurusan untuk mengakses jadwal Zoom</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger py-2 d-flex align-items-center">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <div><?= $error ?></div>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="mb-3">
            <label class="form-label fw-semibold">Nama Lengkap *</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-person"></i></span>
                <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>" placeholder="Nama lengkap Anda" required>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label fw-semibold">Username *</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-at"></i></span>
                <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" placeholder="Username login" required>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label fw-semibold">Pilih Jurusan *</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-building"></i></span>
                <select name="jurusan" class="form-select" required>
                    <option value="">-- Pilih Jurusan Anda --</option>
                    <?php if ($jurusan_list): while ($j = $jurusan_list->fetch_assoc()): ?>
                        <option value="<?= htmlspecialchars($j['nama_jurusan']) ?>" <?= (isset($_POST['jurusan']) && $_POST['jurusan'] === $j['nama_jurusan']) ? 'selected' : '' ?>>
                            [<?= htmlspecialchars($j['kode_jurusan']) ?>] <?= htmlspecialchars($j['nama_jurusan']) ?>
                        </option>
                    <?php endwhile; endif; ?>
                </select>
            </div>
            <div class="form-text small text-primary">
                <i class="bi bi-info-circle"></i> Anda hanya akan melihat jadwal dan arsip dari jurusan yang dipilih.
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label fw-semibold">Password *</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-key"></i></span>
                    <input type="password" name="password" class="form-control" placeholder="Minimal 4 char" required>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label fw-semibold">Ulangi Password *</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-shield-check"></i></span>
                    <input type="password" name="password_confirm" class="form-control" placeholder="Ulangi password" required>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
            <i class="bi bi-check-circle me-1"></i> Daftar Sekarang
        </button>
    </form>

    <div class="text-center mt-3 pt-2 border-top">
        <span class="text-muted small">Sudah memiliki akun?</span>
        <a href="login.php" class="text-decoration-none fw-semibold small ms-1">Login di sini</a>
    </div>
</div>
</body>
</html>
