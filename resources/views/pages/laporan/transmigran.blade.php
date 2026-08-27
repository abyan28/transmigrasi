{{--
    Laporan Daftar Transmigran.

    Kerangka saja per 2026-08-28. Satu laporan lintas modul: tiap transmigran
    ditampilkan beserta data Rumah dan data Lahan yang melekat padanya.
    Format kolomnya menyusul dari dinas.

    Laporan lintas modul memakai pemilih periode sendiri (rules.md 12 poin
    10), sebab tidak ada satu daftar asal yang dapat mewariskan filter.
    Pemilih itu dikerjakan pada tahap berikutnya.
--}}
@extends('layouts.app')

@section('content')
    <x-sim.kerangka-laporan slug="transmigran"
        cakupan="Seluruh kepala keluarga transmigran di kawasan Kobalima Timur, beserta data rumah dan lahannya."
        dasar-periode="Potret keadaan terkini; pemilih periode tersendiri untuk laporan lintas modul menyusul."
        sumber-label="Data Transmigran" :sumber-url="route('transmigran.index')" />
@endsection
