{{--
    Isian satu status kondisi SP.

    Nama dan keterangan bebas ditentukan dinas, sebab tiap dinas punya istilah
    sendiri: "Perlu Penanganan" di satu kabupaten bisa disebut "Prioritas
    Pembinaan" di kabupaten lain, dan keduanya menunjuk keadaan yang sama.

    YANG TIDAK DAPAT DIUBAH:

    - Jumlah status. Perhitungan hanya mengenal tiga keluaran, sehingga status
      keempat tidak akan pernah tercapai satuan permukiman mana pun.
    - Warna. Hijau, kuning, dan merah menyatakan urutan keparahan, bukan
      selera; menukarnya membuat rekap dasbor terbaca terbalik.
    - Ambang status terendah. Ia penampung sisa dan tetap 0, sebab tanpa itu
      ada skor yang tidak mendapat status sama sekali.
--}}
@php
    $data = $data ?? [];

    $kelasKontrol = 'h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-theme-sm text-gray-800 placeholder:text-gray-400 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-white/30';
    $kelasLabel = 'mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-400';
@endphp

<div class="space-y-6" x-data="{ ambangTerkunci: false }"
    x-effect="ambangTerkunci = Number($modalData?.ambang_bawah ?? 1) === 0">

    <div>
        <label for="ubahStatus_nama" class="{{ $kelasLabel }}">
            Nama Status<span class="text-error-500">*</span>
        </label>
        <input type="text" id="ubahStatus_nama" name="nama" required maxlength="50"
            value="{{ old('nama', $data['nama'] ?? '') }}" class="{{ $kelasKontrol }}" />
        <p class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400">
            Teks ini tampil pada lencana status, dasbor, dan rincian penilaian.
        </p>
    </div>

    <div>
        <label for="ubahStatus_keterangan" class="{{ $kelasLabel }}">
            Keterangan<span class="text-error-500">*</span>
        </label>
        <textarea id="ubahStatus_keterangan" name="keterangan" required rows="2" maxlength="255"
            class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-theme-sm text-gray-800 placeholder:text-gray-400 focus:outline-2 focus:outline-offset-2 focus:outline-brand-500 dark:border-gray-700 dark:text-white/90 dark:placeholder:text-white/30">{{ old('keterangan', $data['keterangan'] ?? '') }}</textarea>
        <p class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400">
            Menjelaskan keadaan layanannya. Label tanpa penjelasan berhenti sebagai stempel.
        </p>
    </div>

    <div x-show="! ambangTerkunci">
        <label for="ubahStatus_ambang" class="{{ $kelasLabel }}">
            Ambang Skor Terendah<span class="text-error-500">*</span>
        </label>
        <input type="number" id="ubahStatus_ambang" name="ambang_bawah" min="1" max="100" step="1"
            :required="! ambangTerkunci" :disabled="ambangTerkunci"
            value="{{ old('ambang_bawah', $data['ambang_bawah'] ?? 0) }}"
            class="{{ $kelasKontrol }} tabular-nums" />
        <p class="mt-1.5 text-theme-xs text-gray-500 dark:text-gray-400">
            Skor terendah yang masih menghasilkan status ini. Wajib lebih besar daripada ambang status
            di bawahnya, sebab pembacaannya berhenti pada ambang tertinggi yang cocok lebih dulu.
        </p>
    </div>

    <div x-show="ambangTerkunci" x-cloak class="rounded-lg bg-gray-50 p-4 dark:bg-white/[0.03]">
        <p class="text-theme-xs text-gray-600 dark:text-gray-400">
            <span class="font-medium text-gray-800 dark:text-white/90">Ambang status ini tetap 0.</span>
            Ia penampung sisa: setiap skor yang tidak memenuhi ambang di atasnya jatuh ke sini. Menaikkannya
            akan membuat sebagian skor tidak mendapat status sama sekali.
        </p>
    </div>

    {{--
        Aturan primer nol disebutkan di sini agar tidak tampak sebagai
        kejanggalan: SP dapat berstatus terendah meski skornya tinggi.
    --}}
    <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-800">
        <p class="text-theme-xs text-gray-600 dark:text-gray-400">
            Ambang bukan satu-satunya penentu. Satuan permukiman yang salah satu parameter primernya tidak
            tersedia sama sekali langsung berstatus terendah, berapa pun skornya, sebab rata-rata tidak boleh
            menutupi ketiadaan hal yang mutlak.
        </p>
    </div>
</div>