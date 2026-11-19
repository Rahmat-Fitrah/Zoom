<?php
require_once '../config/database.php';
require_once '../auth/auth_check.php';

$user_role = $_SESSION['role'] ?? 'user';
$user_jurusan = $_SESSION['jurusan'] ?? '';

if ($user_role === 'user' && !empty($user_jurusan)) {
    $stmt = $conn->prepare("SELECT * FROM jadwal_zoom WHERE (dokumentasi != '' OR file_video != '' OR file_materi != '') AND jurusan = ? ORDER BY tanggal_rapat DESC");
    $stmt->bind_param("s", $user_jurusan);
    $stmt->execute();
    $q = $stmt->get_result();
} else {
    $sql = "SELECT * FROM jadwal_zoom WHERE dokumentasi != '' OR file_video != '' OR file_materi != '' ORDER BY tanggal_rapat DESC";
    $q = $conn->query($sql);
}

require_once '../layouts/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h3 class="mb-0">Arsip Dokumentasi Zoom</h3>
        <?php if ($user_role === 'user' && !empty($user_jurusan)): ?>
            <small class="text-primary fw-semibold"><i class="bi bi-building"></i> Menampilkan dokumentasi khusus Jurusan <?= htmlspecialchars($user_jurusan) ?></small>
        <?php endif; ?>
    </div>
</div>

<div class="row">
    <?php if($q && $q->num_rows > 0): while($r = $q->fetch_assoc()): ?>
    <div class="col-md-4 mb-4">
        <div class="card h-100 shadow-sm">
            <?php if(!empty($r['dokumentasi'])): ?>
                <img src="<?= base_url('uploads/dokumentasi/'.$r['dokumentasi']) ?>" class="card-img-top" style="height: 200px; object-fit: cover;">
            <?php else: ?>
                <div class="bg-secondary text-white d-flex justify-content-center align-items-center" style="height: 200px;">
                    <span><i class="bi bi-image"></i> Tanpa Foto</span>
                </div>
            <?php endif; ?>
            <div class="card-body">
                <h5 class="card-title"><?= htmlspecialchars($r['nama_rapat']) ?></h5>
                <p class="card-text text-muted small">
                    <i class="bi bi-calendar"></i> <?= date('d-m-Y', strtotime($r['tanggal_rapat'])) ?> <br>
                    <i class="bi bi-tag"></i> <span class="badge bg-secondary"><?= htmlspecialchars($r['jurusan']) ?></span> <?= htmlspecialchars($r['program_magister'] ? ' - '.$r['program_magister'] : '') ?>
                </p>
                <hr>
                <div class="d-grid gap-2">
                    <?php if(!empty($r['file_video'])): ?>
                        <a href="<?= base_url('uploads/dokumentasi/'.$r['file_video']) ?>" class="btn btn-sm btn-outline-danger" target="_blank"><i class="bi bi-play-circle"></i> Lihat Video</a>
                    <?php endif; ?>
                    <?php if(!empty($r['file_materi'])): ?>
                        <a href="<?= base_url('uploads/dokumentasi/'.$r['file_materi']) ?>" class="btn btn-sm btn-outline-info" target="_blank"><i class="bi bi-file-earmark-text"></i> Buka Materi</a>
                    <?php endif; ?>
                    <a href="<?= base_url('jadwal/detail.php?id='.$r['id']) ?>" class="btn btn-sm btn-primary"><i class="bi bi-info-circle"></i> Lihat Detail Rapat</a>
                </div>
            </div>
        </div>
    </div>
    <?php endwhile; else: ?>
        <div class="col-12">
            <div class="alert alert-info py-4 text-center">
                <i class="bi bi-folder2-open fs-2 d-block mb-2"></i>
                Belum ada dokumentasi rapat (Foto, Video, atau Materi) yang diunggah untuk jurusan ini.
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../layouts/footer.php'; ?>
