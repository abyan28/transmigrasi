@extends('layouts.app')

@section('content')
    <x-sim.kerangka-laporan slug="monografi-sp">
        @include('pages.laporan.isi.monografi-sp', $isiLaporan)
    </x-sim.kerangka-laporan>
@endsection
