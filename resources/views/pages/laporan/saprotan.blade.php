@extends('layouts.app')

@section('content')
    <x-sim.kerangka-laporan slug="saprotan">
        @include('pages.laporan.isi.saprotan', $isiLaporan)
    </x-sim.kerangka-laporan>
@endsection
