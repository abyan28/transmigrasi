@extends('layouts.app')

@section('content')
    <x-sim.kerangka-laporan slug="transmigran">
        @include('pages.laporan.isi.transmigran', $isiLaporan)
    </x-sim.kerangka-laporan>
@endsection
