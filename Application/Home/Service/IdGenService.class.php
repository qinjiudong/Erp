<?php
namespace Home\Service;

/**
 * 生成UUIDService
 *
 * @author 李静波
 */
class IdGenService {
	public function newId() {
		$data = M()->query("select UUID() as uuid");
		if (!$data) {
			return strtoupper(uniqid());
		} else {
			return strtoupper($data[0]["uuid"]);
		}
	}
	
	public function autoId($table) {
		$db = M();
		$max_id = $db->query("SELECT max(id) as id from $table");
		return $max_id[0]['id'] + 1;
	}
}
