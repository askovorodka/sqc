<?php
//��������������� �����
class Shop extends db {

	
	var $db;
	function Shop($db)
	{
		$this->db = &$db;
	}

	function set_order()
	{
		
	}
	
	function getImageProductByCategory($cat_id)
	{
		$products = $this->getProductsByCategory($cat_id);
		if ($products)
		{
			foreach ($products as $product)
			{
				$image = $this->getProductImage($product['id']);
				if (!empty($image))
				{
					return $image;
				}
			}
		}
	}
	
	function getTopProducts($limit = 1)
	{
		$result = $this->db->get_all("select * from fw_products where status='1' and hit='1'");
		
		if ($result && count($result) > 0)
		{
			foreach ($result as $key=>$val)
			{
				$result[$key]['full_url'] = $this->getFullUrlProduct($val['id'], "catalog");
				$result[$key]['category'] = $this->getCategory($val['parent']);
				$result[$key]['image'] = $this->getProductImage($val['id']);
			}
			return $result;
		}
		else 
		{
			return null;
		}

	}


	function searchCount($where)
	{
		if (count($where) > 0)
		{
			$where = " and " . implode(" and ", $where);
		}
		else 
		{
			$where = "";
		}
		
		$result = $this->db->query("select count(*) as count 
		from fw_products as a
		left join fw_catalogue as b on a.parent = b.id 
		where a.status = '1' and (a.tire_sklad > 0 or a.disk_sklad > 0) " . $where . " order by a.insert_date desc");
		
		return $result;
		
	}


	function search($keyword, $sort_field = "date", $sort_order = "desc")
	{
		if (count($where) > 0)
		{
			$where = " and " . implode(" and ", $where);
		}
		else 
		{
			$where = "";
		}
		
		switch ($sort_field)
		{
			case 'date':
				$sort_field = " order by a.insert_date";
				break;
			case 'name':
				$sort_field = "order by a.name";
				break;
			case 'article':
				$sort_field = "order by a.article";
				break;
			case 'disk_width':
				$sort_field = "order by a.disk_width";
				break;
			case 'tire_width':
				$sort_field = "order by a.tire_width";
				break;
			case 'tire_height':
				$sort_field = "order by a.tire_height";
				break;
			case 'tire_diameter':
				$sort_field = "order by a.tire_diameter";
				break;
			case 'disk_diameter':
				$sort_field = "order by a.disk_diameter";
				break;
			case 'disk_krep':
				$sort_field = "order by a.disk_krep";
				break;
			case 'price':
				$sort_field = "order by a.price";
				break;
			
		}
		
		if ($sort_order == "asc" || $sort_order = "desc")
		{
			$sort_order = $sort_order;
		}
		else 
		{
			$sort_order = "desc";
		}
		
		
		$result = $this->db->get_all("select a.*
		from fw_products as a 
		where a.status = '1' and (name like '{$keyword}%' or article='{$keyword}' or price='{$keyword}' or country='{$keyword}') " . 
		" {$sort_field} {$sort_order} ");
		
		return $result;
		
	}


	/**
	 * ������ ���� �� ��������
	 *
	 * @param unknown_type $id
	 */
	function getFullUrlProduct($id, $module_name = "")
	{
		$product = self::getProductInfo($id);
		if (!$product)
		{
			return false;
		}
		$category = self::getCategory($product['parent']);
		
		$url = $id;
		if ($category)
		{
			$url = $category['url'] . '/' . $url;
			for ($i = $category['param_level']; $i > 1; $i--)
			{
				$category = self::getParent($category, $category['param_level']-1 );
				$url = $category['url'] . '/' . $url;
			}
		}
		
		return ($module_name) ? $module_name . "/" .$url : $url;
		
	}

	function getFullUrlCategory($id, $module_name = "")
	{
		$category = self::getCategory($id);
		$url = "";
		if ($category)
		{
			$url = $category['url'];
			for ($i = $category['param_level']; $i > 1; $i--)
			{
				$category = self::getParent($category, $category['param_level']-1 );
				$url = $category['url'] . '/' . $url;
			}
		}
		return ($module_name) ? $module_name . "/" .$url : $url;
		
	}

	function getUser($id)
	{
		$result = $this->db->get_single("select * from fw_users where id = '{$id}'");
		if ($result)
		{
			return $result;
		}
		else 
		{
			return null;
		}
	}

	function getChildrenCategor($categor, $param_level = null)
	{
		if (isset($param_level))
		{
			$where = " and param_level = '{$param_level}' ";
		}
		else 
		{
			$where = "";
		}
		$result = $this->db->get_all("SELECT * FROM fw_catalogue WHERE param_left BETWEEN '{$categor['param_left']}' 
					and '{$categor['param_right']}' and status = '1' " . $where);
		if ($result)
		{
			return $result;
		}
		else 
		{
			return null;
		}
		
	}


	function getCategories($param_level = 0)
	{
		$result = $this->db->get_all("select * from fw_catalogue where status='1' and param_level >= '{$param_level}' order by param_left");
		
		if ($result)
		{
			return $result;
		}
		else 
		{
			return null;
		}
		
	}
	
	
	function getProductsProperties($categories)
	{
		if (is_array($categories))
		{
			$where = " and parent in (" . implode(",", $categories) . ")";
		}
		else 
		{
			$where = " and parent = '{$categories}' ";
		}
		
		$result = $this->db->get_single("
					SELECT min(disk_diameter) as min_diameter, 
					max(disk_diameter) as max_diameter,
					min(disk_width) as min_width,
					max(disk_width) as max_width,
					avg(disk_krep) as avg_krep,
					avg(disk_pcd) as avg_pcd,
					min(price) as price
					from fw_products where status = '1' and (tire_sklad > 0 or disk_sklad > 0) " . $where);
		if ($result)
		{
			return $result;
		}
		else 
		{
			return null;
		}
		
	}


	function getProductsPropertiesTires($categories)
	{
		if (is_array($categories))
		{
			$where = " and parent in (" . implode(",", $categories) . ")";
		}
		else 
		{
			$where = " and parent = '{$categories}' ";
		}
		
		$result = $this->db->get_single("
					SELECT min(tire_diameter) as min_diameter, 
					max(tire_diameter) as max_diameter,
					min(tire_width) as min_width,
					max(tire_width) as max_width,
					min(price) as price
					from fw_products where status = '1' and tire_sklad > 0 " . $where);
		if ($result)
		{
			return $result;
		}
		else 
		{
			return null;
		}
		
	}
	
	
	function getProductsPropertiesDisk($categories)
	{
		if (is_array($categories))
		{
			$where = " and parent in (" . implode(",", $categories) . ")";
		}
		else 
		{
			$where = " and parent = '{$categories}' ";
		}
		
		$result = $this->db->get_single("
					SELECT min(disk_diameter) as min_diameter, 
					max(disk_diameter) as max_diameter,
					min(disk_width) as min_width,
					max(disk_width) as max_width,
					min(price) as price
					from fw_products where status = '1' and disk_sklad > 0 " . $where);
		if ($result)
		{
			return $result;
		}
		else 
		{
			return null;
		}
		
	}
	
	
	function getParent($categor, $param_level = 1)
	{
		$result = $this->db->get_single("
			SELECT * FROM fw_catalogue 
			WHERE param_left < '{$categor['param_left']}' and param_right > '{$categor['param_right']}' and param_level = '{$param_level}'");
		
		if ($result)
		{
			return $result;
		}
		else 
		{
			return null;
		}
		
	}
	
	
	function getProductInfo($id)
	{
		$result = $this->db->get_single("
			SELECT a.*
			FROM fw_products as a 
			WHERE a.id = '{$id}'");
		
		if ($result)
		{
			return $result;
		}
		else 
		{
			return null;
		}
		
	}
	
	
	function getDiskType($id)
	{
		$result = $this->db->get_single("select * from fw_disk_types where id = '{$id}'");
		if ($result)
		{
			return $result;
		}
		else
		{
			return null;
		}
	}
	
	
	function getCategory($id)
	{
		$result = $this->db->get_single("SELECT * FROM fw_catalogue WHERE id = '{$id}'");
		
		if ($result)
		{
			return $result;
		}
		else 
		{
			return null;
		}
		
	}

	function getCategoryByUrl($url, $id = null)
	{
		$url = trim($url);
		$where = "";
		if (!empty($id))
		{
			$where = " and id <> '{$id}'";
		}
		$result = $this->db->get_single("SELECT * FROM fw_catalogue WHERE url = '{$url}' " . $where . " limit 1");

		if ($result)
		{
			return $result;
		}
		else
		{
			return null;
		}

	}

	function checkCategoryUrl($url, $id = null)
	{
		$count = 0;
		while($this->getCategoryByUrl($url, $id) !== null)
		{
			$url = preg_replace("/\_([0-9]+)$/is", "", $url);
			$url .= "_" . ++$count;
		}
		return $url;
	}


	function getBodyById($id)
	{
		$result = $this->db->get_single("SELECT * FROM fw_body_types WHERE id = '{$id}'");
		
		if ($result)
		{
			return $result;
		}
		else 
		{
			return null;
		}
	}
	
	
	function getProductsByCategory($cat_id)
	{
		
		$result = $this->db->get_all("
				SELECT *,
						(SELECT id FROM fw_products_images i WHERE i.parent=p.id ORDER BY sort_order ASC LIMIT 1) AS image,
						(SELECT ext FROM fw_products_images WHERE parent=p.id ORDER BY insert_date DESC LIMIT 1) AS ext
					FROM fw_products AS p
					WHERE p.parent='".$cat_id."' AND p.status='1' order by p.sort_order");
		
		if ($result)
		{
			return $result;
		}
		else 
		{
			return null;
		}
		
	}
	
	
	
	function getProductImages($product_id)
	{
		
		$result = $this->db->get_all("select * from fw_products_images where parent='{$product_id}' ");
		
		if ($result)
		{
			return $result;
		}
		else
		{
			return null;
		}
		
	}

	
	function getProductImage($product_id)
	{
		
		$result = $this->db->get_single("select id, ext from fw_products_images where parent='{$product_id}' order by sort_order limit 1 ");
		
		if ($result)
		{
			return $result['id'].'.'.$result['ext'];
		}
		else
		{
			return null;
		}
		
	}
	
	
	function get_product_properties($product_id)
	{
		
		$result = $this->db->get_all("select * from fw_products_properties as a left join fw_catalogue_properties as b on a.property_id=b.id where a.product_id='{$product_id}' ");
		
		if ($result)
		{
			return $result;
		}
		else
		{
			return null;
		}
		
	}
	
	
	function get_catalog_properties($cat_id)
	{
		
		$result = $this->db->get_all("select * from fw_catalogue_relations left join fw_catalogue_properties on fw_catalogue_relations.property_id=fw_catalogue_properties.id where fw_catalogue_relations.cat_id='{$cat_id}' order by fw_catalogue_properties.id");
		
		if ($result)
		{
			return $result;
		}
		else
		{
			return null;
		}
		
	}
	
	
}
?>