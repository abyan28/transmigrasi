{{--
    Laporan Daftar Poktan.

    Kerangka saja per 2026-08-28. Format kolom (kelompok, ketua, jumlah
    anggota, komoditas, SP) menyusul dari dinas.

    Potret keadaan terkini kelembagaan tani, bukan rekap lintas tahun.
--}}
@extends('layouts.app')

@section('content')
    <x-sim.kerangka-laporan slug="poktan"
        cakupan="Seluruh kelompok tani beserta anggotanya di kawasan transmigrasi Kobalima Timur."
        dasar-periode="Potret keadaan terkini kelembagaan tani, bukan rekap lintas tahun."
        sumber-label="Data Kelompok Tani" :sumber-url="route('poktan.index')" />
@endsection
