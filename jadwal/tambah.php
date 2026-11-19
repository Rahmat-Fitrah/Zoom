<?php
require_once '../config/database.php';
require_once '../auth/auth_check.php';

$user_role = $_SESSION['role'] ?? 'user';
$user_jurusan = $_SESSION['jurusan'] ?? '';

// Ambil data jurusan dari master data
$jurusan_list = $conn->query("SELECT * FROM jurusan ORDER BY nama_jurusan ASC");
$jurusan_data = [];
$user_jurusan_id = 0;

if ($jurusan_list) {
    while ($row = $jurusan_list->fetch_assoc()) {
        $jurusan_data[] = $row;
        if ($user_role === 'user' && !empty($user_jurusan) && $row['nama_jurusan'] === $user_jurusan) {
            $user_jurusan_id = $row['id'];
        }
    }
}

require_once '../layouts/header.php';
?>
<div class="card shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h4 class="mb-0">Tambah Jadwal Rapat Zoom</h4>
        <?php if ($user_role === 'user' && !empty($user_jurusan)): ?>
            <span class="badge bg-primary px-3 py-2"><i class="bi bi-building"></i> Jurusan <?= htmlspecialchars($user_jurusan) ?></span>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <form action="simpan.php" method="POST" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-semibold">Tanggal Rapat *</label>
                    <input type="date" name="tanggal_rapat" class="form-control" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-semibold">Jam Mulai *</label>
                    <input type="time" name="waktu_mulai" class="form-control" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-semibold">Jam Selesai *</label>
                    <input type="time" name="waktu_selesai" class="form-control" required>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Nama Rapat *</label>
                    <input type="text" name="nama_rapat" class="form-control" required placeholder="Contoh: Rapat Koordinasi Kurikulum">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Jenis Rapat</label>
                    <input type="text" name="jenis_rapat" class="form-control" placeholder="Contoh: Rapat Dosen / Akademik">
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Jurusan *</label>
                    <?php if ($user_role === 'user' && !empty($user_jurusan)): ?>
                        <input type="hidden" name="jurusan" id="select_jurusan" value="<?= htmlspecialchars($user_jurusan) ?>" data-id="<?= $user_jurusan_id ?>">
                        <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($user_jurusan) ?>" readonly>
                        <small class="text-muted"><i class="bi bi-lock-fill"></i> Terkunci sesuai jurusan akun login Anda</small>
                    <?php else: ?>
                        <select name="jurusan" id="select_jurusan" class="form-select" required>
                            <option value="">-- Pilih Jurusan --</option>
                            <?php foreach ($jurusan_data as $j): ?>
                            <option value="<?= htmlspecialchars($j['nama_jurusan']) ?>" data-id="<?= $j['id'] ?>">
                                [<?= htmlspecialchars($j['kode_jurusan']) ?>] <?= htmlspecialchars($j['nama_jurusan']) ?>
                            </option>
                            <?php endforeach; ?>
                            <?php if (empty($jurusan_data)): ?>
                            <option disabled>Belum ada jurusan — tambahkan di Master Data</option>
                            <?php endif; ?>
                        </select>
                        <?php if (empty($jurusan_data)): ?>
                        <small class="text-danger"><i class="bi bi-exclamation-circle"></i> 
                            <a href="<?= base_url('jurusan/tambah.php') ?>" target="_blank">Tambah Jurusan</a> terlebih dahulu.
                        </small>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Program Studi</label>
                    <select name="program_magister" id="select_prodi" class="form-select">
                        <option value="">-- Pilih Jurusan dulu --</option>
                    </select>
                    <small class="text-muted" id="prodi_hint"></small>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-semibold">Link Zoom *</label>
                    <input type="url" name="link_zoom" class="form-control" required placeholder="https://zoom.us/j/...">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-semibold">Kode Token / Meeting ID</label>
                    <input type="text" name="token_zoom" class="form-control" placeholder="Contoh: 812 3456 7890">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-semibold">Password Zoom</label>
                    <input type="text" name="password_zoom" class="form-control" placeholder="Contoh: 123456">
                </div>
            </div>

            <?php if ($_SESSION['role'] === 'admin'): ?>
            <div class="row">
                <div class="col-md-12 mb-3">
                    <label class="fw-semibold text-danger"><i class="bi bi-shield-lock-fill"></i> Link Zoom Admin <small class="text-muted fw-normal">(Hanya terlihat oleh Admin)</small></label>
                    <div class="input-group">
                        <span class="input-group-text bg-danger text-white"><i class="bi bi-camera-video-fill"></i></span>
                        <input type="url" name="link_zoom_admin" class="form-control border-danger" placeholder="https://zoom.us/j/... (link khusus admin/host)">
                    </div>
                    <small class="text-muted">Link ini hanya ditampilkan kepada pengguna dengan role Admin.</small>
                </div>
            </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-semibold">Foto Dokumentasi (JPG/PNG)</label>
                    <input type="file" name="dokumentasi" class="form-control" accept=".jpg,.jpeg,.png">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-semibold">Video Hasil Zoom (MP4)</label>
                    <input type="file" name="file_video" class="form-control" accept=".mp4,.avi">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-semibold">Materi Hasil Rapat (PDF/DOC/PPT)</label>
                    <input type="file" name="file_materi" class="form-control" accept=".pdf,.doc,.docx,.ppt,.pptx">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Hasil Rapat</label>
                <textarea name="hasil_rapat" class="form-control" rows="3" placeholder="Catatan atau notulensi hasil rapat..."></textarea>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Keterangan Tambahan</label>
                <textarea name="keterangan" class="form-control" rows="2" placeholder="Catatan tambahan bila ada..."></textarea>
            </div>

            <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Simpan Data</button>
            <a href="index.php" class="btn btn-secondary ms-1">Batal</a>
        </form>
    </div>
