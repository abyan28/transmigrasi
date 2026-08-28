@extends('layouts.app')

@section('content')
    <x-sim.kerangka-laporan slug="poktan">
        @include('pages.laporan.isi.poktan', $isiLaporan)
    </x-sim.kerangka-laporan>
@endsection
