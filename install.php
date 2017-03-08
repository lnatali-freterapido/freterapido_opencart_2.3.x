<?php

$db = $this->db;

try {
    // Adiciona a coluna 'manufacturing_deadline' na tabela *_product
    $db->query("ALTER TABLE " . DB_PREFIX . "product ADD manufacturing_deadline INT(11) DEFAULT '0' NOT NULL AFTER stock_status_id");
} catch (Exception $exception) {}
