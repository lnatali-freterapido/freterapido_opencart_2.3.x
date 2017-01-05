<?php

function hasColumn($db, $table, $column) {
    $query = $db->query("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME LIKE '" . DB_PREFIX . "$table' AND COLUMN_NAME = '$column'");
    return $query->row ? true : false;
}

function addColumn($db, $table, $column, $query) {
    $column = hasColumn($db, $table, $column);

    if (!$column) {
        $db->query($query);
    }
}

$db = $this->db;

// Adiciona a coluna 'manufacturing_deadline' na tabela *_product
addColumn(
    $db,
    'product',
    'manufacturing_deadline',
    "ALTER TABLE " . DB_PREFIX . "product ADD manufacturing_deadline INT(11) DEFAULT '0' NOT NULL AFTER stock_status_id"
);
