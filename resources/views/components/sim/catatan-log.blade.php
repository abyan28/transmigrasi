{{--
    Riwayat perubahan satu baris data, untuk tab "Catatan Log" pada halaman
    rincian.

    Menjawab pertanyaan yang selalu muncul saat membaca data: siapa yang
    memasukkannya, siapa yang pernah mengubahnya, dan kapan. Sebelumnya
    jawabannya hanya ada di halaman Audit Log yang memuat seluruh sistem,
    sehingga petugas harus menelusuri sendiri baris mana yang menyangkut data
    yang sedang dibukanya.

    Halaman Audit Log tetap ada dan tidak digantikan komponen ini. Keduanya
    menjawab pertanyaan berbeda: audit log menjawab "apa saja yang terjadi hari
    ini di seluruh sistem", sedangkan tab ini menjawab "apa yang pernah terjadi
    pada data ini saja".

    Entri PALING BARU diletakkan di atas, sebab yang pertama dicari pembaca
    biasanya perubahan terakhir, bukan asal-usulnya.

    Pemakaian:
        <x-sim.catatan-log nama-tabel="transmigran" :record-id="$data['id_transmigran']" />
--}}
@props([
    'namaTabel',
    'recordId',
])

@php
    $riwayat = \App\Support\DummyData::riwayatData($namaTabel, (int) $recordId);

    // Warna badge per jenis aksi, disamakan dengan halaman audit log agar
    // petugas tidak perlu belajar dua sandi warna yang berbeda.
    $warnaAksi = [
        'Tambah' => 'teal',
        'Ubah' => 'warning',
        'Hapus' => 'error',
        'Pulihkan' => 'success',
        'Reset Kata Sandi' => 'warning',
        'Nonaktifkan Akun' => 'error',
        'Aktifkan Akun' => 'success',
        'Ubah Izin Role' => 'warning',
    ];
@endphp

<div {{ $attributes->merge(['class' => 'p-5 sm:p-6']) }}>
    @if ($riwayat === [])
        {{--
            Keadaan kosong dibedakan dari "belum ada data": riwayat kosong
            berarti datanya memang belum pernah disentuh sejak dicatat, bukan
            berarti pencatatannya gagal.
        --}}
        <x-sim.empty-state judul="Belum ada perubahan tercatat"
            pesan="Data ini belum pernah diubah sejak dimasukkan. Setiap penambahan, perubahan, dan penghapusan akan tercatat di sini beserta pelakunya." />
    @else
        <ol class="relative space-y-6 border-l border-gray-200 pl-6 dark:border-gray-700">
            @foreach ($riwayat as $jejak)
                <li class="relative">
                    <span
                        class="absolute -left-[1.9rem] mt-1 flex h-3 w-3 rounded-full bg-brand-500 ring-4 ring-white dark:ring-gray-900"
                        aria-hidden="true"></span>

                    <div class="flex flex-wrap items-center gap-2">
                        <x-sim.status-badge :teks="$jejak['aksi']"
                            :warna="$warnaAksi[$jejak['aksi']] ?? 'gray'" ukuran="sm" />
                        <span class="text-theme-xs tabular-nums text-gray-500 dark:text-gray-400">
                            {{ \Illuminate\Support\Carbon::parse($jejak['waktu'])->translatedFormat('d F Y, H:i') }} WITA
                        </span>
                    </div>

                    <p class="mt-1.5 text-theme-sm text-gray-800 dark:text-white/90">
                        {{ $jejak['ringkasan'] }}
                    </p>

                    <p class="mt-1 text-theme-xs text-gray-500 dark:text-gray-400">
                        oleh {{ $jejak['pengguna'] }}
                        <span class="tabular-nums">&middot; {{ $jejak['ip_address'] }}</span>
                    </p>
                </li>
            @endforeach
        </ol>

        <p class="mt-6 rounded-lg bg-gray-50 p-3.5 text-theme-xs text-gray-600 dark:bg-white/[0.03] dark:text-gray-400">
            Riwayat ini hanya memuat perubahan pada data yang sedang dibuka.
            Untuk menelusuri seluruh tindakan di sistem, buka
            <a href="{{ route('audit-log') }}"
                class="rounded font-medium text-teal-700 hover:underline focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:text-teal-300">Audit
                Log</a>.
        </p>
    @endif
</div>
