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

	if ($removereport != "") {
		include_once(__DIR__ . '/../lib/db.php');
		$uids = explode("~", $removereport);
		$pdo = mikhmon_pdo();
		$stmt = $pdo->prepare('DELETE FROM sales WHERE session = :session AND id = :id');
		foreach ($uids as $uid) {
			$uid = trim($uid);
			if (is_numeric($uid)) {
				$stmt->execute(array(':session' => $session, ':id' => $uid));
			}
		}
		$_SESSION[$session.'idhr'] = "";
	}
	echo "<script>window.location='./?report=selling".$_SESSION['report']."&session=" . $session . "'</script>";