</div>

<script>
function loadProdi(jurusanId) {
    const prodiSelect = document.getElementById('select_prodi');
    const hint = document.getElementById('prodi_hint');
    if (!prodiSelect) return;

    prodiSelect.innerHTML = '<option value="">Memuat program studi...</option>';

    if (!jurusanId) {
        prodiSelect.innerHTML = '<option value="">-- Pilih Jurusan dulu --</option>';
        if (hint) hint.textContent = '';
        return;
    }

    fetch(`<?= base_url('prodi/get_by_jurusan.php') ?>?jurusan_id=` + jurusanId)
        .then(r => r.json())
        .then(data => {
            prodiSelect.innerHTML = '<option value="">-- Pilih Program Studi --</option>';
            if (data.length === 0) {
                prodiSelect.innerHTML += '<option disabled>Belum ada prodi untuk jurusan ini</option>';
                if (hint) hint.innerHTML = '<span class="text-warning">Belum ada prodi untuk jurusan ini.</span>';
            } else {
                data.forEach(p => {
                    prodiSelect.innerHTML += `<option value="${p.nama_prodi}">[${p.kode_prodi}] ${p.nama_prodi} (${p.jenjang})</option>`;
                });
                if (hint) hint.textContent = '';
            }
        })
        .catch(() => {
            prodiSelect.innerHTML = '<option value="">Gagal memuat prodi</option>';
        });
}

const selectJurusanEl = document.getElementById('select_jurusan');
if (selectJurusanEl) {
    if (selectJurusanEl.tagName.toLowerCase() === 'select') {
        selectJurusanEl.addEventListener('change', function () {
            const jurusanId = this.options[this.selectedIndex]?.dataset?.id ?? '';
            loadProdi(jurusanId);
        });
    } else {
        // Elemen hidden input untuk user
        const jurusanId = selectJurusanEl.dataset?.id ?? '';
        if (jurusanId) {
            loadProdi(jurusanId);
        }
    }
}
</script>
<?php require_once '../layouts/footer.php'; ?>
