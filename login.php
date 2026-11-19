<?php
require_once 'config/database.php';

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

// Ambil daftar jurusan dari database
$jurusan_query = $conn->query("SELECT * FROM jurusan ORDER BY nama_jurusan ASC");
$jurusan_list = [];
if ($jurusan_query) {
    while ($j = $jurusan_query->fetch_assoc()) {
        $jurusan_list[] = $j;
    }
}

$error = '';
$success = '';

// Tentukan tab aktif awal (default: 'user', bisa lewat URL ?tab=admin)
$active_tab = (isset($_GET['tab']) && $_GET['tab'] === 'admin') ? 'admin' : 'user';

if (isset($_GET['registered'])) {
    $success = 'Pendaftaran akun user berhasil! Silakan login dengan akun Anda.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_type = $_POST['login_type'] ?? 'user';
    $active_tab = ($login_type === 'admin') ? 'admin' : 'user';
    
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $selected_jurusan = trim($_POST['jurusan'] ?? '');
    
    if (empty($username) || empty($password)) {
        $error = 'Username dan Password wajib diisi!';
    } else {
        $stmt = $conn->prepare("SELECT id, nama, username, password, role, jurusan FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            if (password_verify($password, $row['password'])) {
                if ($login_type === 'admin') {
                    // Validasi khusus Login Admin: akun harus bertipe admin
                    if ($row['role'] !== 'admin') {
                        $error = 'Akses ditolak! Akun Anda bukan akun Administrator. Silakan login melalui tab <strong>Login User</strong>.';
                    } else {
                        $_SESSION['user_id'] = $row['id'];
                        $_SESSION['nama'] = $row['nama'];
                        $_SESSION['username'] = $row['username'];
                        $_SESSION['role'] = 'admin';
                        $_SESSION['jurusan'] = ''; // Admin memiliki akses semua jurusan
                        header("Location: dashboard.php");
                        exit();
                    }
                } else {
                    // Validasi Login User Jurusan
                    if ($row['role'] === 'admin') {
                        // Jika admin login melalui tab user, arahkan sebagai admin
                        $_SESSION['user_id'] = $row['id'];
                        $_SESSION['nama'] = $row['nama'];
                        $_SESSION['username'] = $row['username'];
                        $_SESSION['role'] = 'admin';
                        $_SESSION['jurusan'] = !empty($selected_jurusan) ? $selected_jurusan : '';
                        header("Location: dashboard.php");
                        exit();
                    }

                    $registered_jurusan = trim($row['jurusan'] ?? '');

                    if (!empty($registered_jurusan)) {
                        // User sudah terikat ke satu jurusan di database
                        if (!empty($selected_jurusan) && strcasecmp($selected_jurusan, $registered_jurusan) !== 0) {
                            $error = 'Akun Anda terdaftar khusus untuk jurusan <strong>' . htmlspecialchars($registered_jurusan) . '</strong>. Silakan pilih jurusan tersebut pada dropdown.';
                        } else {
                            $_SESSION['user_id'] = $row['id'];
                            $_SESSION['nama'] = $row['nama'];
                            $_SESSION['username'] = $row['username'];
                            $_SESSION['role'] = 'user';
                            $_SESSION['jurusan'] = $registered_jurusan;
                            header("Location: dashboard.php");
                            exit();
                        }
                    } else {
                        // Akun belum terikat jurusan: wajib memilih jurusan sekarang
                        if (empty($selected_jurusan)) {
                            $error = 'Silakan pilih Jurusan Anda untuk masuk ke sistem!';
                        } else {
                            $_SESSION['user_id'] = $row['id'];
                            $_SESSION['nama'] = $row['nama'];
                            $_SESSION['username'] = $row['username'];
                            $_SESSION['role'] = 'user';
                            $_SESSION['jurusan'] = $selected_jurusan;
                            
                            // Simpan jurusan pilihan ke profil user
                            $stmt_update = $conn->prepare("UPDATE users SET jurusan = ? WHERE id = ?");
                            $stmt_update->bind_param("si", $selected_jurusan, $row['id']);
                            $stmt_update->execute();

                            header("Location: dashboard.php");
                            exit();
                        }
                    }
                }
            } else {
                $error = 'Password yang Anda masukkan salah!';
            }
        } else {
            $error = 'Username tidak ditemukan di sistem!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Jadwal Zoom</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #0d6efd 0%, #0dcaf0 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 15px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .login-card {
            width: 100%;
            max-width: 450px;
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            padding: 32px 30px;
            transition: all 0.3s ease;
        }
        .brand-icon {
            width: 65px;
            height: 65px;
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
            margin-bottom: 12px;
            transition: all 0.3s ease;
        }
        .icon-user {
            background: #e7f1ff;
            color: #0d6efd;
        }
        .icon-admin {
            background: #fee2e2;
            color: #dc3545;
        }
        /* Tab Switcher Styling */
        .tab-switcher {
            background: #f1f5f9;
            padding: 4px;
            border-radius: 50px;
            display: flex;
            gap: 4px;
        }
        .tab-btn {
            flex: 1;
            border: none;
            background: transparent;
            padding: 10px 14px;
            font-size: 0.92rem;
            font-weight: 600;
            color: #64748b;
            border-radius: 50px;
            transition: all 0.2s ease;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .tab-btn.active.tab-user-btn {
            background: #0d6efd;
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(13, 110, 253, 0.3);
        }
        .tab-btn.active.tab-admin-btn {
            background: #dc3545;
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
        }
    </style>
</head>
<body>
<div class="login-card">
    <!-- Header Icon & Title -->
    <div class="text-center mb-3">
        <div class="brand-icon <?= ($active_tab === 'admin') ? 'icon-admin' : 'icon-user' ?>" id="brandIcon">
            <i class="bi <?= ($active_tab === 'admin') ? 'bi-shield-lock-fill' : 'bi-camera-video-fill' ?>" id="iconEl"></i>
        </div>
        <h3 class="fw-bold mb-1" id="loginTitle">
            <?= ($active_tab === 'admin') ? 'Login Administrator' : 'SI Jadwal Rapat Zoom' ?>
        </h3>
        <p class="text-muted small mb-0" id="loginSubtitle">
            <?= ($active_tab === 'admin') ? 'Akses pengelola sistem & seluruh jurusan' : 'Silakan masuk sesuai jurusan Anda' ?>
        </p>
    </div>

    <!-- 2 Pilihan Tab: User vs Admin -->
    <div class="tab-switcher mb-4">
        <button type="button" 
                class="tab-btn tab-user-btn <?= ($active_tab === 'user') ? 'active' : '' ?>" 
                id="btnTabUser" 
                onclick="setLoginTab('user')">
            <i class="bi bi-person-fill me-1"></i> Login User
        </button>
        <button type="button" 
                class="tab-btn tab-admin-btn <?= ($active_tab === 'admin') ? 'active' : '' ?>" 
                id="btnTabAdmin" 
                onclick="setLoginTab('admin')">
            <i class="bi bi-shield-lock-fill me-1"></i> Login Admin
        </button>
    </div>

    <!-- Alert Sukses / Error -->
    <?php if ($success): ?>
        <div class="alert alert-success py-2 d-flex align-items-center mb-3">
            <i class="bi bi-check-circle-fill me-2 fs-5"></i>
            <div><?= $success ?></div>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger py-2 d-flex align-items-center mb-3">
            <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
            <div><?= $error ?></div>
        </div>
    <?php endif; ?>

    <!-- Form Login -->
    <form method="POST" action="" id="loginForm">
        <input type="hidden" name="login_type" id="loginTypeInput" value="<?= htmlspecialchars($active_tab) ?>">

        <div class="mb-3">
            <label class="form-label fw-semibold">Username</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-person"></i></span>
                <input type="text" 
                       name="username" 
                       class="form-control" 
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" 
                       placeholder="Masukkan username" 
                       required 
                       autofocus>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label fw-semibold">Password</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-key"></i></span>
                <input type="password" 
                       name="password" 
                       class="form-control" 
                       placeholder="Masukkan password" 
                       required>
            </div>
        </div>

        <!-- Field Jurusan: Hanya Muncul Pada Login User -->
        <div class="mb-4" id="jurusanGroup" style="<?= ($active_tab === 'admin') ? 'display: none;' : '' ?>">
            <label class="form-label fw-semibold d-flex justify-content-between">
                <span><i class="bi bi-building"></i> Pilihan Jurusan</span>
                <span class="badge bg-primary text-white">Khusus User</span>
            </label>
            <select name="jurusan" class="form-select" id="jurusanSelect">
                <option value="">-- Pilih Jurusan Anda --</option>
                <?php foreach ($jurusan_list as $j): ?>
                    <option value="<?= htmlspecialchars($j['nama_jurusan']) ?>" <?= (isset($_POST['jurusan']) && $_POST['jurusan'] === $j['nama_jurusan']) ? 'selected' : '' ?>>
                        [<?= htmlspecialchars($j['kode_jurusan']) ?>] <?= htmlspecialchars($j['nama_jurusan']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <div class="form-text small text-muted">
                <i class="bi bi-info-circle"></i> Anda hanya dapat mengakses jadwal rapat dan data dari jurusan ini.
            </div>
        </div>

        <!-- Info Khusus Admin: Hanya Muncul Pada Login Admin -->
        <div class="mb-4" id="adminInfoGroup" style="<?= ($active_tab === 'admin') ? '' : 'display: none;' ?>">
            <div class="p-2 px-3 bg-light rounded border border-danger-subtle text-danger small">
                <i class="bi bi-shield-check me-1"></i> Mode Administrator memiliki hak akses kelola penuh ke seluruh data jurusan, user, dan jadwal.
            </div>
        </div>

        <button type="submit" 
                class="btn w-100 py-2 fw-semibold <?= ($active_tab === 'admin') ? 'btn-danger' : 'btn-primary' ?>" 
                id="submitBtn">
            <i class="bi bi-box-arrow-in-right me-1"></i> 
            <span id="submitBtnText"><?= ($active_tab === 'admin') ? 'Masuk sebagai Admin' : 'Masuk sebagai User' ?></span>
        </button>
    </form>

    <!-- Footer Bawah: Link Register hanya untuk User -->
    <div class="text-center mt-3 pt-3 border-top" id="footerRegister" style="<?= ($active_tab === 'admin') ? 'display: none;' : '' ?>">
        <span class="text-muted small">Belum punya akun user?</span>
        <a href="register.php" class="text-decoration-none fw-semibold small ms-1">Daftar Akun Baru</a>
    </div>

    <!-- Footer Khusus Admin -->
    <div class="text-center mt-3 pt-3 border-top text-muted small" id="footerAdmin" style="<?= ($active_tab === 'admin') ? '' : 'display: none;' ?>">
        <i class="bi bi-lock"></i> Halaman terbatas untuk pengelola sistem.
    </div>
</div>

<script>
function setLoginTab(tab) {
    const inputType = document.getElementById('loginTypeInput');
    const btnUser = document.getElementById('btnTabUser');
    const btnAdmin = document.getElementById('btnTabAdmin');
    const brandIcon = document.getElementById('brandIcon');
    const iconEl = document.getElementById('iconEl');
    const title = document.getElementById('loginTitle');
    const subtitle = document.getElementById('loginSubtitle');
    const jurusanGroup = document.getElementById('jurusanGroup');
    const adminInfo = document.getElementById('adminInfoGroup');
    const submitBtn = document.getElementById('submitBtn');
    const submitText = document.getElementById('submitBtnText');
    const footerRegister = document.getElementById('footerRegister');
    const footerAdmin = document.getElementById('footerAdmin');
    const jurusanSelect = document.getElementById('jurusanSelect');

    inputType.value = tab;

    if (tab === 'admin') {
        btnAdmin.classList.add('active');
        btnUser.classList.remove('active');

        brandIcon.className = 'brand-icon icon-admin';
        iconEl.className = 'bi bi-shield-lock-fill';
        title.textContent = 'Login Administrator';
        subtitle.textContent = 'Akses pengelola sistem & seluruh jurusan';

        jurusanGroup.style.display = 'none';
        adminInfo.style.display = 'block';

        submitBtn.className = 'btn btn-danger w-100 py-2 fw-semibold';
        submitText.textContent = 'Masuk sebagai Admin';

        footerRegister.style.display = 'none';
        footerAdmin.style.display = 'block';

        if (jurusanSelect) jurusanSelect.removeAttribute('required');
    } else {
        btnUser.classList.add('active');
        btnAdmin.classList.remove('active');

        brandIcon.className = 'brand-icon icon-user';
        iconEl.className = 'bi bi-camera-video-fill';
        title.textContent = 'SI Jadwal Rapat Zoom';
        subtitle.textContent = 'Silakan masuk sesuai jurusan Anda';

        jurusanGroup.style.display = 'block';
        adminInfo.style.display = 'none';

        submitBtn.className = 'btn btn-primary w-100 py-2 fw-semibold';
        submitText.textContent = 'Masuk sebagai User';

        footerRegister.style.display = 'block';
        footerAdmin.style.display = 'none';
    }
}
</script>
</body>
</html>
