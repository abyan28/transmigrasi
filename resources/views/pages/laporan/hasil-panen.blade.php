@extends('layouts.app')

@section('content')
    <x-sim.kerangka-laporan slug="hasil-panen">
        @include('pages.laporan.isi.hasil-panen', $isiLaporan)
    </x-sim.kerangka-laporan>
@endsection
