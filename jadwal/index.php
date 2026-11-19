<?php
require_once '../config/database.php';
require_once '../auth/auth_check.php';

$user_role = $_SESSION['role'] ?? 'user';
$user_jurusan = $_SESSION['jurusan'] ?? '';

$where = [];
$params = [];
$types = "";

// Jika role user biasa, kunci query HANYA untuk jurusannya
if ($user_role === 'user' && !empty($user_jurusan)) {
    $where[] = "jurusan = ?";
    $params[] = $user_jurusan;
    $types .= "s";
}

if (isset($_GET['cari'])) {
    if (!empty($_GET['tanggal'])) {
        $where[] = "tanggal_rapat = ?";
        $params[] = $_GET['tanggal'];
        $types .= "s";
    }
    if (!empty($_GET['bulan'])) {
        $where[] = "MONTH(tanggal_rapat) = ?";
        $params[] = $_GET['bulan'];
        $types .= "s";
    }
    if (!empty($_GET['tahun'])) {
        $where[] = "YEAR(tanggal_rapat) = ?";
        $params[] = $_GET['tahun'];
        $types .= "s";
    }
    // Filter jurusan hanya berlaku bebas untuk admin
    if ($user_role === 'admin' && !empty($_GET['jurusan'])) {
        $where[] = "jurusan LIKE ?";
        $params[] = "%".$_GET['jurusan']."%";
        $types .= "s";
    }
    if (!empty($_GET['program'])) {
        $where[] = "program_magister LIKE ?";
        $params[] = "%".$_GET['program']."%";
        $types .= "s";
    }
}

$sql = "SELECT * FROM jadwal_zoom";
if (count($where) > 0) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY tanggal_rapat DESC, waktu_mulai DESC";

$stmt = $conn->prepare($sql);
if ($types) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$hari_indonesia = [
    'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
    'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'
];

require_once '../layouts/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">Data Jadwal Zoom</h2>
        <?php if ($user_role === 'user' && !empty($user_jurusan)): ?>
            <small class="text-primary fw-semibold"><i class="bi bi-building"></i> Menampilkan jadwal khusus Jurusan <?= htmlspecialchars($user_jurusan) ?></small>
        <?php endif; ?>
    </div>
    <a href="tambah.php" class="btn btn-primary"><i class="bi bi-plus-circle me-1"></i> Tambah Jadwal</a>
</div>

<!-- Filter Box -->
<div class="card mb-4 shadow-sm">
    <div class="card-body bg-light">
        <form method="GET" action="">
            <div class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Tanggal</label>
                    <input type="date" name="tanggal" class="form-control" value="<?= $_GET['tanggal'] ?? '' ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Bulan</label>
                    <select name="bulan" class="form-select">
                        <option value="">Semua</option>
                        <?php for($i=1;$i<=12;$i++): ?>
                        <option value="<?= str_pad($i,2,'0',STR_PAD_LEFT) ?>" <?= (isset($_GET['bulan']) && $_GET['bulan'] == $i) ? 'selected' : '' ?>><?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Tahun</label>
                    <input type="number" name="tahun" class="form-control" value="<?= $_GET['tahun'] ?? '' ?>" placeholder="2026">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Jurusan</label>
                    <?php if ($user_role === 'user' && !empty($user_jurusan)): ?>
                        <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($user_jurusan) ?>" readonly title="Terkunci sesuai jurusan akun">
                    <?php else: ?>
                        <input type="text" name="jurusan" class="form-control" value="<?= $_GET['jurusan'] ?? '' ?>" placeholder="Semua Jurusan">
                    <?php endif; ?>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Program</label>
                    <input type="text" name="program" class="form-control" value="<?= $_GET['program'] ?? '' ?>" placeholder="Semua Program">
                </div>
                <div class="col-md-2">
                    <button type="submit" name="cari" value="1" class="btn btn-info text-white w-100 mb-1"><i class="bi bi-search"></i> Cari</button>
                    <a href="index.php" class="btn btn-secondary w-100"><i class="bi bi-arrow-counterclockwise"></i> Reset</a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th width="40">No</th>
                        <th>Hari</th>
                        <th>Tanggal</th>
                        <th>Waktu</th>
                        <th>Nama Rapat</th>
                        <th>Jurusan</th>
                        <th>Program Studi</th>
                        <th>Link Zoom</th>
                        <th width="160">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($result->num_rows > 0): $no=1; while($row = $result->fetch_assoc()): 
                        $hari_en = date('l', strtotime($row['tanggal_rapat']));
                        $hari_id = $hari_indonesia[$hari_en] ?? $hari_en;
                    ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= $hari_id ?></td>
                        <td><?= date('d-m-Y', strtotime($row['tanggal_rapat'])) ?></td>
                        <td><?= substr($row['waktu_mulai'],0,5) ?> - <?= substr($row['waktu_selesai'],0,5) ?></td>
                        <td class="fw-semibold"><?= htmlspecialchars($row['nama_rapat']) ?></td>
                        <td><span class="badge bg-secondary"><?= htmlspecialchars($row['jurusan']) ?></span></td>
                        <td><?= htmlspecialchars($row['program_magister']) ?></td>
                        <td>
                            <a href="<?= htmlspecialchars($row['link_zoom']) ?>" target="_blank" class="btn btn-sm btn-primary"><i class="bi bi-camera-video"></i> Join</a>
                            <?php if($_SESSION['role'] === 'admin' && !empty($row['link_zoom_admin'])): ?>
                            <a href="<?= htmlspecialchars($row['link_zoom_admin']) ?>" target="_blank" class="btn btn-sm btn-danger" title="Link Admin/Host"><i class="bi bi-shield-lock-fill"></i> Host</a>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="detail.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-info text-white"><i class="bi bi-eye"></i> Detail</a>
                            <?php if($_SESSION['role'] === 'admin'): ?>
                            <a href="edit.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-warning"><i class="bi bi-pencil"></i></a>
                            <a href="hapus.php?id=<?= $row['id'] ?>" onclick="return confirm('Yakin hapus data ini?');" class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; else: ?>
                    <tr>
                        <td colspan="9" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox fs-3 d-block mb-1"></i>
                            Tidak ada jadwal rapat yang ditemukan untuk jurusan ini.
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require_once '../layouts/footer.php'; ?>
