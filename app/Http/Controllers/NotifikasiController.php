<?php

namespace App\Http\Controllers;

use App\Support\Paginasi;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotifikasiController extends Controller
{
    public function index(Request $request): View
    {
        $baris = $request->user()->notifikasi()
            ->latest('created_at')
            ->latest('id_notifikasi')
            ->paginate(Paginasi::perHalaman($request))
            ->withQueryString();

        return view('pages.notifikasi.index', [
            'title' => 'Notifikasi',
            'baris' => $baris,
        ]);
    }

    public function baca(Request $request, int $id): RedirectResponse
    {
        $notifikasi = $request->user()->notifikasi()->findOrFail($id);
        $notifikasi->update(['dibaca_at' => $notifikasi->dibaca_at ?? now()]);

        return redirect()->to($notifikasi->urlTujuan());
    }

    public function bacaSemua(Request $request): RedirectResponse
    {
        $request->user()->notifikasi()->whereNull('dibaca_at')->update(['dibaca_at' => now()]);

        return back()->with('sukses', 'Semua notifikasi ditandai sudah dibaca.');
    }
}
