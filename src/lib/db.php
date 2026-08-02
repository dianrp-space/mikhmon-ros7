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

if (!defined('MIKHMON_DB_FILE')) {
    define('MIKHMON_DB_FILE', dirname(__DIR__) . '/data/mikhmon.db');
}

function mikhmon_pdo()
{
    static $pdo = null;
    if ($pdo === null) {
        $dir = dirname(MIKHMON_DB_FILE);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $pdo = new PDO('sqlite:' . MIKHMON_DB_FILE);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS sales (
                id       INTEGER PRIMARY KEY AUTOINCREMENT,
                session  TEXT NOT NULL DEFAULT "",
                source   TEXT NOT NULL DEFAULT "",
                owner    TEXT NOT NULL DEFAULT "",
                name     TEXT NOT NULL DEFAULT "",
                tdate    TEXT NOT NULL DEFAULT "",
                ttime    TEXT NOT NULL DEFAULT "",
                username TEXT NOT NULL DEFAULT "",
                price    TEXT NOT NULL DEFAULT "",
                address  TEXT NOT NULL DEFAULT "",
                mac      TEXT NOT NULL DEFAULT "",
                validity TEXT NOT NULL DEFAULT "",
                profile  TEXT NOT NULL DEFAULT "",
                comment  TEXT NOT NULL DEFAULT "",
                synced_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE (session, name)
            )'
        );
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_sales_session ON sales(session)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_sales_source ON sales(source)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_sales_owner ON sales(owner)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_sales_username ON sales(username)');
    }
    return $pdo;
}

function mikhmon_normalize_date($date)
{
    $date = trim($date);
    if (preg_match('#^[0-9]{4}-[0-9]{2}-[0-9]{2}$#', $date)) {
        return $date;
    }
    if (preg_match('#^([a-zA-Z]{3})/([0-9]{2})/([0-9]{4})$#', $date, $m)) {
        $months = array(
            'jan' => '01', 'feb' => '02', 'mar' => '03', 'apr' => '04', 'may' => '05', 'jun' => '06',
            'jul' => '07', 'aug' => '08', 'sep' => '09', 'oct' => '10', 'nov' => '11', 'dec' => '12',
        );
        $mon = strtolower($m[1]);
        if (isset($months[$mon])) {
            return $m[3] . '-' . $months[$mon] . '-' . $m[2];
        }
    }
    return $date;
}

function mikhmon_sale_owner($date)
{
    if (preg_match('#^([0-9]{4})-([0-9]{2})-[0-9]{2}$#', $date, $m)) {
        return $m[2] . $m[1];
    }
    return '';
}

function mikhmon_upsert_sale($session, $script)
{
    $name = isset($script['name']) ? $script['name'] : '';
    if ($name == '') {
        return false;
    }
    $parts = explode('-|-', $name);
    $date = mikhmon_normalize_date(isset($parts[0]) ? $parts[0] : '');
    $pdo = mikhmon_pdo();
    $stmt = $pdo->prepare(
        'INSERT INTO sales (session, source, owner, name, tdate, ttime, username, price, address, mac, validity, profile, comment)
         VALUES (:session, :source, :owner, :name, :tdate, :ttime, :username, :price, :address, :mac, :validity, :profile, :comment)
         ON CONFLICT (session, name) DO NOTHING'
    );
    $stmt->execute(array(
        ':session'  => $session,
        ':source'   => $date,
        ':owner'    => mikhmon_sale_owner($date),
        ':name'     => $name,
        ':tdate'    => $date,
        ':ttime'    => isset($parts[1]) ? $parts[1] : '',
        ':username' => isset($parts[2]) ? $parts[2] : '',
        ':price'    => isset($parts[3]) ? $parts[3] : '',
        ':address'  => isset($parts[4]) ? $parts[4] : '',
        ':mac'      => isset($parts[5]) ? $parts[5] : '',
        ':validity' => isset($parts[6]) ? $parts[6] : '',
        ':profile'  => isset($parts[7]) ? $parts[7] : '',
        ':comment'  => isset($parts[8]) ? $parts[8] : '',
    ));
    return $stmt->rowCount() > 0;
}

function mikhmon_get_sales($session, $source = '', $owner = '')
{
    $pdo = mikhmon_pdo();
    $sql = 'SELECT * FROM sales WHERE session = :session';
    $params = array(':session' => $session);
    if ($source != '') {
        $sql .= ' AND source = :source';
        $params[':source'] = $source;
    } elseif ($owner != '') {
        $sql .= ' AND owner = :owner';
        $params[':owner'] = $owner;
    }
    $sql .= ' ORDER BY id DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = array();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $rows[] = array('name' => $row['name']);
    }
    return $rows;
}

function mikhmon_get_sales_rows($session, $source = '', $owner = '')
{
    $pdo = mikhmon_pdo();
    $sql = 'SELECT * FROM sales WHERE session = :session';
    $params = array(':session' => $session);
    if ($source != '') {
        $sql .= ' AND source = :source';
        $params[':source'] = $source;
    } elseif ($owner != '') {
        $sql .= ' AND owner = :owner';
        $params[':owner'] = $owner;
    }
    $sql .= ' ORDER BY id ASC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function mikhmon_delete_sales($session, $source = '', $owner = '')
{
    $pdo = mikhmon_pdo();
    $sql = 'DELETE FROM sales WHERE session = :session';
    $params = array(':session' => $session);
    if ($source != '') {
        $sql .= ' AND source = :source';
        $params[':source'] = $source;
    } elseif ($owner != '') {
        $sql .= ' AND owner = :owner';
        $params[':owner'] = $owner;
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->rowCount();
}

function mikhmon_get_sessions_from_config()
{
    $config = array();
    include dirname(__DIR__) . '/include/config.php';
    foreach ($data as $key => $value) {
        if ($key == 'mikhmon') {
            continue;
        }
        if (isset($value[1])) {
            $config[$key] = array(
                'iphost' => explode('!', $value[1])[1],
                'userhost' => explode('@|@', $value[2])[1],
                'passwdhost' => explode('#|#', $value[3])[1],
            );
        }
    }
    return $config;
}

function mikhmon_parse_time($scriptName)
{
    $parts = explode('-|-', $scriptName);
    return array(
        'tdate'    => isset($parts[0]) ? $parts[0] : '',
        'ttime'    => isset($parts[1]) ? $parts[1] : '',
        'username' => isset($parts[2]) ? $parts[2] : '',
        'price'    => isset($parts[3]) ? $parts[3] : '',
        'profile'  => isset($parts[7]) ? $parts[7] : '',
        'comment'  => isset($parts[8]) ? $parts[8] : '',
    );
}
