<?php
class ModelCatalogFRCategory extends Model {
    public function getCategory($category_id) {
        $query = $this->db->query("SELECT fc.fr_category_id, name AS fr_category, code FROM fr_category fc INNER JOIN " . DB_PREFIX . "category_to_fr_category cfr ON fc.fr_category_id = cfr.fr_category_id WHERE category_id = '" . (int)$category_id . "'");

        return $query->row;
    }

    public function getCategories($data = array()) {
		$sql = "SELECT fr_category_id, name FROM fr_category";

		if (!empty($data['filter_name'])) {
			$sql .= " WHERE name LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
		}

		$sort_data = array(
			'code',
			'name'
		);

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY name";
		}

		if (isset($data['order']) && ($data['order'] == 'DESC')) {
			$sql .= " DESC";
		} else {
			$sql .= " ASC";
		}

		if (isset($data['start']) || isset($data['limit'])) {
			if ($data['start'] < 0) {
				$data['start'] = 0;
			}

			if ($data['limit'] < 1) {
				$data['limit'] = 20;
			}

			$sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
		}

		$query = $this->db->query($sql);

		return $query->rows;
	}
}
