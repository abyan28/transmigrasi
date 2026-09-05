<?php

namespace App\View\Components\header;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class NotificationDropdown extends Component
{
    public function render(): View|Closure|string
    {
        $pengguna = auth()->user();
        $notifikasi = $pengguna?->notifikasi()->latest('created_at')->limit(8)->get() ?? collect();

        return view('components.header.notification-dropdown', [
            'notifikasi' => $notifikasi,
            'belumDibaca' => $pengguna?->notifikasi()->whereNull('dibaca_at')->count() ?? 0,
        ]);
    }
}
