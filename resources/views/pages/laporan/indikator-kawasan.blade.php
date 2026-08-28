@extends('layouts.app')

@section('content')
    <x-sim.kerangka-laporan slug="indikator-kawasan">
        @include('pages.laporan.isi.indikator-kawasan', $isiLaporan)
    </x-sim.kerangka-laporan>
@endsection
