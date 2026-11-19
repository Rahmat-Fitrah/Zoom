<?php
require_once 'config/database.php';
require_once 'auth/auth_check.php';

// Menghitung statistik
$today = date('Y-m-d');
$week_end = date('Y-m-d', strtotime('+7 days'));

$user_role = $_SESSION['role'] ?? 'user';
$user_jurusan = $_SESSION['jurusan'] ?? '';

if ($user_role === 'user' && !empty($user_jurusan)) {
    // Query khusus jurusan user
    $st = $conn->prepare("SELECT COUNT(id) as c FROM jadwal_zoom WHERE jurusan = ?");
    $st->bind_param("s", $user_jurusan);
    $st->execute();
    $total = $st->get_result()->fetch_assoc()['c'] ?? 0;

    $st = $conn->prepare("SELECT COUNT(id) as c FROM jadwal_zoom WHERE tanggal_rapat = ? AND jurusan = ?");
    $st->bind_param("ss", $today, $user_jurusan);
    $st->execute();
    $hari_ini = $st->get_result()->fetch_assoc()['c'] ?? 0;

    $st = $conn->prepare("SELECT COUNT(id) as c FROM jadwal_zoom WHERE tanggal_rapat >= ? AND tanggal_rapat <= ? AND jurusan = ?");
    $st->bind_param("sss", $today, $week_end, $user_jurusan);
    $st->execute();
    $minggu_ini = $st->get_result()->fetch_assoc()['c'] ?? 0;

    $st = $conn->prepare("SELECT COUNT(id) as c FROM jadwal_zoom WHERE tanggal_rapat > ? AND jurusan = ?");
    $st->bind_param("ss", $today, $user_jurusan);
    $st->execute();
    $akan_datang = $st->get_result()->fetch_assoc()['c'] ?? 0;

    $st = $conn->prepare("SELECT * FROM jadwal_zoom WHERE tanggal_rapat >= ? AND jurusan = ? ORDER BY tanggal_rapat ASC, waktu_mulai ASC LIMIT 5");
    $st->bind_param("ss", $today, $user_jurusan);
    $st->execute();
    $q = $st->get_result();
} else {
    // Admin: menampilkan semua jurusan
    $total = $conn->query("SELECT COUNT(id) as c FROM jadwal_zoom")->fetch_assoc()['c'] ?? 0;
    $hari_ini = $conn->query("SELECT COUNT(id) as c FROM jadwal_zoom WHERE tanggal_rapat = '$today'")->fetch_assoc()['c'] ?? 0;
    $minggu_ini = $conn->query("SELECT COUNT(id) as c FROM jadwal_zoom WHERE tanggal_rapat >= '$today' AND tanggal_rapat <= '$week_end'")->fetch_assoc()['c'] ?? 0;
    $akan_datang = $conn->query("SELECT COUNT(id) as c FROM jadwal_zoom WHERE tanggal_rapat > '$today'")->fetch_assoc()['c'] ?? 0;

    $q = $conn->query("SELECT * FROM jadwal_zoom WHERE tanggal_rapat >= '$today' ORDER BY tanggal_rapat ASC, waktu_mulai ASC LIMIT 5");
}

require_once 'layouts/header.php';
?>
<h2 class="mb-4">Dashboard</h2>

<div class="row">
    <div class="col-md-3 mb-4">
        <div class="card text-white bg-primary shadow-sm">
            <div class="card-body">
                <h5 class="card-title">Total Rapat</h5>
                <h2><?= $total ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card text-white bg-success shadow-sm">
            <div class="card-body">
                <h5 class="card-title">Rapat Hari Ini</h5>
                <h2><?= $hari_ini ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card text-white bg-warning shadow-sm">
            <div class="card-body">
                <h5 class="card-title">Rapat 7 Hari Kedepan</h5>
                <h2><?= $minggu_ini ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card text-white bg-info shadow-sm">
            <div class="card-body">
                <h5 class="card-title">Akan Datang</h5>
                <h2><?= $akan_datang ?></h2>
            </div>
        </div>
    </div>
</div>

<div class="card mt-2 shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <strong>Jadwal Rapat Terdekat</strong>
        <a href="jadwal/index.php" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Waktu</th>
                        <th>Nama Rapat</th>
                        <th>Jurusan</th>
                        <th>Program</th>
                        <th>Link Zoom</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($q && $q->num_rows > 0): ?>
                        <?php while($r = $q->fetch_assoc()): ?>
                        <tr>
                            <td><?= date('d-m-Y', strtotime($r['tanggal_rapat'])) ?></td>
                            <td><?= substr($r['waktu_mulai'],0,5) ?> - <?= substr($r['waktu_selesai'],0,5) ?></td>
                            <td><?= htmlspecialchars($r['nama_rapat']) ?></td>
                            <td><span class="badge bg-secondary"><?= htmlspecialchars($r['jurusan']) ?></span></td>
                            <td><?= htmlspecialchars($r['program_magister']) ?></td>
                            <td><a href="<?= htmlspecialchars($r['link_zoom']) ?>" target="_blank" class="btn btn-sm btn-primary"><i class="bi bi-camera-video"></i> Join</a></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-3 text-muted">Tidak ada jadwal rapat terdekat untuk jurusan ini.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require_once 'layouts/footer.php'; ?>
