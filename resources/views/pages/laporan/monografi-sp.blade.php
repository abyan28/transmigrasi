{{--
    Laporan Monografi SP.

    Kerangka saja per 2026-08-28. Mengikuti berkas rujukan
    "LAPORAN MONOGRAFI UPT KAPITAN MEO 2025.doc" di refs/. Format bab dan
    kolomnya menyusul dari dinas.

    Monografi adalah potret keadaan terkini satu satuan permukiman, bukan
    rekap lintas tahun. Cakupannya satu SP per dokumen; pilihan SP diwarisi
    dari halaman daftar SP lewat pintasan (belum dipasang).
--}}
@extends('layouts.app')

@section('content')
    <x-sim.kerangka-laporan slug="monografi-sp"
        cakupan="Satu satuan permukiman per dokumen, dipilih dari halaman Satuan Permukiman."
        dasar-periode="Potret keadaan terkini SP pada tahun berjalan, bukan rekap lintas tahun."
        sumber-label="Data Satuan Permukiman" :sumber-url="route('sp.index')" />
@endsection
