<?php

namespace Database\Seeders;

use App\Models\Pengaduan;
use App\Support\LayananNotifikasi;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DemoSeeder extends Seeder
{
    private array $spIds = [];

    public function run(): void
    {
        $this->call(DatabaseSeeder::class);
        $this->spIds = DB::table('satuan_permukiman')->orderBy('id_satuan_permukiman')->pluck('id_satuan_permukiman')->all();

        Model::withoutEvents(function (): void {
            $this->transmigran();
            $this->anggotaKeluarga();
            $this->rumah();
            $this->lahan();
            $this->poktan();
            $this->asetPertanian();
            $this->produksi();
            $this->pengaduan();
            $this->asetSp();
        });
    }

    private function transmigran(): void
    {
        $nama = ['YOHANIS BERE', 'MARIA NAHAK', 'PETRUS SERAN', 'LUSIA BRIA', 'ANTONIUS KLAU', 'THERESIA ASA', 'GABRIEL LEKI', 'YULIANA FAHIK'];
        for ($i = DB::table('transmigran')->count() + 1; $i <= 90; $i++) {
            DB::table('transmigran')->insert([
                'uuid' => (string) Str::uuid(),
                'satuan_permukiman_id' => $this->spIds[($i - 1) % count($this->spIds)],
                'nik' => sprintf('5399%012d', $i),
                'no_kk' => sprintf('5388%012d', $i),
                'nama_kepala_keluarga' => $nama[($i - 1) % count($nama)].' '.str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                'jenis_kelamin' => $i % 3 ? 'Laki-laki' : 'Perempuan',
                'agama' => $i % 4 ? 'Katolik' : 'Kristen',
                'tempat_lahir' => 'Malaka',
                'tanggal_lahir' => sprintf('%d-%02d-%02d', 1965 + ($i % 30), ($i % 12) + 1, ($i % 27) + 1),
                'pendidikan_terakhir' => ['SD', 'SMP', 'SMA/SMK', 'Diploma'][$i % 4],
                'pekerjaan_kepala_keluarga' => ['Petani', 'Peternak', 'Pedagang', 'Tukang'][$i % 4],
                'pendapatan_per_bulan' => 900000 + ($i % 8) * 250000,
                'tahun_kedatangan' => 2000 + ($i % 20),
                'status_tinggal' => $i % 15 === 0 ? 'Pindah Penduduk' : 'Aktif',
                'tahun_keluar' => $i % 15 === 0 ? 2024 + ($i % 2) : null,
                'status_anggota_poktan' => $i % 5 ? 'Ya' : 'Tidak',
                'status_sertifikat' => ['Sudah', 'Belum', 'Belum Didata'][$i % 3],
                'telepon' => '0813'.str_pad((string) $i, 8, '0', STR_PAD_LEFT),
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    private function anggotaKeluarga(): void
    {
        $keluarga = DB::table('transmigran')->orderBy('id_transmigran')->pluck('id_transmigran')->all();
        for ($i = DB::table('anggota_keluarga')->count() + 1; $i <= 270; $i++) {
            DB::table('anggota_keluarga')->insert([
                'transmigran_id' => $keluarga[($i - 1) % count($keluarga)],
                'hubungan' => $i % 3 === 1 ? 'Istri' : 'Anak',
                'nama_lengkap' => 'ANGGOTA KELUARGA '.str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                'nik' => sprintf('5377%012d', $i),
                'jenis_kelamin' => $i % 2 ? 'Perempuan' : 'Laki-laki',
                'tempat_lahir' => 'Malaka',
                'tanggal_lahir' => sprintf('%d-%02d-%02d', 1980 + ($i % 35), ($i % 12) + 1, ($i % 27) + 1),
                'agama' => 'Katolik',
                'kegiatan' => $i % 3 === 1 ? 'Bekerja' : 'Masih Sekolah',
                'pendidikan_terakhir' => ['SD', 'SMP', 'SMA/SMK'][$i % 3],
                'status' => 'Aktif',
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    private function rumah(): void
    {
        $terpakai = DB::table('rumah')->whereNotNull('transmigran_id')->pluck('transmigran_id')->all();
        $keluarga = DB::table('transmigran')->whereNotIn('id_transmigran', $terpakai)->orderBy('id_transmigran')->get();
        $urutan = DB::table('rumah')->count();
        foreach ($keluarga as $keluargaBaru) {
            if (++$urutan > 88) {
                break;
            }
            DB::table('rumah')->insert([
                'uuid' => (string) Str::uuid(),
                'satuan_permukiman_id' => $keluargaBaru->satuan_permukiman_id,
                'transmigran_id' => $keluargaBaru->id_transmigran,
                'no_rumah' => 'R-'.str_pad((string) $urutan, 3, '0', STR_PAD_LEFT),
                'kondisi' => ['Tidak Rusak', 'Rusak Ringan', 'Rusak Berat'][$urutan % 3],
                'status_hunian' => 'Dihuni',
                'tahun_pembangunan' => 2000 + ($urutan % 20),
                'luas_bangunan' => 36 + ($urutan % 4) * 9,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        while (DB::table('rumah')->count() < 90) {
            $i = DB::table('rumah')->count() + 1;
            DB::table('rumah')->insert([
                'uuid' => (string) Str::uuid(), 'satuan_permukiman_id' => $this->spIds[$i % count($this->spIds)],
                'no_rumah' => 'R-'.str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                'kondisi' => 'Rusak Ringan', 'status_hunian' => 'Tidak Dihuni',
                'alasan_tidak_dihuni' => 'Menunggu penempatan penghuni.',
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    private function lahan(): void
    {
        $terpakai = DB::table('lahan')->pluck('transmigran_id')->all();
        $keluarga = DB::table('transmigran')->whereNotIn('id_transmigran', $terpakai)->orderBy('id_transmigran')->get();
        foreach ($keluarga as $baris) {
            if (DB::table('lahan')->count() >= 85) {
                break;
            }
            $i = DB::table('lahan')->count() + 1;
            DB::table('lahan')->insert([
                'uuid' => (string) Str::uuid(), 'transmigran_id' => $baris->id_transmigran,
                'satuan_permukiman_id' => $baris->satuan_permukiman_id,
                'kode_lahan' => 'LH-'.str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                'luas_pekarangan' => .25, 'luas_usaha' => 2, 'luas_kering' => $i % 3 ? 2 : 1,
                'luas_basah' => $i % 3 ? 0 : 1, 'tujuan_pemanfaatan' => 'Tanaman pangan',
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    private function poktan(): void
    {
        while (DB::table('poktan')->count() < 30) {
            $i = DB::table('poktan')->count() + 1;
            DB::table('poktan')->insert([
                'satuan_permukiman_id' => $this->spIds[($i - 1) % count($this->spIds)],
                'slug' => 'poktan-demo-'.$i, 'nama' => 'POKTAN DEMO '.str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                'asal_ketua' => 'Bukan Transmigran', 'nama_ketua' => 'KETUA DEMO '.$i,
                'nik_ketua' => sprintf('5366%012d', $i), 'tahun_berdiri' => 2010 + ($i % 14),
                // Lahan ketua diisi LANGSUNG (asal_ketua = Bukan Transmigran,
                // sehingga RekapPoktan::kekuatan() membaca luas kelompok dari
                // kolom ini, bukan lahan anggota) -- tanpa ini seluruh poktan
                // demo berluas 0 ha, dan penanaman apa pun di atasnya akan
                // ditolak "melebihi lahan yang belum ditanami" begitu formulir
                // sungguhannya dibuka dan disimpan ulang.
                'luas_kering_ketua' => 3.0 + ($i % 3), 'luas_basah_ketua' => 2.0 + ($i % 2),
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    private function asetPertanian(): void
    {
        $alsintan = DB::table('alsintan')->first();
        while ($alsintan && DB::table('alsintan')->count() < 35) {
            $i = DB::table('alsintan')->count() + 1;
            DB::table('alsintan')->insert([
                'jenis_alsintan' => $alsintan->jenis_alsintan,
                'nama_alat' => 'ALSINTAN DEMO '.$i, 'jumlah_total' => 1 + ($i % 5),
                'tahun_pengadaan' => 2018 + ($i % 8), 'sumber_dana' => $alsintan->sumber_dana,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        // Dicontoh dari SELURUH saprotan ber-jenis Benih yang ada (bukan cuma
        // baris pertama): `produksi()` menanam memakai benih ini, dan
        // menyamakan komoditasnya pada 40 baris membuat seluruh penanaman
        // demo seragam satu komoditas saja.
        $templateBenih = DB::table('saprotan')->where('jenis', 'Benih')->get();
        while ($templateBenih->isNotEmpty() && DB::table('saprotan')->count() < 40) {
            $i = DB::table('saprotan')->count() + 1;
            $template = $templateBenih[($i - 1) % $templateBenih->count()];
            DB::table('saprotan')->insert([
                'satuan_id' => $template->satuan_id, 'komoditas_id' => $template->komoditas_id,
                'jenis' => $template->jenis, 'varietas' => $template->varietas,
                'nama' => 'SAPROTAN DEMO '.$i,
                'jumlah_total' => 100 + $i * 5, 'tahun_pengadaan' => 2020 + ($i % 6),
                'sumber_dana' => $template->sumber_dana,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    private function produksi(): void
    {
        $poktanIds = DB::table('poktan')->orderBy('id_poktan')->pluck('id_poktan')->all();
        $saprotanBenih = DB::table('saprotan')->where('jenis', 'Benih')->get();
        $komoditas = DB::table('komoditas')->get()->keyBy('id_komoditas');
        if ($poktanIds === [] || $saprotanBenih->isEmpty() || $komoditas->isEmpty()) {
            return;
        }
        while (DB::table('penanaman')->count() < 60) {
            $i = DB::table('penanaman')->count() + 1;
            $poktanId = $poktanIds[($i - 1) % count($poktanIds)];
            $saprotan = $saprotanBenih[($i - 1) % $saprotanBenih->count()];

            // Distribusi BARU khusus baris ini, bukan dipinjam dari baris
            // demo/contoh lain: menjamin poktan/komoditas/benih tetap sepadan
            // (`PenanamanController::validasiLanjutan`) dan tidak pernah
            // berbagi jatah dengan penanaman lain, sehingga menyunting lalu
            // menyimpan ulang salah satu baris tidak pernah melebihi sisa
            // benih atau lahan kelompok yang belum ditanami.
            $jumlahBenih = 10.0;
            $distribusiId = DB::table('saprotan_distribusi')->insertGetId([
                'saprotan_id' => $saprotan->id_saprotan, 'poktan_id' => $poktanId,
                'jumlah' => $jumlahBenih, 'tanggal_serah' => now()->subDays($i)->toDateString(),
                'created_at' => now(), 'updated_at' => now(),
            ]);

            DB::table('penanaman')->insert([
                'poktan_id' => $poktanId,
                'komoditas_id' => $saprotan->komoditas_id,
                'saprotan_distribusi_id' => $distribusiId,
                'volume_benih' => $jumlahBenih * .8,
                // 1,0-2,0 ha; dua baris per poktan (60 penanaman / 30 poktan)
                // tetap di bawah luas ketua (5-8 ha) yang diisi `poktan()`.
                'realisasi_tanam' => 1 + ($i % 3) * .5,
                'periode_tanam' => (2022 + ($i % 4)).'-'.str_pad((string) (($i % 12) + 1), 2, '0', STR_PAD_LEFT),
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        $sudah = DB::table('hasil_panen')->pluck('penanaman_id')->all();
        $tanam = DB::table('penanaman')->whereNotIn('id_penanaman', $sudah)->orderBy('id_penanaman')->get();
        foreach ($tanam as $baris) {
            if (DB::table('hasil_panen')->count() >= 50) {
                break;
            }
            $komoditasBaris = $komoditas[$baris->komoditas_id];
            $luas = (float) $baris->realisasi_tanam;
            $produktivitas = 3 + ($baris->id_penanaman % 4) * .5;
            DB::table('hasil_panen')->insert([
                'uuid' => (string) Str::uuid(), 'penanaman_id' => $baris->id_penanaman,
                'satuan_id' => $komoditasBaris->satuan_id,
                'periode_panen' => substr($baris->periode_tanam, 0, 4).'-'.str_pad((string) (((int) substr($baris->periode_tanam, 5, 2) + 3 - 1) % 12 + 1), 2, '0', STR_PAD_LEFT),
                'realisasi_panen' => $luas, 'puso' => 0, 'produktivitas' => $produktivitas,
                'produksi' => $luas * $produktivitas, 'harga_jual' => 4000 + ($baris->id_penanaman % 5) * 250,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    private function pengaduan(): void
    {
        while (DB::table('pengaduan')->count() < 60) {
            $i = DB::table('pengaduan')->count() + 1;
            $status = ['Menunggu Diterima', 'Diterima', 'Diproses', 'Selesai'][$i % 4];
            $prioritas = ['Rendah', 'Sedang', 'Tinggi', 'Mendesak'][($i + 1) % 4];
            $id = DB::table('pengaduan')->insertGetId([
                'uuid' => (string) Str::uuid(), 'nama_pelapor' => 'WARGA DEMO '.$i,
                'kontak_pelapor' => '0821'.str_pad((string) $i, 8, '0', STR_PAD_LEFT),
                'sumber_laporan' => 'Publik', 'ip_pelapor' => '10.20.1.'.(($i % 200) + 1),
                'satuan_permukiman_id' => $this->spIds[$i % count($this->spIds)],
                'nomor_pengaduan' => 'PGD-DEMO-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'tanggal_pengaduan' => now()->subDays($i % 180)->toDateString(),
                'kategori' => ['Rumah', 'Infrastruktur', 'Pertanian', 'Bencana'][$i % 4],
                'bidang' => $i % 4 === 3 ? null : ($i % 3 === 0 ? 'Pertanian' : 'Ketransmigrasian'),
                'judul' => 'PENGADUAN DEMO '.str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                'deskripsi' => 'Data demonstrasi untuk pengujian alur pengaduan.',
                'status' => $status, 'prioritas' => $prioritas,
                'created_at' => now(), 'updated_at' => now(),
            ], 'id_pengaduan');

            // Lewat mesin notifikasi sungguhan (bukan `DB::table()->insert()`
            // yang dulunya diam-diam melewatinya): data demo ikut mengisi
            // dropdown notifikasi & riwayat `penilaian_sp` secara nyata,
            // bukan cuma tabel `pengaduan`-nya saja.
            LayananNotifikasi::pengaduanBaru(Pengaduan::findOrFail($id));
        }
    }

    private function asetSp(): void
    {
        foreach (['inventaris_sp' => 30, 'fasilitas_sp' => 30] as $tabel => $target) {
            $contoh = DB::table($tabel)->first();
            $spTerdampak = [];
            while ($contoh && DB::table($tabel)->count() < $target) {
                $data = (array) $contoh;
                unset($data[$tabel === 'inventaris_sp' ? 'id_inventaris_sp' : 'id_fasilitas_sp'], $data['deleted_at']);
                $i = DB::table($tabel)->count() + 1;
                $data['satuan_permukiman_id'] = $this->spIds[$i % count($this->spIds)];
                $spTerdampak[] = $data['satuan_permukiman_id'];
                $data[$tabel === 'inventaris_sp' ? 'nama_barang' : 'nama_fasilitas'] = strtoupper($tabel).' DEMO '.$i;
                $data['created_at'] = now();
                $data['updated_at'] = now();
                DB::table($tabel)->insert($data);
            }

            // Hanya `fasilitas_sp` yang ikut dihitung `PenilaianKondisiSp`
            // (`inventaris_sp` tak pernah dirujuk parameter penilaian mana
            // pun) -- menghitung ulang di sini membuat riwayat `penilaian_sp`
            // demo ikut nyata, bukan cuma tabelnya saja yang terisi.
            if ($tabel === 'fasilitas_sp' && $spTerdampak !== []) {
                LayananNotifikasi::hitungUlangSp($spTerdampak);
            }
        }
    }
}
