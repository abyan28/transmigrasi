{{--
    Rekap Indikator Kawasan.

    Kerangka saja per 2026-08-28. Sumber seluruh indikatornya adalah
    dashboard (rules.md 12 poin 11): dashboard memang penampung indikator
    kawasan dan tidak punya tabel padanan di modul mana pun.

    Indikator produksi memakai tahun panen ("apa yang terjadi tahun ini"),
    beda dari Laporan Hasil Panen yang memakai tahun pengadaan bantuan
    (rules.md 9 poin 16; basis tahun dipisah menurut tujuannya).
--}}
@extends('layouts.app')

@section('content')
    <x-sim.kerangka-laporan slug="indikator-kawasan"
        cakupan="Seluruh kawasan transmigrasi Kobalima Timur, gabungan seluruh satuan permukiman."
        dasar-periode="Keadaan terkini kawasan; indikator produksi memakai tahun panen, bukan tahun pengadaan bantuan."
        sumber-label="Dashboard" :sumber-url="route('beranda')" />
@endsection
