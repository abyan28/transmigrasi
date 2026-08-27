{{--
    Laporan Alsintan.

    Kerangka saja per 2026-08-28. Mengikuti berkas rujukan
    "laporan alsintan.jpeg" di refs/. Format kolomnya menyusul dari dinas.

    Laporan terpisah dari Laporan Saprotan, mengikuti dua berkas rujukan
    terpisah, bukan digabung.
--}}
@extends('layouts.app')

@section('content')
    <x-sim.kerangka-laporan slug="alsintan"
        cakupan="Alat dan mesin pertanian milik seluruh kelompok tani di kawasan Kobalima Timur."
        dasar-periode="Dikelompokkan menurut tahun pengadaan bantuan (tahun anggaran)."
        sumber-label="Data Alsintan" :sumber-url="route('alsintan.index')" />
@endsection
