<?php
class ModelSaleOrderMeta extends Model {
	public function addMeta($order_id, $meta_key, $meta_value) {
	    if (is_array($meta_value)) {
	        $meta_value = json_encode($meta_value);
        }

		$this->db->query("INSERT INTO `" . DB_PREFIX . "order_meta` SET order_id = '" . $order_id . "', meta_key = '" . $this->db->escape($meta_key) . "', meta_value = '" . $this->db->escape($meta_value) . "'");

		$order_meta_id = $this->db->getLastId();

		return $order_meta_id;
	}

	public function getMeta($order_id, $meta_key) {
		$order_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order_meta` WHERE order_id = '" . (int)$order_id . "' AND meta_key = '" . $this->db->escape($meta_key) . "'");

		if ($order_query->num_rows) {
		    $meta_value = $order_query->row['meta_value'];

		    if (json_decode($meta_value, true)) {
		        $meta_value = json_decode($meta_value, true);
            }

			return $meta_value;
		} else {
			return false;
		}
	}
}