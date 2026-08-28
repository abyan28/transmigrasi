@extends('layouts.app')

@section('content')
    <x-sim.kerangka-laporan slug="alsintan">
        @include('pages.laporan.isi.alsintan', $isiLaporan)
    </x-sim.kerangka-laporan>
@endsection
