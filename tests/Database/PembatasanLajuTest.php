<?php

/*
 * Task 3.10 -- Pembatasan laju per jenis akses (`rules.md` 14c).
 *
 * Batas dimatikan di lingkungan `testing` (phpunit.xml SIM_BATAS_LAJU=false)
 * supaya uji penjaga penyapu rute tidak terkena. Berkas ini menyalakannya
 * sendiri lewat config() dengan angka kecil.
 */

use App\Models\Role;
use App\Models\User;

require_once __DIR__.'/DatabaseHelpers.php';

beforeEach(function () {
    config(['sim.batas_laju.aktif' => true]);
});

/** Pengguna terautentikasi ber-`semuaIzin` (lolos middleware izin). */
function penggunaBerizin(): User
{
    $user = User::factory()->create(['role_id' => Role::factory()->create()->id_role]);
    $user->semuaIzin = true;
    test()->actingAs($user);

    return $user;
}

it('membatasi halaman baca internal per akun', function () {
    config(['sim.batas_laju.baca_internal' => 3]);
    penggunaBerizin();

    for ($i = 0; $i < 3; $i++) {
        $this->get(route('tentang'))->assertOk();
    }

    $this->get(route('tentang'))
        ->assertStatus(429)
        ->assertSee('Tunggu sebentar', false);
});

it('membatasi halaman tulis internal dengan angka tersendiri', function () {
    config(['sim.batas_laju.tulis_internal' => 2]);
    penggunaBerizin();

    // Rute tulis mana pun; badan kosong -> 302 balik (tetap satu "ketukan").
    for ($i = 0; $i < 2; $i++) {
        $this->post(route('pengguna.simpan'))->assertStatus(302);
    }

    $this->post(route('pengguna.simpan'))->assertStatus(429);
});

it('menghitung batas internal per akun, bukan per alamat IP', function () {
    config(['sim.batas_laju.baca_internal' => 2]);

    $a = penggunaBerizin();
    $this->get(route('tentang'))->assertOk();
    $this->get(route('tentang'))->assertOk();
    $this->get(route('tentang'))->assertStatus(429);

    // Akun kedua dari "IP" yang sama tetap punya jatah penuh.
    $this->actingAs(tap(User::factory()->create(['role_id' => $a->role_id]), fn ($u) => $u->semuaIzin = true));
    $this->get(route('tentang'))->assertOk();
});

it('mengecualikan rute berkas besar dari batas halaman baca', function () {
    config(['sim.batas_laju.baca_internal' => 2, 'sim.batas_laju.berkas_besar' => 20]);
    penggunaBerizin();

    // Lebih dari batas baca (2), masih di bawah batas berkas besar (20).
    for ($i = 0; $i < 6; $i++) {
        $this->get(route('template-impor', ['entitas' => 'transmigran']))
            ->assertOk();
    }
});

it('membatasi pelacakan pengaduan publik per alamat IP', function () {
    config(['sim.batas_laju.lacak_publik' => 3]);

    for ($i = 0; $i < 3; $i++) {
        $this->get(route('lacak-pengaduan'))->assertOk();
    }

    $this->get(route('lacak-pengaduan'))
        ->assertStatus(429)
        ->assertSee('coba lagi', false);
});

it('membatasi pengiriman pengaduan publik 3 per jam per IP', function () {
    config(['sim.batas_laju.kirim_pengaduan' => 3]);

    for ($i = 0; $i < 3; $i++) {
        $this->post(route('pengaduan-warga.kirim'), ['deskripsi_pengaduan' => 'uji'])->assertStatus(302);
    }

    $this->post(route('pengaduan-warga.kirim'), ['deskripsi_pengaduan' => 'uji'])
        ->assertStatus(429)
        ->assertSee('satu jam lagi', false);
});

it('tidak membatasi apa pun ketika config batas_laju.aktif false', function () {
    config(['sim.batas_laju.aktif' => false, 'sim.batas_laju.baca_internal' => 1]);
    penggunaBerizin();

    $this->get(route('tentang'))->assertOk();
    $this->get(route('tentang'))->assertOk();
    $this->get(route('tentang'))->assertOk();
});
