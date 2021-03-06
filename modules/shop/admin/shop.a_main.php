<?php

//error_reporting(E_ALL);
//ini_set('display_errors','On');

require_once '../lib/class.tree.php';
require_once '../lib/class.image.php';
require_once '../lib/class.import.php';
require_once('../lib/class.string.php');
require_once('../modules/shop/front/class.shop.php');
require_once 'order_status.php';

$smarty->assign("currency",DEFAULT_CURRENCY);

/* DB TREE VARIABLES */
$table='fw_catalogue';
$id_name='id';
$field_names = array(
   'left' => 'param_left',
   'right'=> 'param_right',
   'level'=> 'param_level',
);

$tree=new CDBTree($db, $table, $id_name, $field_names);
$string = new String($db);
$shop = new Shop($db);

//$id = $tree->clear();
//$tree->insert($id, array("name" => "����", "url" => "tires"));
//$tree->insert($id, array("name" => "�����", "url" => "disk"));
//echo date("Y-m-d H:i:s");

$cat_list=$db->get_all("SELECT *,
	(SELECT COUNT(*) FROM fw_products WHERE parent=p.id) AS products 
	FROM fw_catalogue p ORDER BY param_left");

/*$cat_list=$db->get_all("SELECT *
	FROM fw_catalogue p ORDER BY param_left");*/

$cat_list=String::unformat_array($cat_list);

$type_list=$db->get_all("SELECT * FROM fw_products_types ORDER BY name");
$type_list=String::unformat_array($type_list);

$cur_site=$db->get_single("SELECT kurs,znak FROM fw_currency WHERE id=" . CURRENCY_SITE);
$cur_site=String::unformat_array($cur_site);
$smarty->assign("currency_site",$cur_site);

$cur_admin=$db->get_single("SELECT kurs,znak FROM fw_currency WHERE id=" . CURRENCY_ADMIN);
$cur_admin=String::unformat_array($cur_admin);

$navigation[]=array("url" => BASE_URL . "/admin/?mod=shop","title" => '�������');
//UPDATE `fw_products` SET sort_order=id-2 WHERE 1
if (isset($_GET['action']) && $_GET['action']!='') $action=$_GET['action'];
else $action='';

/*------------------------- ��������� ��������� �������� ---------------------*/


if (!empty($_POST['add_color']))
{
	$color = $_POST['color'];
	$name = $_POST['color_name'];
	$db->query("insert into colors (`color`,`name`) values ( '{$color}', '{$name}' )");

	$location = $_SERVER['HTTP_REFERER'];
	header("Location: " . $location);
	die;
}

if (!empty($_POST['property_size']) && !empty($_POST['product_id']))
{
	$product_id = (int) $_POST['product_id'];
	if (!$shop->productPropertyExist($product_id, 'size', $_POST['property_size']))
	{
		$id = $shop->addProductProperty($product_id, 'size', $_POST['property_size']);
	}

	if (!empty($_POST['property_size_brand']) && !empty($id)){
		$shop->addProductProperty($product_id, 'size_brand', $_POST['property_size_brand'], $id);
	}

	$location = "/admin/index.php?mod=shop&action=edit_product&id={$product_id}&property=show";
	header("Location: " . $location);
	die;

}


if (!empty($_POST['property_color']) && !empty($_POST['product_id']) && !empty($_POST['parent_id']))
{
	$product_id = (int) $_POST['product_id'];
	$parent_id = (int) $_POST['parent_id'];

	if (!$shop->productPropertyExist($product_id, 'color', $_POST['property_color'], $parent_id))
	{
		$shop->addProductProperty($product_id, 'color', $_POST['property_color'], $parent_id);
	}

	$location = "/admin/index.php?mod=shop&action=edit_product&id={$product_id}&property=show";
	header("Location: " . $location);
	die;

}

if (isset($_POST['edit_code']))
{
	$code = (string)$_POST['code'];
	$state = (int)$_POST['state'];
	$percent = (int) $_POST['percent'];
	$code=strtoupper($code);

	$query = "INSERT into
	  	promo_codes (`code`, `state`, `percent`)
	  	values('{$code}', '{$state}', '{$percent}')
		on duplicate key
		update state='{$state}', percent='{$percent}'";
	$db->query($query);

	$location = "/admin/index.php?mod=shop&action=promo_codes";
	header("Location: " . $location);
	die;

}


if (isset($_POST['action']) && $_POST['action']=="resort_order") {
	if (isset($_POST['product']) && isset($_POST['product_prev']) && isset($_POST['parent_cat'])) {
		foreach ($_POST['product'] as $key=>$val){
   			$db->query("UPDATE fw_products SET sort_order='$val' WHERE id='$key'");
		}
	}

		if (isset($_POST['del_product']) && is_array($_POST['del_product'])){
			foreach ($_POST['del_product'] as $key=>$val)
				$db->query("DELETE FROM fw_products WHERE id='$key'");
				$db->query("DELETE FROM fw_products_images WHERE parent='$key'");
				$db->query("DELETE FROM fw_products_comments WHERE product_id='$key'");
				$db->query("DELETE FROM fw_products_properties WHERE product_id='$key'");

		}
		$location=$_SERVER['HTTP_REFERER'];
		header ("Location: $location");
		die();

}

if (isset($_POST['submit_edit_user_order'])  &&  intval($_POST['order_id'])>0){
	$comment = String::secure_format($_POST['comment_user']);
	$db->query("UPDATE fw_orders SET comments='$comment' WHERE id='".intval($_POST['order_id'])."'");
	$location=$_SERVER['HTTP_REFERER'];
	header ("Location: $location");
	die();
}

if (isset($_POST['submit_sort_products']) && isset($_POST['sortArray']) ){

	$sortList = explode("|",$_POST['sortArray']);
	foreach ($sortList as $key=>$val){
		$sort= $key + 1;
		$db->query("UPDATE fw_products SET sort_order='$sort' WHERE id='$val' ");
	}
		$smarty->assign("message","��������� �������������");
}


if ($action=="change_cur_status" && isset($_GET['id'])) {
  $id=intval($_GET['id']);
  $db->query("UPDATE fw_currency SET status=IF(status=0,1,0) WHERE id='".$id."'");

  $location=$_SERVER['HTTP_REFERER'];
  header ("Location: $location");
  die();
}

if (isset($_POST['edit_order_status']) && intval($_POST['edit_order_status'])==1 && isset($_POST['id'])){
	
	$db->query("UPDATE fw_orders SET status='".$_POST['edit_status']."' WHERE id='". $_POST['id'] ."'");
	//echo  "UPDATE fw_orders SET status='".$_POST['edit_status']."' WHERE id='". $_POST['id'] ."'";
	//exit();
  	$location=$_SERVER['HTTP_REFERER'];
  	header ("Location: $location");
  	die();
}

if (isset($_POST['submit_add_cur'])) {

  $name=String::secure_format($_POST['edit_cur_name']);
  $kurs=String::secure_format($_POST['edit_cur_kurs']);
  $znak=$_POST['edit_cur_znak'];
  $status=intval($_POST['edit_cur_status']);

  $db->query("INSERT INTO fw_currency(name,kurs,status,znak) VALUES('$name',$kurs,$status,'$znak')");
  header("Location: ?mod=shop&action=currency");
}

if (isset($_POST['submit_edit_cur'])) {

  $name=String::secure_format($_POST['edit_cur_name']);
  $kurs=$_POST['edit_cur_kurs'];
  $znak=$_POST['edit_cur_znak'];
  $status=intval($_POST['edit_cur_status']);
  $id=intval($_POST['id']);
  $db->query("UPDATE fw_currency SET name='$name',status='$status',kurs=$kurs,znak='$znak' WHERE id='$id'");

  $location=$_SERVER['HTTP_REFERER'];
  header("Location: $location");
  die();

}


if ($action=='delete_cur' && isset($_GET['id'])) {
  $id=intval($_GET['id']);

  $db->query("DELETE FROM fw_currency WHERE id='$id' LIMIT 1");
  header ("Location: ?mod=shop&action=currency");
  die();
}



if ($action=='delete_type' && isset($_GET['id'])) {
  $id=intval($_GET['id']);

  $db->query("DELETE FROM fw_products_types WHERE id='$id' LIMIT 1");

  header ("Location: ?mod=shop&action=types");
  die();
}

if (isset($_POST['submit_edit_type'])) {

  $name=String::secure_format($_POST['edit_type_name']);
  $text=String::secure_format($_POST['edit_type_text']);
  $status=intval($_POST['edit_type_status']);
  $id=intval($_POST['id']);
  $type_id = intval($_POST['type_id']);


  $db->query("UPDATE fw_products_types SET name='$name',text='$text',status='$status' WHERE id='$id'");
  $db->query("DELETE FROM fw_cats_types_relations WHERE type_id=$id");

  if (isset($_POST['cat'])){
  	if (is_array($_POST['cat'])){
  		foreach ($_POST['cat'] as $key=>$val){
  			$query="INSERT INTO fw_cats_types_relations (cat_id,type_id) VALUES ($key,$id)";
  			$db->query($query);
  		}
  	}
  }

  $location=$_SERVER['HTTP_REFERER'];
  header("Location: $location");
  die();

}

if (isset($_POST['submit_add_type'])) {

  $name=String::secure_format($_POST['edit_type_name']);
  $status=intval($_POST['edit_type_status']);

  $db->query("INSERT INTO fw_products_types(name,status) VALUES('$name','$status')");
  header("Location: ?mod=shop&action=types");
}


if (isset($_POST['action']) && $_POST['action']=="move_to") {

	$db->query("UPDATE fw_products SET parent='".intval($_POST['cat'])."' WHERE id IN (".implode(',',array_keys($_POST['edit_move'])).")");

	$location=$_SERVER['HTTP_REFERER'];
	header ("Location: $location");
	die();
}

if ($action=="change_property_status" && isset($_GET['id'])) {
	$id=intval($_GET['id']);
	$db->query("UPDATE properties SET status=IF(status='0','1','0') WHERE id='".$id."'");

	$location=$_SERVER['HTTP_REFERER'];
	header ("Location: $location");
	die();
}

if ($action=="change_status" && isset($_GET['id'])) {
	$id=intval($_GET['id']);
	$db->query("UPDATE fw_catalogue SET status=IF(status='0','1','0') WHERE id='".$id."'");

	$location=$_SERVER['HTTP_REFERER'];
	header ("Location: $location");
	die();
}

if ($action == "delete_color" && !empty($_GET['color']))
{
	$color = intval($_GET['color']);
	$query = "delete from colors where id='{$color}'";
	$db->query($query);
	$location=$_SERVER['HTTP_REFERER'];
	header ("Location: $location");
	die();
}

if ($action=="change_product_status" && isset($_GET['id'])) {
	$id=intval($_GET['id']);
	$db->query("UPDATE fw_products SET status=IF(status='0','1','0') WHERE id='".$id."'");

	$location=$_SERVER['HTTP_REFERER'];
	header ("Location: $location");
	die();
}

if ($action=="change_property_status" && isset($_GET['id'])) {
	$id=intval($_GET['id']);
	$db->query("UPDATE fw_catalogue_properties SET status=IF(status='0','1','0') WHERE id='".$id."'");

	$location=$_SERVER['HTTP_REFERER'];
	header ("Location: $location");
	die();
}

if (isset($_POST['submit_add_property'])) {

	$name=String::secure_format($_POST['edit_property_name']);
	$status=intval($_POST['edit_property_status']);
	$type=intval($_POST['edit_property_type']);
	$entity=($_POST['edit_property_entity']);

	$tmp=explode("\n",$_POST['edit_property_elements']);
	$elements = array();
	foreach ($tmp as $k) {
		$k = trim($k);
		if ($k != '') $elements[] = $k;
	}

	$elements = implode("\n", $elements);

	$db->query("INSERT INTO fw_catalogue_properties(name,status,elements,type, entity) VALUES('$name','$status','$elements','$type', '$entity')");
	header("Location: ?mod=shop&action=properties");
}

if (isset($_POST['submit_edit_property'])) {

	$name=String::secure_format($_POST['edit_property_name']);
	$status=intval($_POST['edit_property_status']);
	$type=intval($_POST['edit_property_type']);
	$entity=($_POST['edit_property_entity']);
	$id=intval($_POST['id']);

	$tmp=explode("\n",$_POST['edit_property_elements']);
	$elements = array();
	foreach ($tmp as $k) {
		$k = trim($k);
		if ($k != '') $elements[] = $k;
	}

	$elements = implode("\n", $elements);

	$db->query("UPDATE fw_catalogue_properties SET name='$name',entity='$entity',type='$type',elements='$elements',status='$status' WHERE id='$id'",1);

	$location=$_SERVER['HTTP_REFERER'];
	header("Location: $location");
	die();

}

if ($action=='delete_property' && isset($_GET['id'])) {
	$id=intval($_GET['id']);

	$db->query("DELETE FROM fw_catalogue_properties WHERE id='$id' LIMIT 1");

	header ("Location: ?mod=shop&action=properties");
	die();
}


if (isset($_POST['submit_add_cat'])) {

	Common::check_priv("$priv");

	$check=true;

	$parent=$_POST['edit_cat_parent'];

	$name=String::secure_format($_POST['edit_cat_name']);

	$url=String::secure_format($_POST['edit_cat_url']);

	if (!trim($url)){
		$url = $string->translit(strtolower($name));
	}
	$url = $shop->checkCategoryUrl($url);
	$title=String::secure_format($_POST['edit_cat_title']);
	$status=$_POST['edit_cat_status'];
	$keywords=String::secure_format($_POST['edit_cat_keywords']);
	$description=String::secure_format($_POST['edit_cat_description']);

	if ($name=='') $name="����� ���������� ���������";

	$check_if_exists=$db->get_all("SELECT id FROM fw_catalogue WHERE url='$url' AND param_left>(SELECT param_left FROM fw_catalogue WHERE id='$parent') AND param_right<(SELECT param_right FROM fw_catalogue WHERE id='$parent') AND param_level=(SELECT param_level FROM fw_catalogue WHERE id='$parent')");
	if (count($check_if_exists)>0) {
		$smarty->assign("error_message","��������� � ����� ����� ��� ����������!");
		$check=false;
	}

	if (!preg_match("/^([a-z0-9_-]+)$/",$url)) {
		$smarty->assign("error_message","� URL ��������� ������ ������� ��������, ����� � ���� �������������!");
		$check=false;
	}

	if ($_FILES['edit_cat_image']['name']!='') {

		$file_name=$_FILES['edit_cat_image']['name'];
		$tmp=$_FILES['edit_cat_image']['tmp_name'];

		$trusted_formats=array('jpg','jpeg','gif','png');

		$check_file_name=explode(".",$file_name);
		$ext=strtolower($check_file_name[count($check_file_name)-1]);
		if (!in_array($ext,$trusted_formats)) {
			$smarty->assign("error_message","��������� �������� �������� jpg, jpeg, gif � png");
			$check=false;
		}

		if (filesize($tmp)>2000000) {
			$smarty->assign("error_message","������ ���������� �� ������ ��������� 2Mb");
			$check=false;
		}
	}

	if ($check) {
		$tree->insert($parent,array(
			"name"=>$name,
			"title"=>$title,
			"url"=>$url,
			"status"=>$status,
			"meta_keywords"=>$keywords,
			"meta_description"=>$description));
			
		if (@$file_name!='') {
			$id=mysql_insert_id();
			$image = md5($id . rand(0,1000)) .'.'.$ext;
			if (move_uploaded_file($tmp, BASE_PATH.'/uploaded_files/category_images/'.$image)) {
				chmod(BASE_PATH.'/uploaded_files/category_images/'.$image, 0777);
				$details = Image::image_details(BASE_PATH.'/uploaded_files/category_images/'.$image);
				
				//���������
				Image::resize(BASE_PATH."/uploaded_files/category_images/$image", BASE_PATH."/uploaded_files/category_images/small-$image", PRODUCT_PREVIEW_WIDTH,PRODUCT_PREVIEW_HEIGHT, false, "#FFFFFF");
				Image::resize(BASE_PATH."/uploaded_files/category_images/$image", BASE_PATH."/uploaded_files/category_images/medium-$image", PRODUCT_MEDIUM_WIDTH,PRODUCT_MEDIUM_HEIGHT, false, "#FFFFFF");
				Image::resize(BASE_PATH."/uploaded_files/category_images/$image", BASE_PATH."/uploaded_files/category_images/big-$image", PRODUCT_BIG_WIDTH,PRODUCT_BIG_HEIGHT, false, "#FFFFFF");
				//unlink(BASE_PATH.'/uploaded_files/shop_images/'.'-'.$id.'.'.$ext);
				$result=$db->query("UPDATE fw_catalogue SET image='". $image ."' WHERE id='". $id ."'");
			}
		}

		header("Location: index.php?mod=shop&action=catalogue");
	}
}

if (isset($_POST['submit_edit_cat'])) {

	Common::check_priv("$priv");

	$check=true;

	$id=$_POST['id'];
	$old_url=$_POST['old_url'];
	$old_parent=$_POST['old_parent'];

	$name=String::secure_format($_POST['edit_cat_name']);
	$parent=$_POST['edit_cat_parent'];
	$url=String::secure_format($_POST['edit_cat_url']);
	if (!trim($url)){
		$url=$string->translit(strtolower($name));
	}
	$url = $shop->checkCategoryUrl($url, $id);

	$text=String::secure_format($_POST['edit_cat_text']);
	$title=String::secure_format($_POST['edit_cat_title']);
	$status=$_POST['edit_cat_status'];
	$keywords=String::secure_format($_POST['edit_cat_keywords']);
	$description=String::secure_format($_POST['edit_cat_description']);

	if (!empty($_POST['show_in_menu']) && $_POST['show_in_menu'] == 1)
		$show_in_menu = 1;
	else 
		$show_in_menu = 0;
		
	if (isset($_POST['edit_cat_properties']))
		$properties = $_POST['edit_cat_properties'];
	else
		$properties = "";

	$db->query("DELETE FROM fw_catalogue_relations WHERE cat_id='$id'");
	if (!empty($properties))
	{
		foreach($properties as $k) {
			$db->query("INSERT INTO fw_catalogue_relations (cat_id,property_id,sort_order) VALUES('$id', '$k','".intval($_POST['edit_cat_properties_sort_order'][$k])."')");
		}
	}

	if ($name=='') $name="����� ���������� ���������";
	
	

	if ($url!=$old_url or $parent!=$old_parent) {
		$check_if_exists=$db->get_all("SELECT id FROM fw_catalogue WHERE url='$url' AND param_left>(SELECT param_left FROM fw_catalogue WHERE id='$parent') AND param_right<(SELECT param_right FROM fw_catalogue WHERE id='$parent') AND param_level=(SELECT param_level FROM fw_catalogue WHERE id='$parent')");
		if (count($check_if_exists)>0) {
			$smarty->assign("error_message","���� � ����� ����� ��� ����������!");
			$check=false;
		}
	}

	if (!preg_match("/^([a-z0-9_-]+)$/",$url)) {
		$smarty->assign("error_message","� URL ��������� ������ ������� ��������, ����� � ���� �������������!");
		$check=false;
	}
	
	$category_image = $db->get_single("select image from fw_catalogue where id='{$id}'");

	if ($_FILES['edit_cat_image']['name']!='') {

		$file_name=$_FILES['edit_cat_image']['name'];
		$tmp=$_FILES['edit_cat_image']['tmp_name'];

		$trusted_formats=array('jpg','jpeg','gif','png');

		$check_file_name=explode(".",$file_name);
		$ext=strtolower($check_file_name[count($check_file_name)-1]);
		if (!in_array($ext,$trusted_formats)) {
			$smarty->assign("error_message","��������� �������� �������� jpg, jpeg, gif � png");
			$check=false;
		}

		if (filesize($tmp)>2000000) {
			$smarty->assign("error_message","������ ���������� �� ������ ��������� 2Mb");
			$check=false;
		}
	}

	if ($check || $id=="1") {

		if (@$file_name!='') {
			$image= md5($id . rand(0, 10000)).'.'.$ext;
			if (move_uploaded_file($tmp, BASE_PATH.'/uploaded_files/category_images/'.$image)) {
				chmod(BASE_PATH.'/uploaded_files/category_images/'.$image, 0777);
				$details = Image::image_details(BASE_PATH.'/uploaded_files/category_images/'.$image);
				
				Image::resize(BASE_PATH."/uploaded_files/category_images/$image", BASE_PATH."/uploaded_files/category_images/small-$image", PRODUCT_PREVIEW_WIDTH,PRODUCT_PREVIEW_HEIGHT, false, "#FFFFFF");
				Image::resize(BASE_PATH."/uploaded_files/category_images/$image", BASE_PATH."/uploaded_files/category_images/medium-$image", PRODUCT_MEDIUM_WIDTH,PRODUCT_MEDIUM_HEIGHT, false, "#FFFFFF");
				Image::resize(BASE_PATH."/uploaded_files/category_images/$image", BASE_PATH."/uploaded_files/category_images/big-$image", PRODUCT_BIG_WIDTH,PRODUCT_BIG_HEIGHT, false, "#FFFFFF");
				
				if (isset($category_image['image']))
				{
					@system("rm " . BASE_PATH . "/uploaded_files/category_images/*".$category_image['image']);
				}
				
			}
			
		}
		else $image=$_POST['old_image'];

		if (isset($_POST['delete_image'])) {
			$image='';
			unlink(BASE_PATH.'/uploaded_files/category_images/'.$_POST['old_image']);
		}
		$db->query("UPDATE fw_catalogue SET name='$name',image='$image',title='$title',text='$text',url='$url',status='$status',meta_keywords='$keywords',meta_description='$description' WHERE id='$id'");

		if ($parent!=$old_parent) {
			$a=array(array('from' => $id,'to' => $parent));
			$move=$tree->move($a,true);

			if($move===false) $move=-2;
		}
		$location=$_SERVER['HTTP_REFERER'];
		header("Location: $location");
		die();
	}

}

if (isset($_POST['submit_add_product'])) {

	Common::check_priv("$priv");

	$article=$_POST['edit_article'];
	$parent=$_POST['edit_parent'];
	$name=String::secure_format($_POST['edit_name']);
	$title=String::secure_format($_POST['edit_title']);
	//$site_url=$_POST['edit_site_url'];
	//$small_description=String::secure_format($_POST['edit_small_description']);
	//$description=String::secure_format($_POST['edit_description']);
	$price=String::secure_format($_POST['edit_price']);
	$price_sale=!empty($_POST['edit_price_sale']) ? ($_POST['edit_price_sale']) : 0.00;
	//$price1=String::secure_format($_POST['edit_price1']);
	//$price2=String::secure_format($_POST['edit_price2']);
	//$guarantie=String::secure_format($_POST['edit_guarantie']);
	$article=String::secure_format($_POST['edit_article']);
	$country=String::secure_format($_POST['edit_country']);
	$type_name=String::secure_format($_POST['edit_type_name']);
	
	$description=String::secure_format($_POST['edit_description']);
	
	
	//$sort_order=$db->get_single("SELECT MAX(sort_order) as max FROM fw_products WHERE parent='$parent'");
	//$sort_order=$sort_order['max']+1;
	$sort_order=0;
  	//$type=($_POST['edit_type']!='')?intval($_POST['edit_type']):"NULL";


	if ($name=='') $name='����� �������';
	if ($price=='') $price='0.00';

	$db->query("INSERT INTO fw_products 
		(
		
		article,parent,name,
		title,price, price_sale,insert_date,
		country,type_name,description)
		
		VALUES(
			'$article','$parent','$name','$title','$price', '{$price_sale}',
			'".time()."','$country','$type_name','$description'
		)");
	
	header("Location: ?mod=shop&action=edit_product&id=".mysql_insert_id());
	
}

if (isset($_POST['submit_edit_product'])) {

	Common::check_priv("$priv");

	$article=$_POST['edit_article'];
	$parent=$_POST['edit_parent'];
	$name=String::secure_format($_POST['edit_name']);
	$title=String::secure_format($_POST['edit_title']);
	$meta_keywords=String::secure_format($_POST['edit_meta_keywords']);
	$meta_description=String::secure_format($_POST['edit_meta_description']);
	
	$description=String::secure_format($_POST['edit_description']);
	$price=String::secure_format($_POST['edit_price']);
	$price_sale=!empty($_POST['edit_price_sale']) ? ($_POST['edit_price_sale']) : 0.00;
	
	$status=$_POST['edit_status'];
	$sale=isset($_POST['edit_sale'])?"1":"0";
	$country=String::secure_format($_POST['edit_country']);
	$type_name=String::secure_format($_POST['edit_type_name']);
	

	$id=$_POST['id'];

	//$db->query("DELETE FROM fw_products_properties WHERE product_id='$id' LIMIT ".count($_POST['edit_properties']));
	$db->query("DELETE FROM fw_products_properties WHERE product_id='$id'");

	foreach($_POST['edit_properties'] as $key => $val)
	{

		$key_array = explode("|",$key);
		$property_id = $key_array[0];
		$value = $key_array[1];
		$db->query("REPLACE INTO fw_products_properties SET product_id='$id', property_id='$property_id', value='$value'");
		//echo "REPLACE INTO fw_products_properties SET product_id='$id', property_id='$property_id', value='$value'";
		//$v=String::secure_format($v);
		//if ($v!="") $db->query("INSERT INTO fw_products_properties SET product_id='$id', property_id='$k', value='$v'");
	}

	
	$db->query("UPDATE 
		fw_products SET 
			article='$article',
			parent='$parent',
			name='$name',
			title='$title',
			meta_description='$meta_description',
			meta_keywords='$meta_keywords',
			price='$price',
			price_sale='$price_sale',
			type_name='$type_name',
			country='$country',
			status='$status',
			description='$description',
			sale='$sale'
		WHERE id='$id'");
	

}

if (isset($_POST['submit_add_photo'])) {

	Common::check_priv("$priv");

	$check=true;

	$title=String::secure_format($_POST['add_photo_title']);
	$file_name=$_FILES['add_new_photo']['name'];
	$tmp=$_FILES['add_new_photo']['tmp_name'];

	$trusted_formats=array('jpg','jpeg','gif','png');

	$check_file_name=explode(".",$file_name);
	$ext=strtolower($check_file_name[count($check_file_name)-1]);
	if (!in_array($ext,$trusted_formats)) {
		$smarty->assign("error","��������� �������� �������� jpg, jpeg, gif � png");
		$check=false;
	}

	if (filesize($tmp)>2000000) {
		$smarty->assign("error","������ ���������� �� ������ ��������� 2Mb");
		$check=false;
	}

	if ($check) {
		$order=$db->get_single("SELECT MAX(sort_order)+1 AS s_order FROM fw_products_images WHERE parent='".$_POST['parent']."'");
		if ($order['s_order']=='') $order=1;
		else $order=$order['s_order'];
		$result=$db->query("INSERT INTO fw_products_images(parent,title,ext,sort_order) VALUES('".$_POST['parent']."','$title','$ext','".$order."')");
		$id=mysql_insert_id();
		if (move_uploaded_file($tmp, BASE_PATH."/uploaded_files/shop_images/$id.$ext")) {
			chmod(BASE_PATH."/uploaded_files/shop_images/$id.$ext",0777);
			Image::resize(BASE_PATH."/uploaded_files/shop_images/$id.$ext", BASE_PATH."/uploaded_files/shop_images/100x100-$id.$ext", 100,100, true);
			Image::resize(BASE_PATH."/uploaded_files/shop_images/$id.$ext", BASE_PATH."/uploaded_files/shop_images/small-$id.$ext", PRODUCT_PREVIEW_WIDTH,PRODUCT_PREVIEW_HEIGHT, true);
			Image::resize(BASE_PATH."/uploaded_files/shop_images/$id.$ext", BASE_PATH."/uploaded_files/shop_images/medium-$id.$ext", PRODUCT_MEDIUM_WIDTH,PRODUCT_MEDIUM_HEIGHT, true);
			Image::resize(BASE_PATH."/uploaded_files/shop_images/$id.$ext", BASE_PATH."/uploaded_files/shop_images/85x85-$id.$ext", 85,85, true);
			Image::resize(BASE_PATH."/uploaded_files/shop_images/$id.$ext", BASE_PATH."/uploaded_files/shop_images/super-$id.$ext", 800,600, false, "#FFFFFF");
			Image::resize(BASE_PATH."/uploaded_files/shop_images/$id.$ext", BASE_PATH."/uploaded_files/shop_images/50x50-$id.$ext", 50,50, true);
		}
		else {
			$result=$db->query("DELETE FROM fw_products_images WHERE id='".mysql_insert_id()."'");
			$smarty->assign("error","���� �� ��� ��������");
		}
	}

}

if (isset($_POST['submit_save_photos'])) {

	Common::check_priv("$priv");

	$check=true;

	if (isset($_POST['delete_photos'])) {
		$delete_photos=$_POST['delete_photos'];
		for ($i=0;$i<count($delete_photos);$i++) {
			$values.=$delete_photos[$i];
			if ($i!=count($delete_photos)-1) $values.=',';
		}
		$db->query("DELETE FROM fw_products_images WHERE id IN ($values)");
		$location=$_SERVER['HTTP_REFERER'];
		header("Location: $location");
	}


	if (@in_array('1',$_POST['order_changed'])) {

		$order_changed=array_keys($_POST['order_changed'],"1");
		$order=$_POST['edit_order'];

		for ($i=0;$i<count($order_changed);$i++) {
			$new_order=$order[$order_changed[$i]];
			$db->query("UPDATE fw_products_images SET sort_order='$new_order' WHERE id='".$order_changed[$i]."'");
		}
	}

	if (@in_array('1',$_POST['title_changed'])) {

		$title_changed=array_keys($_POST['title_changed'],"1");
		$title=$_POST['edit_title'];

		for ($i=0;$i<count($title_changed);$i++) {
			$new_title=$title[$title_changed[$i]];
			$db->query("UPDATE fw_products_images SET title='$new_title' WHERE id='".$title_changed[$i]."'");
		}
	}
}

if ($action=='cat_move_up' && isset($_GET['id'])){

	Common::check_priv("$priv");

	$id = $_GET['id'];

	$a=array(array('from' => $id,'sibling' => true,'left' => true));

	$tree->move($a,true);

	$location=$_SERVER['HTTP_REFERER'];
	header ("Location: $location");
	die();

}

if ($action=='cat_move_down' && isset($_GET['id'])) {

	Common::check_priv("$priv");

	$id = $_GET['id'];

	$a=array(array('from' => $id,'sibling' => true,'right' => true));

	$tree->move($a,true);

	$location=$_SERVER['HTTP_REFERER'];
	header ("Location: $location");
	die();

}

if ($action=='delete_cat' && isset($_GET['id'])) {

	Common::check_priv("$priv");

	$id = $_GET['id'];

	$cat=$db->get_single("SELECT image FROM fw_catalogue WHERE id='$id'");

	$tree->deleteAll($id);
	unlink(BASE_PATH.'/uploaded_files/shop_images/'.$cat['image']);

	header ("Location: ?mod=shop&action=catalogue");
	die();

}

if ($action=='delete_order' && isset($_GET['id'])) {

	Common::check_priv("$priv");

	$id = $_GET['id'];

	$db->query("DELETE FROM fw_orders WHERE id='$id'");
	$db->query("DELETE FROM fw_orders_products WHERE order_id='$id'");

	header ("Location: ?mod=shop&action=orders");
	die();

}

if (isset($_POST['edit_status'])) {

	Common::check_priv("$priv");

	$id=$_POST['id'];
	$status=$_POST['edit_status'];

	$db->query("UPDATE fw_orders SET status='$status' WHERE id='$id'");

	$location=$_SERVER['HTTP_REFERER'];
	header("Location: $location");
}

if (isset($_GET['action']) && $_GET['action'] == 'code_state') {

	Common::check_priv("$priv");
	$code=$_GET['code'];
	$db->query("UPDATE promo_codes SET state=if(state=1,0,1) WHERE code='$code'");

	$location=$_SERVER['HTTP_REFERER'];
	header("Location: $location");
}

if ($action=='delete_product') {

	Common::check_priv("$priv");

	$id = $_GET['id'];

	$images=$db->get_all("SELECT id,ext FROM fw_products_images WHERE parent='$id'");

	foreach ($images as $k=>$v) {
		@unlink(BASE_PATH.'/uploaded_files/shop_images/'.$v['id'].'.'.$v['ext']);
		@unlink(BASE_PATH.'/uploaded_files/shop_images/resized-'.$v['id'].'.'.$v['ext']);
	}

	$db->get_all("DELETE FROM fw_products_images WHERE parent='$id'");
	$db->query("DELETE FROM fw_products WHERE id='$id'");
	$db->query("DELETE FROM fw_products_properties WHERE product_id='$id'");

	header ("Location: ?mod=shop&action=products_list");
	die();

}

if ($action=='delete_from_order' && isset($_GET['product_id'])  &&  (isset($_GET['order_id']))) {

	Common::check_priv("$priv");

	$product_id=$_GET['product_id'];
	$order_id=$_GET['order_id'];

	/*$order=$db->get_single("SELECT products FROM fw_orders WHERE id='$order_id'");
	$product=$db->get_single("SELECT price FROM fw_products WHERE id='$product_id'");

	$products_list=explode(",",$order['products']);

	for ($i=0;$i<count($products_list);$i++) {
		list($id,$number)=explode("|",$products_list[$i]);
		if ($id==$product_id) {
			$price=$number*$product['price'];
			unset($products_list[$i]);
		}
	}

	$products_list=implode(",",$products_list);

	$db->query("UPDATE fw_orders SET products='$products_list', total_price=total_price-$price WHERE id='$order_id'");
	*/
	
	$db->query("DELETE FROM fw_orders_products WHERE order_id='$order_id' AND product_id='$product_id'");
	
	$location=$_SERVER['HTTP_REFERER'];
	header("Location: ?mod=shop&action=order_details&id=$order_id");

}



if (isset($_POST['submit_number_recount'])) {

	Common::check_priv("$priv");

	$order_id=$_POST['id'];

	$id_list='';
	$products_list='';
	$total_price='';

	if (isset($_POST['edit_number']) && is_array($_POST['edit_number'])){
		foreach ($_POST['edit_number'] as $key=>$val){
   			$db->query("UPDATE fw_orders_products SET product_count='$val' WHERE product_id='$key' AND order_id='$order_id'");
			//echo "UPDATE fw_orders_products SET product_count='$val' WHERE product_id='$key' AND order_id='$order_id'";
		}
	}

	/*foreach ($_POST['edit_number'] as $k=>$v) {
		$id_list.=$k.',';
	}
	$id_list=substr($id_list,0,-1);
	$products=$db->get_all("SELECT id,price FROM fw_products WHERE id IN ($id_list)");
	foreach ($_POST['edit_number'] as $k=>$v) {
		$products_list.=$k.'|'.$v.',';
		for ($p=0;$p<count($products);$p++) {
			if ($products[$p]['id']==$k) {
				$total_price+=$v*$products[$p]['price'];
			}
		}
	}
	$products_list=substr($products_list,0,-1);
	$db->query("UPDATE fw_orders SET products='$products_list',total_price='$total_price' WHERE id='$order_id'");*/

	$location=$_SERVER['HTTP_REFERER'];
	header("Location: $location");
}



if ($action=='delete_previews') {

	set_time_limit(0);

	foreach (glob(BASE_PATH."/uploaded_files/shop_images/resized-*.*") as $filename) {
	   unlink ($filename);
	}

	foreach (glob(BASE_PATH."/uploaded_files/shop_images/*.*") as $filename) {

		$filename=explode("/",$filename);
		$filename=$filename[count($filename)-1];

		if (preg_match("/^[0-9]*\.[a-z]{0,4}$/i",$filename)) Image::image_resize(BASE_PATH."/uploaded_files/shop_images/$filename",BASE_PATH."/uploaded_files/shop_images/resized-$filename",PRODUCT_PREVIEW_WIDTH,PRODUCT_PREVIEW_HEIGHT);
	}

	$location=$_SERVER['HTTP_REFERER'];
	header("Location: $location");
}


/*--------------------------------- ����������� ------------------------------*/

SWITCH (TRUE) {

	CASE ($action == 'colors'):

		$colors = $db->get_all("select * from colors order by id desc");
		$smarty->assign('colors', $colors);
		$navigation[]=array("url" => BASE_URL."/admin/?mod=shop&action=colors","title" => '�����');
		$template="shop.a_colors.html";
		BREAK;

	CASE ($action == 'promo_codes'):

		if (!empty($_GET['code'])){
			$code=$_GET['code'];
			$edit_code = $db->get_single("
				select * from promo_codes where code='{$code}'");
			$smarty->assign('edit_code', $edit_code);
		}

		$codes = $db->get_all("
			select *,
			(select count(*) from promo_codes_users where code=promo_codes.code) count,
			  (select group_concat(promo_codes_users.order_id) from promo_codes_users where code=promo_codes.code) orders
		  	from promo_codes order by date desc");
		$smarty->assign('codes', $codes);
		$navigation[]=array("url" => BASE_URL."/admin/?mod=shop&action=promo_codes","title" => '����� ����');
		$template="shop.a_promo_codes.html";
	BREAK;

	//������ �������
	CASE ($action == 'import_error'):
		
		$navigation[]=array("url" => BASE_URL."/admin/?mod=shop&action=import_error","title" => '������ ������� �������');
		$template='shop.a_import_error.html';
		
	BREAK;
	

	//����������� �������
	CASE ($action == 'import_details'):
		
		$navigation[]=array("url" => BASE_URL."/admin/?mod=shop&action=import_details","title" => '����������� �������');
		$import = new Import($db, $tree, $string);
		$import = $import->getImportById($_GET['id']);
		if ($import)
		{
			$import['details'] = @unserialize($import['import_details']);
		}
		
		$smarty->assign('import', $import);
		$template='shop.a_import_details.html';
		
	BREAK;
	
	//��� �������
	CASE ($action == 'import_log'):
		
		$navigation[]=array("url" => BASE_URL."/admin/?mod=shop&action=import_log","title" => '��� �������');
		$import = new Import($db, $tree, $string);
		$imports = $import->getImports();
		$smarty->assign('imports', $imports);
		$template='shop.a_import_log.htm';
		
	BREAK;
	
	
	CASE ($action == 'import'):
		
		$navigation[]=array("url" => BASE_URL."/admin/?mod=shop&action=import","title" => '������');
		$template='shop.a_import.html';
		
	BREAK;
	
	CASE ($action=='add_cat'):

		$navigation[]=array("url" => BASE_URL."/admin/?mod=shop&action=add_cat","title" => '�������� ���������');

		$smarty->assign("mode","add");
		$smarty->assign("parent",$_GET['parent']);
		$smarty->assign("cat_list",Common::get_nodes_list($cat_list));
		$template='shop.a_edit_cat.html';

	BREAK;
	
	CASE($action=='viewSortProducts'):
		$items = array();
		if (intval($_GET['cat_id'])>0){
			$items = $db->get_all("SELECT id,name,sort_order FROM fw_products WHERE parent='".intval($_GET['cat_id'])."' ORDER BY sort_order");
			$items=String::unformat_array($items);
		}
		$smarty->assign("items",$items);
		$template = 'shop.a_sortProducts.html';
		$template_mode='single';
	BREAK;

	CASE ($action=='edit_cat' && isset($_GET['id'])):

		$id=$_GET['id'];

		$navigation[]=array("url" => BASE_URL."/admin/?mod=shop&action=catalogue","title" => '���������');
		$navigation[]=array("url" => BASE_URL."/admin/?mod=shop&action=edit_cat","title" => '������������� ���������');

		$parent=$tree->getParent($id);

		$cat=$db->get_single("SELECT *, (SELECT GROUP_CONCAT(property_id SEPARATOR ',') FROM fw_catalogue_relations WHERE cat_id='$id') as properties FROM fw_catalogue WHERE id='$id' GROUP BY id");
		$cat['properties'] = explode(",",$cat['properties']);
		$cat=String::unformat_array($cat);

		$properties=$db->get_all("
			SELECT cp.*, cr.sort_order
			FROM fw_catalogue_properties as cp
			LEFT JOIN fw_catalogue_relations as cr
				ON cr.property_id=cp.id AND cr.cat_id='".$cat['id']."'
			ORDER BY name
		");
		$properties=String::unformat_array($properties);

		$smarty->assign("parent",$parent['id']);
		$smarty->assign("cat",$cat);
		$smarty->assign("mode","edit");
		$smarty->assign("cat_list",Common::get_nodes_list($cat_list));
		$smarty->assign("properties",$properties);
		$template='shop.a_edit_cat.html';

	BREAK;

	CASE ($action=='products_list'):

		$navigation[]=array("url" => BASE_URL."/admin/?mod=shop&action=products_list","title" => '������');


		if (isset($_GET['page']) && $_GET['page']!='') 
			$page=$_GET['page'];
		else 
			$page=1;

		if (isset($_GET['cat']) && $_GET['cat']!='') {
			$sort_cat=$_GET['cat'];
		}


		if (isset($_GET['type']) && $_GET['type']!='') {
				$sort_type=$_GET['type'];
		}

		if ((isset($sort_cat)) or (isset($_GET['search']) && $_GET['search']>'1')  or  (isset($sort_type))) {

			if (isset($sort_cat)) {
				$temp_cond[]=" (parent = '".$sort_cat."') ";
				$smarty->assign("cat",$sort_cat);
			}
			if (isset($_GET['search'])) {
				$temp_cond[]="(name LIKE '%".$_GET['search']."%' OR description LIKE '%".$_GET['search']."%' OR article='".$_GET['search']."')";
				$smarty->assign("search",$_GET['search']);
			}

			if (isset($_GET['type'])) {
				if ($_GET['type']!='*')
					$temp_cond[]="(product_type='".intval($_GET['type'])."')";
				else
					$temp_cond[] = "(product_type IN (SELECT id FROM fw_products_types))";
				$smarty->assign("type",$_GET['type']);
			}
			$cond=Common::get_cond($temp_cond);
		}
		else $cond="";

		if (isset($_GET['sort']) && $_GET['sort']!='') {
			$sort='ORDER BY '.$_GET['sort'].' ';
			$smarty->assign("sort",$_GET['sort']);
		}
		else $sort='ORDER BY sort_order ';
		
		if (isset($_GET['order']) && $_GET['order']!='') {
			$sort.=$_GET['order'];
			$smarty->assign("order",$_GET['order']);
		}
		else $sort.='ASC';

		$result=$db->query("SELECT COUNT(*) FROM fw_products $cond");
		$pager=Common::pager($result,PRODUCTS_PER_PAGE,$page);


		$products_list=$db->get_all("SELECT *,(SELECT name FROM fw_products_types WHERE id=p.product_type) as type_name FROM fw_products p $cond $sort LIMIT ".$pager['limit']);
		$products_list=String::unformat_array($products_list);

		//echo "SELECT *,(SELECT name FROM fw_products_types WHERE id=p.product_type) as type_name FROM fw_products p $cond $sort LIMIT ".$pager['limit'];
		$cl=Common::get_nodes_list($cat_list);

		for ($i=0;$i<count($products_list);$i++) {
			for($k=0;$k<count($cl);$k++) {
				if ($products_list[$i]['parent']==$cl[$k]['id']) {
					if (!isset($sort_cat))
						$products_list[$i]['cat_title']=$cl[$k]['full_title'];
					else
						$products_list[$i]['cat_title']=$cl[$k]['name'];
					//$products_list[$i]['price'] = $products_list[$i]['price'];
					break;
				}
			}
		}

        $smarty->assign("currency_site",$cur_site);
      	$smarty->assign("currency_admin",$cur_admin);
		$smarty->assign("total_pages",$pager['total_pages']);
		$smarty->assign("cat_list",$cl);
		$smarty->assign("type_list",$type_list);
		$smarty->assign("current_page",$pager['current_page']);
		$smarty->assign("pages",$pager['pages']);
		$smarty->assign("products_list",$products_list);

		$template='shop.a_products_list.html';

	BREAK;

	CASE ($action=='add_product'):
		
		$navigation[]=array("url" => BASE_URL."/admin/?mod=shop&action=products_list","title" => '��������');
		$navigation[]=array("url" => BASE_URL."/admin/?mod=shop&action=add_product","title" => '�������� �������');

		$cat_list=Common::get_nodes_list($cat_list);
        $types_list=$db->get_all("SELECT * FROM fw_products_types WHERE status='1' ORDER BY name");
        //$body_types=$db->get_all("SELECT * FROM fw_body_types ORDER BY name");
        //$disk_types=$db->get_all("SELECT * FROM fw_disk_types ORDER BY name");
        $smarty->assign('types_list',$types_list);
        //$smarty->assign('body_types',$body_types);
        //$smarty->assign('disk_types',$disk_types);
		$smarty->assign("cat_list",$cat_list);
		$smarty->assign("mode","add");
		$smarty->assign("cat",@$_GET['cat']);
		$template='shop.a_edit_product.html';

	BREAK;

	CASE ($action=='edit_product' && isset($_GET['id'])):

		$navigation[]=array("url" => BASE_URL."/admin/?mod=shop&action=products_list","title" => '������ ���������');
		$navigation[]=array("url" => BASE_URL."/admin/?mod=shop&action=edit_product","title" => '������������� �������');

		$id=$_GET['id'];

		$cat_list=Common::get_nodes_list($cat_list);
//		unset($cat_list[0]);

// SORRY for so strange separator
/****************************************************************/
		$product=$db->get_single("SELECT *, (SELECT
			GROUP_CONCAT(CONCAT_WS('||#||',
				cp.id,
				cp.name,
				cp.type,
				cp.elements,
				cp.status,
				(SELECT value FROM fw_products_properties AS pp WHERE pp.product_id = p.id AND pp.property_id = cr.property_id LIMIT 1)
			) ORDER BY cr.sort_order SEPARATOR '##|##')
			FROM fw_catalogue_relations AS cr
			LEFT JOIN fw_catalogue_properties AS cp ON cp.id=cr.property_id
		WHERE cr.cat_id = p.parent) as properties FROM fw_products AS p WHERE id='$id'");

		
		
		$tmp=explode("##|##",$product['properties']);
		$product['properties']=array();
		foreach ($tmp as $val => $k) {
			if (substr_count($k,"||#||")>0) {
				$tmp2=explode("||#||",$k);
				$product['properties'][$val]=$tmp2;
				if ($tmp2[2]=="1") {
					$product['properties'][$val][3]=explode("\n",$tmp2[3]);
				}
			}
		}

		$product=String::unformat_array($product);
		$product=String::unformat_array($product);

		
		//$product_properties = $db->get_all("select * from fw_products_properties where product_id='$id'");
		
		
		if ($product['additional_products']!='') {
			$additional_products=$db->get_all("SELECT * FROM fw_products WHERE id IN (".$product['additional_products'].")");
			$smarty->assign("additional_products",$additional_products);
		}
		$photos_list=$db->get_all("SELECT * FROM fw_products_images WHERE parent='$id' ORDER BY sort_order");
        $types_list=$db->get_all("SELECT * FROM fw_products_types WHERE status='1' ORDER BY name");
		$product_properties = $shop->getProductProperties($id, 'size');
		
		$colors = $db->get_all("select * from colors order by id desc");
		$smarty->assign('colors', $colors);

		$smarty->assign("currency_admin",$cur_admin);
		$smarty->assign('types_list',$types_list);
		$smarty->assign('product_properties',$product_properties);
		
		$smarty->assign('photos_list',$photos_list);
		$smarty->assign('photos_count',count($photos_list));
		$smarty->assign('photo_height',PRODUCT_PREVIEW_HEIGHT+10);
		$smarty->assign("cat_list",$cat_list);
		$smarty->assign("product",$product);
		$smarty->assign("mode","edit");
		$template='shop.a_edit_product.html';

	BREAK;

	CASE ($action=='orders'):

		$navigation[]=array("url" => BASE_URL."/admin/?mod=shop&action=orders","title" => '������ �������');

		if (isset($_GET['page'])) $page=$_GET['page'];
		else $page=1;

		if (isset($_GET['user']) && $_GET['user']>'1') {

			if ($_GET['user']!='') {
				$temp_cond[]="user='".$_GET['user']."'";
				$smarty->assign("user",$_GET['user']);
			}
			$cond=Common::get_cond($temp_cond);
		}
		else $cond="";

		if (isset($_GET['sort']) && $_GET['sort']!='') {
			$sort='ORDER BY '.$_GET['sort'].' ';
			$smarty->assign("sort",$_GET['sort']);
		}
		else $sort='ORDER BY insert_date ';
		if (isset($_GET['order']) && $_GET['order']!='') {
			$sort.=$_GET['order'];
			$smarty->assign("order",$_GET['order']);
		}
		else $sort.='DESC';

		$result=$db->query("SELECT COUNT(*) FROM fw_orders $cond");
		$pager=Common::pager($result,PRODUCTS_PER_PAGE,$page);

		/*$orders_list=$db->get_all("SELECT id,user,status,status as status_number,insert_date,total_price,
		(SELECT name FROM fw_users WHERE id=o.user) AS user_name, 
		(SELECT SUM((SELECT price FROM fw_products WHERE id=b.product_id) * b.product_count) 
		FROM fw_orders_products as b WHERE b.order_id=o.id) as total_products_price 
		FROM fw_orders o $cond $sort LIMIT ".$pager['limit']);*/
		$orders_list=$db->get_all("SELECT id,user,status,status as status_number,insert_date,total_price,
		(SELECT name FROM fw_users WHERE id=o.user) AS user_name
		FROM fw_orders o $cond $sort LIMIT ".$pager['limit']);
		
		$orders_list=String::unformat_array($orders_list);

		for ($i=0;$i<count($orders_list);$i++) {
			$orders_list[$i]['status']=str_replace($status_value,$status_name,$orders_list[$i]['status']);
			$orders_list[$i]['total_products_price'] = (($orders_list[$i]['total_price'] * $cur_admin['kurs'])/$cur_site['kurs']);
		}

		$smarty->assign("orders_list",$orders_list);
		$smarty->assign("total_pages",$pager['total_pages']);
		$smarty->assign("current_page",$pager['current_page']);
		$smarty->assign("pages",$pager['pages']);
		$template='shop.a_orders.html';

	BREAK;

	CASE ($action=='order_details'):

		$navigation[]=array("url" => BASE_URL."/admin/?mod=shop&action=orders","title" => '������ �������');

		$id=$_GET['id'];

		$total_summ = $db->get_single("SELECT SUM(price) FROM fw_products WHERE id IN (SELECT product_id FROM fw_orders_products WHERE order_id='$id')");

		$user_info=$db->get_single("SELECT fw_orders.*, promo_codes.*
						FROM fw_orders
						LEFT JOIN promo_codes_users on fw_orders.id = promo_codes_users.order_id
						LEFT JOIN promo_codes on promo_codes_users.code = promo_codes.code
						WHERE fw_orders.id='$id'");

		$smarty->assign("user_info",$user_info);

		$orders=$db->get_all("SELECT a.*,
		b.product_id as product_id,
		b.properties,
		c.parent as parent,
		c.hit as hit,
		b.product_count,
		c.price as price,
		(b.product_price*b.product_count) as total_summ,
		b.product_price,
		order_id,
		product_count,
		c.name 	
		FROM `fw_orders` as a 
		INNER JOIN
		(fw_orders_products as b INNER JOIN fw_products as c ON b.product_id=c.id)	ON a.id=b.order_id 
		WHERE a.id='$id'");

		$cl=Common::get_nodes_list($cat_list);

		for ($i=0;$i<count($orders);$i++) {
			for($k=0;$k<count($cl);$k++) {
				if ($orders[$i]['parent']==$cl[$k]['id']) {
					$orders[$i]['cat_title']=$cl[$k]['full_title'];
					break;
				}
			}
		}


		$navigation[]=array("url" => BASE_URL."/admin/?mod=shop&action=order_details","title" => '����� ����� '.$id);

		for ($i=0;$i<count($status_value);$i++) {
			$status_list[$i]['value']=$status_value[$i];
			$status_list[$i]['name']=$status_name[$i];
		}

		$order_price = 0;
		if (count($orders)>0){
			foreach ($orders as $key=>$val){
				if (strlen(trim($orders[$key]['product_price']))>0){
					/*$orders[$key]['total_summ']=number_format(($orders[$key]['total_summ'] * $cur_admin['kurs'])/$cur_site['kurs'], 2, '.', '');
					$order_price += $orders[$key]['total_summ'];*/
					if (!empty($orders[$key]['properties'])){
						$orders[$key]['properties'] = json_decode($orders[$key]['properties'], true);
					}
				}
			}
		}

		$smarty->assign("order_price",$order_price);

		$next = $db->get_single("SELECT id as next FROM fw_orders WHERE (id=(SELECT min(id) FROM fw_orders WHERE id > '$id'))");
		$previous = $db->get_single("SELECT id as previous FROM fw_orders WHERE (id=(SELECT max(id) FROM fw_orders WHERE id < '$id'))");
		
		if (intval($next['next'])>0) $smarty->assign("next_order",$next['next']);
		if (intval($previous['previous'])>0) $smarty->assign("previous_order",$previous['previous']);
		
		$smarty->assign("orders",$orders);
		$smarty->assign("status_list",$status_list);
		$template='shop.a_order_details.html';

	BREAK;

	CASE ($action=='catalogue'):

		$navigation[]=array("url" => BASE_URL."/admin/?mod=shop","title" => '���������');

		$smarty->assign("cat_list",$cat_list);

	BREAK;

	CASE ($action=='properties'):

		$properties_list=$db->get_all("SELECT * FROM fw_catalogue_properties ORDER BY name");
		$properties_list=String::unformat_array($properties_list);

		$navigation[]=array("url" => BASE_URL."/admin/?mod=shop","title" => '�������� �������');

		$smarty->assign("properties_list",$properties_list);
		$template='shop.a_products_properties.html';

	BREAK;

	CASE ($action=='add_property'):

		$navigation[]=array("url" => BASE_URL."/admin/?mod=shop","title" => '�������� �������');

		$smarty->assign("mode","add");
		$template='shop.a_products_properties.html';

	BREAK;

	CASE ($action=='edit_property' && isset($_GET['id'])):

		$navigation[]=array("url" => BASE_URL."/admin/?mod=shop","title" => '�������������� ��������');

		$id=$_GET['id'];

		$property=$db->get_single("SELECT * FROM fw_catalogue_properties WHERE id='$id'");
		$property=String::unformat_array($property);

		$smarty->assign("mode","edit");
		$smarty->assign("property",$property);
		$template='shop.a_products_properties.html';

	BREAK;

	CASE ($action=='mini_catalogue' && isset($_GET['order_id'])):

		$order_id=$_GET['order_id'];
		$smarty->assign("order_id",$order_id);

		
		if (isset($_GET['add_product']) && isset($_GET['order_id'])) {

			Common::check_priv("$priv");

			$product_found=false;
			$product_id=$_GET['add_product'];
			$order_id=$_GET['order_id'];
			$products=array();
			$id_list='';
			$total_price='';

			//$order=$db->get_single("SELECT products FROM fw_orders WHERE id='$order_id'");
			$order=$db->get_single("SELECT id FROM fw_orders_products WHERE order_id='$order_id' AND product_id='$product_id'");

			if (trim($order['id'])!="") {

				/*$products=explode(",",$order['products']);
				for ($i=0;$i<count($products);$i++) {

					list($id,$number)=explode("|",$products[$i]);
					$id_list.=$id.',';
					if ($id==$product_id) {
						$number++;
						$products[$i]=$id.'|'.$number;
						$product_found=true;
					}
				}*/
				$product_found=true;
			}
			if (!$product_found) {
				/*$products[]=$product_id.'|1';
				$product=$db->get_single("SELECT price FROM fw_products WHERE id='$product_id'");
				$total_price=$product['price'];*/
				$db->query("INSERT fw_orders_products (order_id,product_id,product_count) VALUES ('$order_id','$product_id',1)");
				
			}
			else {

				/*$id_list=substr($id_list,0,-1);

				$products_for_total=$db->get_all("SELECT id,price FROM fw_products WHERE id IN ($id_list)");

				for ($i=0;$i<count($products_for_total);$i++) {
					for ($p=0;$p<count($products);$p++) {
						list($id,$number)=explode("|",$products[$p]);
						if ($products_for_total[$i]['id']==$id) {
							$total_price=$total_price+($number*$products_for_total[$i]['price']);
						}
					}
				}*/
				$db->query("UPDATE fw_orders_products SET product_count=product_count+1 WHERE order_id='$order_id' AND product_id='$product_id'");

			}
			/*$products=implode(",",$products);

			$db->query("UPDATE fw_orders SET products='$products', total_price='$total_price' WHERE id='$order_id'");*/
			$_SESSION['fw_product_added']='1';
			$location=$_SERVER['HTTP_REFERER'];
			header("Location: $location");

		}

		
		
		if (isset($_GET['cat'])) {

			if (isset($_SESSION['fw_product_added']) && $_SESSION['fw_product_added']=='1') {
				$_SESSION['fw_product_added']='0';
				$smarty->assign("product_added",'1');
			}

			if (isset($_GET['page'])) $page=$_GET['page'];
			else $page=1;

			$result=$db->query("SELECT COUNT(*) FROM fw_products");
			$pager=Common::pager($result,PRODUCTS_PER_PAGE,$page);

			$cat=$_GET['cat'];
			$products_list=$db->get_all("SELECT * FROM fw_products WHERE parent='$cat' AND status='1' LIMIT ".$pager['limit']);
			$smarty->assign("products_list",$products_list);
			$smarty->assign("cat",$cat);

			$smarty->assign("total_pages",$pager['total_pages']);
			$smarty->assign("current_page",$pager['current_page']);
			$smarty->assign("pages",$pager['pages']);

		}
		else {
			$smarty->assign("cat_list",$cat_list);
		}

		$template='mini_catalogue.html';
		$template_mode='single';

	BREAK;

	CASE ($action=='mini_catalogue' && isset($_GET['product_id'])):

		$product_id=$_GET['product_id'];
		$smarty->assign("product_id",$product_id);

		if (isset($_GET['add_product']) && isset($_GET['product_id'])) {

			Common::check_priv("$priv");

			$product_id=$_GET['product_id'];
			$add_product=$_GET['add_product'];

			$product=$db->get_single("SELECT additional_products FROM fw_products WHERE id='$product_id'");
			$product=String::unformat_array($product);

			if (!strstr($product['additional_products'],$add_product)) {
				if ($product['additional_products']!='') $additional_products=$product['additional_products'].','.$add_product;
				else $additional_products=$add_product;
			}

			$db->query("UPDATE fw_products SET additional_products='$additional_products' WHERE id='$product_id'");

			$_SESSION['fw_product_added']='1';
			$location=$_SERVER['HTTP_REFERER'];
			header("Location: $location");

		}

		if (isset($_GET['cat'])) {

			if (isset($_SESSION['fw_product_added']) && $_SESSION['fw_product_added']=='1') {
				$_SESSION['fw_product_added']='0';
				$smarty->assign("product_added",'1');
			}

			if (isset($_GET['page'])) $page=$_GET['page'];
			else $page=1;

			$result=$db->query("SELECT COUNT(*) FROM fw_products");
			$pager=Common::pager($result,PRODUCTS_PER_PAGE,$page);

			$cat=$_GET['cat'];
			$products_list=$db->get_all("SELECT * FROM fw_products WHERE parent='$cat' AND status='1' LIMIT ".$pager['limit']);
			$products_list=String::unformat_array($products_list);
			$smarty->assign("products_list",$products_list);
			$smarty->assign("cat",$cat);

			$smarty->assign("total_pages",$pager['total_pages']);
			$smarty->assign("current_page",$pager['current_page']);
			$smarty->assign("pages",$pager['pages']);

		}
		else {
			$smarty->assign("cat_list",$cat_list);
		}
		$template='mini_catalogue.html';
		$template_mode='single';

	BREAK;

	CASE ($action=='delete_additional_product' && isset($_GET['id']) & isset($_GET['from'])):

		$id=$_GET['id'];
		$from=$_GET['from'];

		$product=$db->get_single("SELECT additional_products FROM fw_products WHERE id='$from'");

		$additional_products=explode(",",$product['additional_products']);

		for ($i=0;$i<count($additional_products);$i++) {

			if ($additional_products[$i]==$id) unset($additional_products[$i]);

		}

		if (count($additional_products)>0) $additional_products=implode(",",$additional_products);
		else $additional_products='';

		$db->query("UPDATE fw_products SET additional_products='$additional_products' WHERE id='$from'");

		$location=$_SERVER['HTTP_REFERER'];
		header("Location: $location");

	BREAK;

  CASE ($action=='types'):

    $types_list=$db->get_all("SELECT *, (SELECT COUNT(*) FROM fw_products WHERE product_type=fw_products_types.id) as tovars FROM fw_products_types ORDER BY name");
    $types_list=String::unformat_array($types_list);
    $navigation[]=array("url" => BASE_URL."/admin/?mod=shop","title" => '���� ���������');
    $smarty->assign("types_list",$types_list);
    $template='shop.a_products_types.html';

  BREAK;


  CASE ($action=='add_type'):

    $navigation[]=array("url" => BASE_URL."/admin/?mod=shop","title" => '�������� ��� ��������');

    $smarty->assign("mode","add");
    $template='shop.a_products_types.html';

  BREAK;

  CASE ($action=='edit_type' && isset($_GET['id'])):

    $navigation[]=array("url" => BASE_URL."/admin/?mod=shop","title" => '������������� ��� ��������');

    $id=$_GET['id'];

    $type=$db->get_single("SELECT * FROM fw_products_types WHERE id='$id'");
    $type=String::unformat_array($type);
    $rel = $db->get_all("SELECT * FROM fw_cats_types_relations WHERE type_id=$id");
    $rel=String::unformat_array($rel);
    $smarty->assign("cat_list",$cat_list);
    $smarty->assign("rel",$rel);
    $smarty->assign("mode","edit");
    $smarty->assign("type",$type);
    $template='shop.a_products_types.html';

  BREAK;


  CASE ($action=='currency'):

    $navigation[]=array("url" => BASE_URL."/admin/?mod=shop&action=currency","title" => '����� �����');

    $cur_list=$db->get_all("SELECT * FROM fw_currency");
    $cur_list=String::unformat_array($cur_list);

    $smarty->assign("cur_list",$cur_list);

    $template='shop.a_cur_list.html';

  BREAK;


  CASE ($action=='add_cur'):

    $navigation[]=array("url" => BASE_URL."/admin/?mod=shop","title" => '���� �����');

    $smarty->assign("mode","add");
    $template='shop.a_cur_edit.html';

  BREAK;

  CASE ($action=='edit_cur' && isset($_GET['id'])):

    $navigation[]=array("url" => BASE_URL."/admin/?mod=shop","title" => '�������������� ������');

    $id=$_GET['id'];

    $cur=$db->get_single("SELECT * FROM fw_currency WHERE id='$id'");
    $cur=String::unformat_array($cur);

    $smarty->assign("mode","edit");
    $smarty->assign("cur",$cur);
    $template='shop.a_cur_edit.html';

  BREAK;


	DEFAULT:

		$count_cat=$db->get_single("SELECT COUNT(*) AS count FROM fw_catalogue");
		$count_products=$db->get_single("SELECT COUNT(*) AS count FROM fw_products");
		$count_orders=$db->get_single("SELECT COUNT(*) AS count FROM fw_orders");
		$total_price=$db->get_single("SELECT SUM(total_price) AS total FROM fw_orders");

		if ($total_price['total']=='') $total_price='0.00';
		else $total_price=$total_price['total'];

		$smarty->assign("count_cat",$count_cat['count']);
		$smarty->assign("count_products",$count_products['count']);
		$smarty->assign("count_orders",$count_orders['count']);
		$smarty->assign("total_price",$total_price);

		$template='statistics.html';

}

?>