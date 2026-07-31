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

error_reporting(0);

require_once dirname(__DIR__) . '/lib/routeros_api.class.php';
require_once dirname(__DIR__) . '/lib/db.php';

if (PHP_SAPI !== 'cli') {
    exit('CLI only');
}

$sessions = mikhmon_get_sessions_from_config();

if (empty($sessions)) {
    echo '[' . date('Y-m-d H:i:s') . '] No session configured' . PHP_EOL;
    exit(0);
}

foreach ($sessions as $session => $cfg) {
    $API = new RouterosAPI();
    $API->debug = false;
    $API->timeout = 5;
    $API->attempts = 2;
    $API->delay = 1;

    if (!$API->connect($cfg['iphost'], $cfg['userhost'], decrypt($cfg['passwdhost']))) {
        echo '[' . date('Y-m-d H:i:s') . "] [$session] Connection failed: {$cfg['iphost']}" . PHP_EOL;
        continue;
    }

    $scripts = $API->comm('/system/script/print', array(
        '?comment' => 'mikhmon',
        '.proplist' => '.id,name,source,owner,comment',
    ));

    $imported = 0;
    $removed = 0;

    foreach ($scripts as $script) {
        if (mikhmon_upsert_sale($session, $script)) {
            $imported++;
        }
        if (!empty($script['.id'])) {
            $API->comm('/system/script/remove', array('.id' => $script['.id']));
            $removed++;
        }
    }

    $API->disconnect();

    echo '[' . date('Y-m-d H:i:s') . "] [$session] scripts=" . count($scripts)
        . " imported=$imported removed=$removed" . PHP_EOL;
}

exit(0);
