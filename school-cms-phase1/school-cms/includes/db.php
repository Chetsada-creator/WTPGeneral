<?php
function db(): PDO {
  static $pdo = null;
  if ($pdo === null) {
    $c = require __DIR__ . '/../config.php';
    $dsn = "mysql:host={$c['db_host']};dbname={$c['db_name']};charset=utf8mb4";
    $pdo = new PDO($dsn, $c['db_user'], $c['db_pass'], [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
  }
  return $pdo;
}

function q(string $sql, array $params = []): PDOStatement {
  $st = db()->prepare($sql);
  $st->execute($params);
  return $st;
}

function rows(string $sql, array $params = []): array { return q($sql, $params)->fetchAll(); }
function row(string $sql, array $params = []) { return q($sql, $params)->fetch(); }
function val(string $sql, array $params = []) { return q($sql, $params)->fetchColumn(); }

function insert(string $table, array $data): int {
  $cols = array_keys($data);
  $sql = "INSERT INTO `$table` (`" . implode('`,`', $cols) . "`) VALUES (" .
         implode(',', array_fill(0, count($cols), '?')) . ")";
  q($sql, array_values($data));
  return (int) db()->lastInsertId();
}

function update(string $table, array $data, string $where, array $wparams = []): void {
  $set = implode(',', array_map(fn($c) => "`$c`=?", array_keys($data)));
  q("UPDATE `$table` SET $set WHERE $where", array_merge(array_values($data), $wparams));
}

function delete_row(string $table, int $id): void {
  q("DELETE FROM `$table` WHERE id=?", [$id]);
}
