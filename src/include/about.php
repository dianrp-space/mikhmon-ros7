<?php
/*
 *  Copyright (C) 2018 Laksamadi Guko.
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
session_start();
// hide all error
error_reporting(0);
if (!isset($_SESSION["mikhmon"])) {
  header("Location:../admin.php?id=login");
} else {
}
?>
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <h3><i class="fa fa-info-circle"></i> About</h3>
      </div>
      <div class="card-body">
        <h3>mikhmon <?= $_SESSION['v']; ?></h3>
<p>
  Mikhmon (MikroTik Hotspot Monitor) adalah aplikasi manajemen voucher hotspot
  berbasis web. Versi ini dioptimalkan untuk RouterOS v7 dan dikemas dalam Docker.
</p>
<p>
  <ul>
    <li>
      Author asli : Laksamadi Guko
    </li>
    <li>
      Lisensi : <a href="https://github.com/laksa19/mikhmonv2/blob/master/LICENSE">GPLv2</a>
    </li>
    <li>
      API Class : <a href="https://github.com/BenMenking/routeros-api">routeros-api</a>
    </li>
    <li>
      Website asli : <a href="https://laksa19.github.io">laksa19.github.io</a>
    </li>
    <li>
      Modifikasi : <a href="https://dianrp.com" target="_blank">dianrp.com</a>
    </li>
  </ul>
</p>
<p>
  Terima kasih untuk semua yang telah mendukung pengembangan MIKHMON.
</p>
<div>
    <i>Copyright &copy; 2018 Laksamadi Guko</i>
</div>
</div>
</div>
</div>
<div class="col-12">
<div class="card">
  <div class="card-header">
  <h3><i class="fa fa-info-circle"></i> Changelog</h3>
  </div>
  <div class="card-body">
  <ul>
    <li><b>Report penjualan SQLite</b> - data penjualan disimpan di SQLite dan di-sync otomatis dari router, tidak lagi menumpuk sebagai script.</li>
    <li><b>Analitik laporan penjualan</b> - kartu statistik (omzet, rata-rata, pertumbuhan, perangkat unik) dan grafik penjualan harian, profil terlaris, serta pelanggan terbanyak.</li>
    <li><b>Dukungan port API kustom</b> - koneksi RouterOS melalui port forwarding / VPN (format <code>host:port</code>).</li>
    <li><b>Docker production-ready</b> - berbasis <code>php:8.3-apache</code>, volume persisten, healthcheck, dan cron sync.</li>
    <li><b>Rebranding</b> - logo, favicon, dan teks modifikasi mengarah ke dianrp.com.</li>
  </ul>
  </div>
</div>
</div>
</div>
