<?php
class ModelCatalogFRCategory extends Model {
    public function getCategory($category_id) {
        $query = $this->db->query("SELECT fc.fr_category_id, name AS fr_category, code FROM fr_category fc INNER JOIN " . DB_PREFIX . "category_to_fr_category cfr ON fc.fr_category_id = cfr.fr_category_id WHERE category_id = '" . (int)$category_id . "'");

        return $query->row;
    }

    public function getCategories() {
        $sql = "SELECT fr_category_id, name FROM fr_category ORDER BY (code != 999) DESC, name";

        $query = $this->db->query($sql);

        return $query->rows;
    }
}
