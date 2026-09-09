@extends('layouts.app')

@section('content')
    <x-sim.kerangka-laporan slug="monografi-sp" :isi-laporan="$isiLaporan">
        @include('pages.laporan.isi.monografi-sp', $isiLaporan)
    </x-sim.kerangka-laporan>
@endsection
