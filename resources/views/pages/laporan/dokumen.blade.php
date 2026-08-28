{{--
    Tampilan dokumen polos satu laporan, dibuka di tab baru dari halaman
    berbingkai. Kerangka dan isi tabelnya sama persis dengan halaman
    berbingkai -- keduanya membaca metadata dari LaporanData::meta() dan
    meng-include partial yang sama di pages/laporan/isi.
--}}
@extends('layouts.dokumen')

@section('content')
    <x-sim.kerangka-laporan :slug="$slug" :dokumen="true">
        @include('pages.laporan.isi.' . $slug, $isiLaporan)
    </x-sim.kerangka-laporan>
@endsection
