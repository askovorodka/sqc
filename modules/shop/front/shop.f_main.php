<?php

//error_reporting(E_ALL);
//ini_set('display_errors','On');

//$_SESSION['fw_basket']=array();

if ($switch_default=='on' or $main_module=='on') {

	$basket_number=0;
	$basket_total=0;
	for ($i=0;$i<count(@$_SESSION['fw_basket']);$i++) {
		$basket_number+=@$_SESSION['fw_basket'][$i]['number'];
		$basket_total+=(@$_SESSION['fw_basket'][$i]['price']*@$_SESSION['fw_basket'][$i]['number']);
	}
	$smarty->assign("basket_number",$basket_number);
	$smarty->assign("basket_total",number_format($basket_total,2,'.',''));
	$smarty->assign("currency",DEFAULT_CURRENCY);

}

if  ($main_module=='on')
{

//$css[]=BASE_URL.'/templates/thickbox.css';

require_once 'lib/class.mail.php';
require_once 'lib/class.image.php';
require_once 'lib/class.photoalbum.php';
require_once 'lib/class.table.php';
require_once 'lib/class.form.php';
require_once 'lib/class.users.php';
require_once 'lib/class.password.php';
//require_once 'modules/shop/front/class.shop.php';

$navigation[]=array("url" => $module_url,"title" => $node_content['name']);
$smarty->assign("module_url",BASE_URL.'/'.$module_url);

$cabinet_url=$db->get_single("SELECT url FROM fw_tree WHERE module='cabinet'");
$smarty->assign("cabinet_url",$cabinet_url['url']);

//$cl=$db->get_all("SELECT *,(SELECT COUNT(*) FROM fw_products WHERE parent=c.id AND status='1') AS products FROM fw_catalogue c WHERE c.status='1' ORDER BY param_left ");
$cl=$db->get_all("SELECT * FROM fw_catalogue c WHERE c.status='1' ORDER BY param_left ");


if (preg_match("/^page_[0-9]+$/",$url[$n])) {
	list(,$page)=explode("_",$url[$n]);
	$url=array_values($url);
	unset($url[$n]);
	unset($current_url_pages[count($current_url_pages)-1]);
	$n=count($url)-1;
}
else $page=1;

$filterHash = null;
if (preg_match("/(\?|\&)filter=([0-9a-z]+)$/",$url[$n],$filter)) {
  $filterHash = $filter[2];
  unset($url[$n]);
  unset($current_url_pages[count($current_url_pages)-1]);
  $n=count($url)-1;
  $smarty->assign("filterHash", $filterHash);
}

$responseJSON = false;
if (preg_match("/(\?|\&)json=true$/",$url[$n],$match))
{
	$responseJSON = true;
	unset($url[$n]);
	unset($current_url_pages[count($current_url_pages)-1]);
	$n=count($url)-1;
}

if (isset($type) && $type=='all')
	unset($type);

$cur_site=$db->get_single("SELECT kurs,znak FROM fw_currency WHERE id=".CURRENCY_SITE);
$cur_site=String::unformat_array($cur_site);
$cur_site2=$db->get_single("SELECT kurs,znak FROM fw_currency WHERE id=".CURRENCY_SITE2);
$cur_site2=String::unformat_array($cur_site2);

$cur_admin=$db->get_single("SELECT kurs,znak FROM fw_currency WHERE id=".CURRENCY_ADMIN);
$cur_admin=String::unformat_array($cur_admin);
$smarty->assign("currency_site",$cur_site);
$smarty->assign("currency_site2",$cur_site2);

$shop = new Shop($db);
$users = new Users();

/*-----------------РАЗЛИЧНЫЕ ДЕЙСТВИЯ-----------------*/

if (isset($_REQUEST['filterhash']))
{
	$return = array('status' => 'success', 'data' => null);
	if (!empty($_POST))
	{
		$dataJson = json_encode($_POST);
		$hash = sha1($dataJson . microtime(true));
		$db->query("replace into filter_hashes (`hash`, `data`) values('{$hash}', '{$dataJson}')");
		$return = array('status' => 'success', 'data' => $hash);
	}
	header('Content-Type:text/json;charset:utf8;');
	echo json_encode($return, true);
	die;
}

if (isset($_POST['submit_comment'])) {

	$id=$_POST['brand_id'];

	$comment=String::secure_user_input($_POST['ntrcn']);
	$comment=Common::strip_forum_tags($comment);

	//$author=$_SESSION['fw_user']['id'];
	$username = strip_tags($_POST['bvz']);
	$email = strip_tags($_POST['tvfbk']);

	if (trim($_POST['username']) == '')
	{
		if (trim($_POST['email']) == '')
		{
			if (trim($_POST['text']) == '')
			{
				if ($comment!='')
				{
					$db->query("INSERT INTO fw_products_comments(product_id,username, email,text,insert_date) VALUES('$id','$username', '$email','$comment','".time()."')");
				}
			}
		}
	}

	
	$location=@$_SERVER['HTTP_REFERER'];
	header("Location: $location");
	die();
}


/*--------------------ОТОБРАЖЕНИЕ---------------------*/
//Common::dumper($_SESSION['fw_basket']);
if (!isset($_SESSION['fw_basket'])) $_SESSION['fw_basket']=array();

SWITCH (TRUE) {

	CASE ($url[$n-1] == 'check_promo' && preg_match("/\?value=(.*)$/",$url[$n])):
		$value = filter_var($_GET['value'], FILTER_SANITIZE_STRING);
		$value = trim($value);

		$return = array('status' => 'error', 'data' => array());
		$findPromo = $shop->findCode($value, 1);
		if (!empty($findPromo)){
			$return['status'] = 'success';
			$return['data'] = $findPromo;
		}

		header('ContentType:text/json;charset:utf8');
		echo json_encode($return);
		die;

	BREAK;

	CASE (count($url) > 0 && $url[$n] == 'checkemail'):
		$return = ['status' => 'error', 'data' => []];
		if (!empty($_POST['email']))
		{
			$email = trim($_POST['email']);
			$query = "SELECT id,login FROM fw_users WHERE mail = '{$email}' ";
			$result = $db->get_single($query);
			if (!empty($result['id']))
			{
				$return['status'] = 'success';
				$return['data'] = $result;
			}
		}

		header("Content-Type:text/json;charset:utf8;");
		echo json_encode($return);
		die();

	BREAK;


	CASE (@$url[$n]=='step1' && $url[$n-1]=='basket'):
		
		//идет авторизация на 1 шаге
		if (isset($_POST['submit_login']))
		{
			
			$users->setEmail($_POST['email']);
			$users->setPassword($_POST['password']);
			if ($users->get_login())
			{
				header("Location: ".BASE_URL.'/catalog/basket/step1/');
				die();
			}
			else
			{
				$smarty->assign('error_auth_message','Неверно введен логин или пароль');
			}
			
		}

		
		if ($user_id = $users->is_auth_user())
		{
			$user = $users->get_user($user_id);
			$smarty->assign('user', $user);
		}

		$page_found=true;
		$navigation[]=array("url" => 'basket',"title" => 'Моя корзина');
		$navigation[]=array("url" => 'step1',"title" => 'Оформление заказа');
		$title="Оформление заказа";
		$template='basket_step1.html';

	BREAK;

	CASE (@$url[$n]=='step2' && $url[$n-1]=='basket'):
		$total_price=0;
		for ($i=0;$i<count($_SESSION['fw_basket']);$i++) {
			$total_price+=$_SESSION['fw_basket'][$i]['price']*$_SESSION['fw_basket'][$i]['number'];
		}
		$smarty->assign("basket",$_SESSION['fw_basket']);
		if (isset($_SESSION['fw_user'])) $smarty->assign("user",$_SESSION['fw_user']);
		$smarty->assign("total_price",sprintf("%.2f",$total_price));

		$page_found=true;
		$template='basket_step2.html';
	BREAK;

	CASE (@$url[$n-1] == 'order_later' && @$url[$n] == 'add'):
		if (!empty($_POST['product_id']))
		{
			$product_id =(int) $_POST['product_id'];

			$found = false;
			$products = array();
			if (!empty($_COOKIE['order_later'])){
				$products = @unserialize($_COOKIE['order_later']);
				if (in_array($product_id, $products)){
					$found = true;
				}

			}
			if (!$found){
				$products[] = $product_id;
				setcookie('order_later',serialize($products), (time() + 3600*24*365),'/','');
			}

			header('Content-Type:text/json;charset:utf8');
			echo json_encode(array('status' => 'success', 'data' => json_encode($products)));
			die;

		}
	BREAK;

	CASE ($url[$n]=='order_later' && count($url)==2):
		$page_found=true;
		$navigation[]=array("url" => 'order_later',"title" => 'Товары отложенные на потом');

		/*if (isset($_POST['order_later_remove']))
		{
			unset($_SESSION['fw_basket']);
			$location=$_SERVER['HTTP_REFERER'];
			header("Location: $location");
			die();
		}*/

		if (!empty($_COOKIE['order_later']))
		{
			$products = @unserialize($_COOKIE['order_later']);

			if (!empty($products))
			{
				$sql ="
				SELECT p.*,
						(SELECT id FROM fw_products_images i WHERE i.parent=p.id ORDER BY sort_order ASC LIMIT 1) AS image,
						(SELECT ext FROM fw_products_images WHERE parent=p.id ORDER BY insert_date DESC LIMIT 1) AS ext,
						(SELECT name FROM fw_products_types WHERE id=p.product_type LIMIT 0,1) AS type_name,
						(SELECT id FROM fw_products_types WHERE id=p.product_type LIMIT 0,1) AS type_id
					FROM fw_products AS p
					WHERE
						p.status='1' and p.id in (" . implode(',', $products) . ")";

				$products_list=$db->get_all($sql);
				foreach ($products_list as $v => $key)
				{
					$products_list[$v]['full_url'] = $shop->getFullUrlProduct($products_list[$v]['id'], "catalog");
					$products_list[$v]['sizes'] = $shop->getProductPropertiesByEntity($products_list[$v]['id'], PROPERTY_ENTITY_SIZE);
				}

				$smarty->assign("products_list",$products_list);

			}

		}

		$template='shop.order_later.html';

		BREAK;

	//добавляем продукт в корзину
	CASE (@$url[$n-1] == 'basket' && @$url[$n] == 'add'):

		//header("Content-type: text/html; charset=Windows-1251");
		if (!empty($_POST) && !empty($_POST['product_id']) && !empty($_POST['product_count']))
		{

			$productId = (int) $_POST['product_id'];
			$size = null;
			$color = null;
			$number_found=false;
		
			$properties = array();
			$number = $_POST['product_count'];
			if (!empty($_POST['property_size'])){
				$properties['size'] = $_POST['property_size'];
				$size = $_POST['property_size'];
			}
			elseif(!empty($_POST['property_size_brand'])){
				$properties['size_brand'] = $_POST['property_size_brand'];
				$size = $_POST['property_size_brand'];
			}
			if (!empty($_POST['property_color'])){
				$properties['color'] = $_POST['property_color'];
				$color = $_POST['property_color'];
			}

			$sessionDataKey = sprintf("%d|%s|%s", $productId, $size, $color);
			$sessionKey = md5($sessionDataKey);

			$product=$db->get_single("SELECT id,parent,name,price,price_sale,sale,article,
						(SELECT id FROM fw_products_images i WHERE i.parent=fw_products.id ORDER BY insert_date DESC LIMIT 1) AS image,
						(SELECT ext FROM fw_products_images WHERE parent=fw_products.id ORDER BY insert_date DESC LIMIT 1) AS ext
		 				FROM fw_products WHERE id='" . $productId . "' AND status='1'");


			$product['properties'] = $properties;
		
			for ($i=0;$i<count($_SESSION['fw_basket']);$i++)
			{
				if ($_SESSION['fw_basket'][$i]['sessionKey'] == $sessionKey)
				{
					//$_SESSION['fw_basket'][$i]['number']= number_format($_SESSION['fw_basket'][$i]['number']+$number,2,'.','');
					$_SESSION['fw_basket'][$i]['number'] += 1;
					//$_SESSION['fw_basket'][$i]['properties'] = $properties;
					$number_found=true;
				}
			}
			if (!$number_found)
			{
				$product['number'] = $number;
				$_SESSION['fw_basket'][] = array(
						'sessionKey' => $sessionKey,
						'product_id' => $productId,
						'properties' => $properties,
						'number' => $number,
						'image' => $product['image'],
						'ext' => $product['ext'],
						'name' => $product['name'],
						'sale' => $product['sale'],
						'article' => $product['article'],
						'parent' => $product['parent'],
						'id' => $product['id'],
						'price' => !empty($product['price_sale']) ? (float)$product['price_sale'] : (float) $product['price']);
			}

		$basket_number=0;
		$basket_total=0;
		for ($i=0;$i<count(@$_SESSION['fw_basket']);$i++)
		{
			$basket_number += @$_SESSION['fw_basket'][$i]['number'];
			$basket_total  += @$_SESSION['fw_basket'][$i]['price'] * @$_SESSION['fw_basket'][$i]['number'];
			$basket_total  = number_format($basket_total,2,'.','');
		}

			$switch_off_smarty=true;
			//$basket_total = number_format($basket_total,2,",","");
			//print "$basket_number;$basket_total";

		}
		
		$location=$_SERVER['HTTP_REFERER'];
		header("Location: $location");
		die();
				//exit();

	BREAK;


	/*CASE (@$url[$n-1]=='search_product' && preg_match("/\?keyword=(.+)$/",$url[$n]) && count($url)==3):

  		$patterns = array('/\s+/', '/"+/', '/%+/');
  		$replace = array('');
  		$keyword = $_REQUEST['keyword'];
  		$keyword = preg_replace($patterns,$replace,$keyword);
  		
  		$navigation[]=array("url" => 'product_search',"title" => 'Результаты поиска');

  		$products = $shop->search($keyword);
  		
  		if ($products)
  		{
  			foreach ($products as $key=>$val)
  			{
  				$products[$key]['image'] = $shop->getProductImage($val['id']);
  				$products[$key]['category'] = $shop->getCategory($val['parent']);
  				$products[$key]['full_url'] = $shop->getFullUrlProduct($val['id'],'catalog');
  			}
  			$smarty->assign('products', $products);
  		}
  		
  		$smarty->assign('keyword', $keyword);
  		$page_found = true;
  		$template = "shop.f_search.html";
  		

	BREAK;*/

	CASE (preg_match("/^([0-9]+)$/",$url[$n]) && $url[$n-1]=='delete' && $url[$n-2]=='order_later' && count($url)==4):

		if (!empty($_COOKIE['order_later']))
		{

			$product_id = (int) $url[$n];
			$products = @unserialize($_COOKIE['order_later']);
			foreach($products as $key=>$val	)
			{
				if ($val == $product_id){
					unset($products[$key]);
				}
			}

			setcookie('order_later',serialize($products), (time() + 3600*24*365),'/','');

		}

		$page_found=true;
		$location=$_SERVER['HTTP_REFERER'];
		header("Location: $location");
		die();

	BREAK;



	CASE (preg_match("/^([a-z0-9]+)$/",$url[$n]) && $url[$n-1]=='delete' && $url[$n-2]=='basket' && count($url)==4):

		for ($i=0;$i<count($_SESSION['fw_basket']);$i++)
		{
			if ($_SESSION['fw_basket'][$i]['sessionKey'] == $url[$n])
			{
				unset($_SESSION['fw_basket'][$i]);
			}
		}
		$array=$_SESSION['fw_basket'];
		unset($_SESSION['fw_basket']);
		foreach ($array as $k=>$v)
		{
			$_SESSION['fw_basket'][]=$v;
		}
		$page_found=true;
		$location=$_SERVER['HTTP_REFERER'];
		header("Location: $location");
		die();

	BREAK;

	CASE ($url[$n]=='basket' && count($url)==2):

		$page_found=true;
		$navigation[]=array("url" => 'basket',"title" => 'Моя корзина');

		if (isset($_POST['basket_remove']))
		{
			unset($_SESSION['fw_basket']);
			$location=$_SERVER['HTTP_REFERER'];
			header("Location: $location");
			die();
		}

		if (isset($_POST['basket_recount'])) {
			foreach ($_POST['edit_number'] as $k=>$v)
			{
				for ($i=0; $i < count($_SESSION['fw_basket']);$i++)
				{
					if ($_SESSION['fw_basket'][$i]['sessionKey'] == $k && preg_match("/^([a-z0-9]+)$/",$v)) {
						$_SESSION['fw_basket'][$i]['number']=$v;
					}
				}
			}

			$location=$_SERVER['HTTP_REFERER'];
			header("Location: $location");
			die();
		}

		if (count($_SESSION['fw_basket'])>0)
		{
			$total_price=0;
			for ($i=0;$i<count($_SESSION['fw_basket']);$i++)
			{
				$total_price+=$_SESSION['fw_basket'][$i]['price']*$_SESSION['fw_basket'][$i]['number'];
				$total_price = number_format($total_price,2,'.','');
			}

        $sess = &$_SESSION;
        foreach($sess['fw_basket'] as $key=>$val)
		{
        	//foreach($sess['fw_basket'][$key] as $key2=>$val2)
        	//$sess['fw_basket'][$key]['price_number'] = sprintf("%.2f",$sess['fw_basket'][$key]['price']*$sess['fw_basket'][$key]['number']);
        	$sess['fw_basket'][$key]['price_number'] = number_format(($sess['fw_basket'][$key]['price']*$sess['fw_basket'][$key]['number']),2,'.','');
        	$sess['fw_basket'][$key]['full_url'] = $shop->getFullUrlProduct($sess['fw_basket'][$key]['id'], 'catalog');
        	$sess['fw_basket'][$key]['image'] = $shop->getProductImage($sess['fw_basket'][$key]['id']);
        		
        }

			$smarty->assign("basket",$_SESSION['fw_basket']);
			//$smarty->assign("total_price",sprintf("%.2f",$total_price));

			if ($users->is_auth_shopuser()){
				$smarty->assign('user_register_sale', ($total_price * 5) / 100 );
				$total_price -= ($total_price * 5) / 100;
			}

			$smarty->assign("total_price",$total_price);


			if (isset($_SESSION['shopuser'])) {
				$smarty->assign("user", $_SESSION['shopuser']);
			}
		}


		if ($userId = Common::check_auth_shop('user'))
		{
			$user = $users->get_user($userId);
			$smarty->assign('profile', $user);
		}



		$template='basket.html';

	BREAK;

	CASE ($url[$n]=='confirm' && $url[$n-1]=='basket' && count($url)==3):

		if (!isset($_SESSION['fw_user'])) {
			$location=BASE_URL.'/'.$cabinet_url['url'].'/register';
			header("Location: $location");
			die();
		}


		$navigation[]=array("url" => 'basket',"title" => 'Моя корзина');
		$navigation[]=array("url" => 'confirm',"title" => 'Подтверждение заказа');

		$total_price=0;
		for ($i=0;$i<count($_SESSION['fw_basket']);$i++) {
			$total_price+=$_SESSION['fw_basket'][$i]['price']*$_SESSION['fw_basket'][$i]['number'];
		}
		$smarty->assign("basket",$_SESSION['fw_basket']);
		if (isset($_SESSION['fw_user'])) $smarty->assign("user",$_SESSION['fw_user']);
		$smarty->assign("total_price",sprintf("%.2f",$total_price));

		$page_found=true;
		$template='confirm_order.html';


	BREAK;


	CASE ($url[$n]=='final' && $url[$n-1]=='basket' && count($url)==3):

		$page_found=true;
		$navigation[] = array("url" => "/catalog/basket/final/", "title" => "Финальная страница заказа");
		$navigation[] = array("url" => "/catalog/basket/final/", "title" => "Финальная страница заказа");
		$template='basket_final.html';

	BREAK;


	CASE ($url[$n]=='submit' && $url[$n-1]=='basket' && count($url)==3):


		if (isset($_POST['submit_order']))
		{
			
			//оформление с регистрацией
			//сначало регим пользователя
			$error_register = false;
			$user_register = false;
			$userId = null;
			$user = null;
			$promoSale = 0;
			$registerSale = 0;
			$email = null;

			if (!empty($_POST['cart_email']) && !$users->is_auth_shopuser() && !empty($_POST['cart_password']))
			{

				$users->setEmail($_POST['cart_email']);
				$users->setName($_POST['cart_name']);
				$users->setPhone1($_POST['cart_phone']);
				$users->setAddress($_POST['cart_address']);

				if (!empty($_POST['cart_password']))
				{
					$users->setPassword($_POST['cart_password']);
				}
				else
				{
					$password = new Password();
					$users->setPassword($password->generate());
				}

				if (!$users->get_user_by_email($_POST['cart_email']))
				{
					$user = $users->register();
					$user_register = true;
				}
				else
				{
					$smarty->assign('error_register_message','Пользователь с тами email уже зарегистрирован');
					$error_register = true;
				}
				
				//если ввел пароль, то отпавляем письмо о регистрации пользователя
				if (!empty($_POST['cart_password']) && $user_register)
				{
					$mail_template = $db->get_single("SELECT template FROM fw_mails_templates WHERE mail_key='REGISTOR_MAIL'");
					$message_body=$smarty->fetch('/modules/cabinet/front/templates/registration_mail.txt');
					$message_body = $mail_template['template'];
					$message_body = str_replace("{site_url}",BASE_URL,$message_body);
					$message_body = str_replace("{login}",$user['login'],$message_body);
					$message_body = str_replace("{password}",$_POST['cart_password'],$message_body);
			        $headers  = "Content-type: text/html; charset=windows-1251 \r\n";
			        $headers .= "From: ". MAIL_FROM .".ru>\r\n";
					Mail::send_mail($user['mail'],MAIL_FROM,"Регистрация на сайте ".BASE_URL,$message_body,"","html","standard","windows-1251");
				}

			}
			elseif($userId = $users->is_auth_shopuser()){
				$user = $users->get_user($userId);
				$user_register = true;
			}
			
			if (!empty($user['id'])){
				$userId = $user['id'];
			}
			
			if (empty($_SESSION['fw_basket']))
			{
				header("Location: ".BASE_URL);
				die();
			}
			elseif (!$error_register)
			{
				$navigation[]=array("url" => 'basket',"title" => 'Моя корзина');
				$navigation[]=array("url" => 'confirm',"title" => 'Ваш заказ выполнен');

				$products_list='';
				$total_number=0;

				$phone = $_POST['cart_phone'];
				$name=$_POST['cart_name'];
				$address=$_POST['cart_address'];
				$comment = $_POST['cart_message'];
				$delivery = $_POST['delivery'];
				$email = !empty($user['mail']) ? $user['mail'] : null;
				
				if ($delivery == 2)
				{
					$order_price = DELIVERY_COAST;
				}
				else 
				{
					$order_price = 0;
				}

				$total_price=0;
				for ($i=0;$i<count($_SESSION['fw_basket']);$i++)
				{
					$total_price+=$_SESSION['fw_basket'][$i]['price'] * $_SESSION['fw_basket'][$i]['number'];
				}

				$orderPromo = false;

				if (!empty($_REQUEST['promo_code']))
				{
					$code = trim($_REQUEST['promo_code']);
					$code = filter_var($code, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
					$findPromo = $shop->findCode($code,1);
					if (!empty($findPromo['percent']))
					{
						$promoExist = $shop->findCodeByUserData($code, preg_replace("/\D/","", $phone),$email);
						if (empty($promoExist['code']))
						{
							$orderPromo = true;
							$percent = (int) $findPromo['percent'];
							$total_price -= ($percent * $total_price) / 100;
							$promoSale = (($percent * $total_price) / 100);
							$smarty->assign('promoSale', (($percent * $total_price) / 100) );
						}
					}

				}

				if ($user_register)
				{
					$registerSale = (5 * $total_price) / 100;
					$total_price -= $registerSale;
					$smarty->assign('registerSale', $registerSale);
				}

				$total_price += $order_price;
				$total_price = number_format($total_price,2,'.','');

				$db->query("INSERT INTO fw_orders (
					`user`,
					name,
					email,
					comment,
					total_price,
					insert_date,
					delivery,
					address,
					phone,
					order_price, promo_sale, register_sale)
					VALUES('{$userId}','{$name}', '{$email}', '{$comment}', '{$total_price}',
					'".time()."', '{$delivery}','{$address}','{$phone}', '{$order_price}', '{$promoSale}', '{$registerSale}')");

				$order_id = mysql_insert_id();
				$rel_prod = array();
				for ($i=0;$i<count($_SESSION['fw_basket']);$i++) {
					//$products_list.=$_SESSION['fw_basket'][$i]['id'].'|'.$_SESSION['fw_basket'][$i]['number'].',';
					$total_number=$total_number+$_SESSION['fw_basket'][$i]['number'];
					$rel_prod[] = "(
						'".$_SESSION['fw_basket'][$i]['id']."',
						'".$order_id."',
						'".$_SESSION['fw_basket'][$i]['number']."',
						'{$_SESSION['fw_basket'][$i]['price']}',
						'".json_encode($_SESSION['fw_basket'][$i]['properties'], true)."')";
				}

				$db->query("
						INSERT INTO
						fw_orders_products
						(product_id,order_id,product_count, product_price, properties)
						VALUES ".implode(",",$rel_prod));

				if ($_SESSION['fw_basket'])
				{
					$products = array();
					foreach ($_SESSION['fw_basket'] as $key=>$val)
					{
						$products[$key]['details'] = $shop->getProductInfo($val['id']);
						$products[$key]['count'] = $val['number'];
						$products[$key]['price'] = $val['price'];
						$products[$key]['sum'] = number_format($val['price'] * $products[$key]['count'],2,'.','');
						$products[$key]['properties'] =  $val['properties'];
					}
					$smarty->assign("products",$products);
				}
				
				$_SESSION['fw_basket']=array();

				$smarty->assign("name",$name);
				$smarty->assign("site_url",BASE_URL);
				$smarty->assign("date",time());
				$smarty->assign("order_total",$total_price);
				$smarty->assign("order_id",$order_id);
				$smarty->assign("number",$total_number);
				$smarty->assign("order_price",$order_price);
				$smarty->assign("delivery",$delivery);
				$smarty->assign("user",$user);
				$smarty->assign("phone",$phone);
				$smarty->assign("address",$address);
				$smarty->assign("comment",$comment);
				$smarty->assign("email",$email);
				$smarty->assign("currency",DEFAULT_CURRENCY);

				if ($orderPromo)
				{
					$shop->setUserDataByPromo($code, preg_replace("/\D/","", $phone), $email, $order_id);
				}

		if (!empty($email))
		{
			$body=$smarty->fetch($templates_path.'/order_notice.txt');
			Mail::send_mail($email, MAIL_FROM,"Новый заказ в интернет магазине",$body,'','html','standard','Windows-1251');
		}


		$admin_body=$smarty->fetch($templates_path.'/admin_order_notice.txt');
		Mail::send_mail(SEND_ORDER_TO, MAIL_FROM,"Новый заказ в интернет магазине",$admin_body, $attach,'html','standard','WIndows-1251');

		header("Location: /catalog/basket/final/");
		die();
			}

		}
		else
		{
			header("Location: ".BASE_URL);
		}

	BREAK;
	
	CASE ((count($url)==2 && preg_match("/\?search_product=(.*)$/",$url[$n])) or (count($url)==2 && preg_match("/\?search_product=(.+)&page=([1-9]+)$/",$url[$n]))):

		
		$navigation[]=array("url" => 'search',"title" => 'Поиск');

		$search = filter_var(trim($_GET['search_product']), FILTER_SANITIZE_STRING);
		$search=urldecode($search);

		$current_url_pages[$n]=eregi_replace("&page=([1-9]+)","",$current_url_pages[$n]);

		if (isset($_GET['page']) && $_GET['page']!='') {
			$page=$_GET['page'];
		}
		else {
			$page=1;
		}

		$search_results=$db->get_all("
				SELECT fw_products.*,
				(SELECT id FROM fw_products_images i WHERE i.parent=fw_products.id ORDER BY sort_order ASC LIMIT 1) AS image,
				(SELECT ext FROM fw_products_images WHERE parent=fw_products.id ORDER BY insert_date DESC LIMIT 1) AS ext
				FROM fw_products
				WHERE (fw_products.name LIKE '%$search%' OR fw_products.article LIKE '%$search%') AND fw_products.status='1' ");

		if ($search_results)
		{
			foreach ($search_results as $key=>$val)
			{
				$search_results[$key]['full_url'] = $shop->getFullUrlProduct($search_results[$key]['id'], "catalog");
				$search_results[$key]['sizes'] = $shop->getProductPropertiesByEntity($search_results[$key]['id'], PROPERTY_ENTITY_SIZE);
			}
		}

		$smarty->assign("search_string",$search);
		$smarty->assign("products",$search_results);
		$smarty->assign("total_pages",$pager['total_pages']);
		$smarty->assign("current_page",$pager['current_page']);
		$smarty->assign("pages",$pager['pages']);

		$page_found=true;
		$template='shop.f_search.html';

	BREAK;

	DEFAULT:
		
		$cat_list=Common::get_nodes_list($cl);
		unset($url[0]);

				if (isset($type) && $type!='all'){
					$where =  " AND product_type=".$type." ";
					$smarty->assign("prod_type",$type);
				}
				else{
					$where = "";
				}

		$join = array();

		if (!empty($_REQUEST['price_start'])){
			$priceStart = (int) $_REQUEST['price_start'];
			$where .= " and (price >= '{$priceStart}') ";
		}

		if (!empty($_REQUEST['price_end'])){
			$priceEnd = (int) $_REQUEST['price_end'];
			$where .= " and (price <= '{$priceEnd}') ";
		}

		$filterPropertyIds = array();
		$filterPropertyValues = array();
		if (isset($_REQUEST['color']))
		{
			$colors = $_REQUEST['color'];
			$colors = array_filter($colors, function($value){
				return filter_var($value, FILTER_SANITIZE_STRING);
			});

			if (!empty($colors))
			{
				$join[] = " inner join properties pt_colors on p.id=pt_colors.product_id ";
				$wheres = array();
				foreach($colors as $color)
				{
					$wheres[] = "  (pt_colors.value='{$color}') ";
					$filterPropertyValues[] = "'{$color}'";
				}
				$filterPropertyIds[] = PROPERTY_COLOR_ID;
				$where .= " and ( " . implode(" or ", $wheres) . " ) ";

			}
		}

		if (isset($_REQUEST['size']))
		{
			$sizes = $_REQUEST['size'];
			$sizes = array_filter($sizes, function($value){
				return preg_match("/^([0-9]+)$/is", $value);
			});
			if (!empty($sizes))
			{
				$join[] = " inner join properties pt_sizes on p.id=pt_sizes.product_id ";
				$wheres = array();
				foreach($sizes as $size)
				{
					$wheres[] = "  (pt_sizes.value='{$size}') ";
					$filterPropertyValues[] = "'{$size}'";
				}

				$where .= " and ( " . implode(" or ", $wheres) . " ) ";
				$filterPropertyIds[] = PROPERTY_SIZE_ID;

			}
		}

		if (isset($_REQUEST['brand']))
		{
			$brands = $_REQUEST['brand'];
			if (!empty($brands))
			{
				$join[] = " inner join fw_products_properties pt_brands on p.id=pt_brands.product_id ";
				$wheres = array();
				foreach($brands as $brand)
				{
					$brand = iconv('utf-8', 'windows-1251', $brand);
					$brand = filter_var($brand, FILTER_SANITIZE_STRING);
					$wheres[] = "  (pt_brands.property_id=" . PROPERTY_BRAND_ID . " and pt_brands.value='{$brand}') ";
					$filterPropertyValues[] = "'{$brand}'";
				}

				$where .= " and ( " . implode(" or ", $wheres) . " ) ";
				$filterPropertyIds[] = PROPERTY_BRAND_ID;

			}
		}



		/*if (!empty($filterPropertyIds) && !empty($filterPropertyValues))
		{
			$where .= " and (pt.property_id in (".implode(",", $filterPropertyIds).") and pt.value in (".implode(",", $filterPropertyValues).")) ";
		}*/


		$order='ORDER BY p.sort_order ASC';
		if (!isset($page)) $page=1;
		$dirs=array("price"=>"desc","insert_date"=>"desc","name"=>"desc");

		if (isset($_GET['page']) or isset($_GET['order'])) {

			if (isset($_GET['page'])) $page=$_GET['page'];

			if (isset($_GET['sort']))
			{
				
				switch ($_GET['sort'])
				{
					case 'name':
						$order = "order by p.name ";
					break;
					case 'article':
						$order = "order by p.article ";
					break;
					case 'price':
						$order = "order by p.price ";
					break;
					
					default:
						$order = "order by p.sort_order ";
					break;

				}
				
				$smarty->assign('sort', $_GET['sort']);
				$smarty->assign('order', $_GET['order']);
				if ($_GET['order'] == 'asc')
				{
					$order .= " asc";
				}
				if ($_GET['order'] == 'desc')
				{
					$order .= " desc";
				}
				
			}
			
			unset($url[$n]);
			unset($current_url_pages[count($current_url_pages)-1]);
		}
		$smarty->assign("dir",$dirs);

		for ($f=0;$f<count($cat_list);$f++) {
			$url_to_check=implode("/",$url).'/';
			
			if ($cat_list[$f]['full_url']==$url_to_check) {
				
				$cat_content=$cat_list[$f];
				if (empty($cat_content['param_level'])){
					$categories = $shop->getCategories(1);
					if (!empty($categories[0]['id']))
					{
						$redirectUrl = $shop->getFullUrlCategory($categories[0]['id'], 'catalog');
						header("Location: " . BASE_URL . '/' . $redirectUrl);
						die;
					}
				}
				$page_found=true;

				if (isset($title_template)) $page_title=$title_template;
				else if ($cat_content['name']!='/') $page_title=$cat_content['name'];
				if ($cat_content['meta_keywords']!='') $meta_keywords=$cat_content['meta_keywords'];
				if ($cat_content['meta_description']!='') $meta_description=$cat_content['meta_description'];
				
				$text=$cat_content['text'];
				$smarty->assign("text",$text);
				$smarty->assign('cat_content', $cat_content);

		
		$cat_children_ids = array();
		for ($c=0;$c<count($cat_list);$c++) {
			//определяем дочернии категории каталога
			if ($cat_list[$c]['param_left']>$cat_content['param_left'] && $cat_list[$c]['param_right']<$cat_content['param_right'] && $cat_list[$c]['param_level']==($cat_content['param_level'])+1) 
			{
				$cat_children_ids[] = $cat_list[$c]['id'];
				if (isset($type))
				{
    				$item=array();
    				$item=$db->get_single("SELECT count(id) as count FROM fw_cats_types_relations WHERE cat_id='".(int)$cat_list[$c]['id']."' AND type_id='".$type."'");
    				if (intval($item['count'])>0)
    				{
    					$cat_list[$c]['parent_id'] = $cat_content['id'];
    					$folders_list[]=$cat_list[$c];
    				}
				}
				else
				{
					$cat_list[$c]['parent_id'] = $cat_content['id'];
					$folders_list[]=$cat_list[$c];
				}
			}
			//определяем родительскую категорию каталога
			if ($cat_list[$c]['param_left'] < $cat_content['param_left'] && $cat_list[$c]['param_right'] > $cat_content['param_right'] && $cat_list[$c]['param_level'] == $cat_content['param_level']-1)
			{
				$cat_parent_info = $cat_list[$c];
				$smarty->assign('cat_parent_info', $cat_parent_info);
			}
		}
		
		if (isset($folders_list)) {
          $done=0;
          for ($c=0;$c<count($folders_list);$c++) {

          	$folders_list[$c]['full_url'] = $shop->getFullUrlCategory($folders_list[$c]['id'], "catalog");
          	$folders_list[$c]['products'] = $shop->getProductsByCategory($folders_list[$c]['id']);
			
          	if (!$folders_list[$c]['image'])
          	{
          		$folders_list[$c]['picture'] = $shop->getImageProductByCategory($folders_list[$c]['id']);
          	}
          	
          	if (isset($folders_list[$c]['products']))
          	{
          		foreach ($folders_list[$c]['products'] as $key=>$val)
          		{
          			$folders_list[$c]['products'][$key]['full_url'] = $shop->getFullUrlProduct($folders_list[$c]['products'][$key]['id'], "catalog");
          		}
          	}

          }
          $smarty->assign("folders_list",$folders_list);
        }


				if (!empty($cat_content['id']))
				{

					$allBrands = $shop->get_catalog_properties((int) $cat_content['id'], PROPERTY_ENTITY_BRAND);
					if (!empty($allBrands[0])) {
						$smarty->assign('filter_brands', $allBrands[0]);
					}

					/*$allColors = $shop->get_catalog_properties((int) $cat_content['id'], PROPERTY_ENTITY_COLOR);
					if (!empty($allColors[0])) {
						$smarty->assign('filter_colors', $allColors[0]);
					}

					$allSizes = $shop->get_catalog_properties((int) $cat_content['id'], PROPERTY_ENTITY_SIZE);
					if (!empty($allSizes[0])) {
						$smarty->assign('filter_sizes', $allSizes[0]);
					}*/
					$allColors = $shop->getPropertiesByKey('color');
					$allSizes = $shop->getPropertiesByKey('size');

					$smarty->assign('filter_colors', $allColors);
					$smarty->assign('filter_sizes', $allSizes);

				}

				$joinStr = null;
				if (!empty($join)){
					$joinStr = implode(" ", $join);
				}

				$sql ="
				SELECT DISTINCT p.*,
						(SELECT id FROM fw_products_images i WHERE i.parent=p.id ORDER BY sort_order ASC LIMIT 1) AS image,
						(SELECT ext FROM fw_products_images WHERE parent=p.id ORDER BY insert_date DESC LIMIT 1) AS ext,
						(SELECT name FROM fw_products_types WHERE id=p.product_type LIMIT 0,1) AS type_name,
						(SELECT id FROM fw_products_types WHERE id=p.product_type LIMIT 0,1) AS type_id
					FROM fw_products AS p {$joinStr}
					WHERE
						p.parent='".$cat_content['id']."'
						AND
						p.status='1' $where
						order by p.sort_order ";

				$products_list=$db->get_all($sql);

				$parentId = (int) $cat_content['id'];
				$prices = $db->get_single("select min(price) as min, max(price) as max from fw_products where status='1' and parent='{$parentId}'");
				$smarty->assign('filterPrices', $prices);

				foreach ($products_list as $v => $key)
				{

						/*$tmp=explode("##|##",$key['properties']);
						$products_list[$v]['properties']=array();
						foreach ($tmp as $val => $k) {
							if (substr_count($k,"||#||")>0) {
								$tmp2=explode("||#||",$k);
								$products_list[$v]['properties'][]=$tmp2;
								if (substr_count($products_list[$v]['properties'][$val][3],"\n")>0) $products_list[$v]['properties'][$val][3]=explode("\n",$products_list[$v]['properties'][$val][3]);
							}
						}
					print_r($products_list[$v]['properties']);*/

						$products_list[$v]['full_url'] = $shop->getFullUrlProduct($products_list[$v]['id'], "catalog");
						$products_list[$v]['sizes'] = $shop->getProductProperties($products_list[$v]['id'], 'size', 0, 1);
				}
				
				$smarty->assign("products_list",$products_list);
				
				if ($cat_list[$f]['full_title']!='/') {
					$nav_titles=explode("/",$cat_list[$f]['full_title']);

					$nav_urls=explode("/",$cat_list[$f]['full_url']);
					unset($nav_titles[count($nav_titles)-1]);
					unset($nav_urls[count($nav_urls)-1]);
					for ($l=0;$l<count($nav_titles);$l++) {
						$navigation[]=array("url" => $nav_urls[$l],"title" => trim($nav_titles[$l]));
					}
				}
				
				
				$smarty->assign("cat_list",$cat_list);

				switch($cat_content['param_level'])
				{
					case 1:
						$template = "shop.f_catalog_1.html";
						break;
					case 2:
						$template = "shop.f_catalog_2.html";
						break;
					default:
						$template = "shop.f_catalog_0.html";
						break;
				}

				if ($responseJSON && $page_found)
				{
					$template = "shop.catalog_json.html";
					$template_mode='single';
					$switch_off_smarty = false;
				}

			}
		}

		if (!$page_found) {

			if (preg_match("/^([0-9]+)$/",$url[$n])) {

				$product_content = $shop->getProductInfo( intval($url[$n]) );
				
				if ($product_content['id']!='') {
					for ($f=0;$f<count($cat_list);$f++) {
						
						$url_to_check=implode("/",$url).'/';
						if ($cat_list[$f]['full_url']=='/') $cat_list[$f]['full_url']='';
						if ($cat_list[$f]['full_url'].$product_content['id'].'/'==$url_to_check && $product_content['parent']==$cat_list[$f]['id']) {
							
							
							foreach ($cat_list as $key=>$val)
							{
								if ($val['param_left'] < $cat_list[$f]['param_left'] && $val['param_right'] > $cat_list[$f]['param_right'] && $val['param_level'] == $cat_list[$f]['param_level']-1) {
								$cat_parent_info = $val;
								$smarty->assign('cat_parent_info', $cat_parent_info);
								$smarty->assign('cat_content', $cat_list[$f]);
								$cat_parent = $shop->getParent($cat_parent_info);
								$smarty->assign('parent', $cat_parent);
								}								
							}
							
							$page_found=true;
							
							if ($product_content['title']!='') $page_title=$product_content['title'];
							//if ($title_template) $page_title=$title_template;
							else $page_title= 'Продукция ' . $product_content['name'];

							if ($product_content['meta_keywords']!='') 
								$meta_keywords=$product_content['meta_keywords'];
							else
								$meta_keywords=$page_title;
							
							if ($product_content['meta_description']!='') 
								$meta_description=$product_content['meta_description'];
							else 
								$meta_description=$page_title;


							//$product_properties = $shop->get_product_properties($product_content['id']);
							$brand = $shop->getProductPropertiesByEntity($product_content['id'], PROPERTY_ENTITY_BRAND);
							if (!empty($brand[0])){
								$product_content['brand'] = $brand[0];
							}
							$colors = $shop->getProductPropertiesByEntity($product_content['id'], PROPERTY_ENTITY_COLOR);
							if (!empty($colors)){
								$product_content['colors'] = $colors;
							}
							$sizes = $shop->getProductPropertiesByEntity($product_content['id'], PROPERTY_ENTITY_SIZE);
							if (!empty($sizes)) {
								$product_content['sizes'] = $sizes;
							}

							$smarty->assign("product",$product_content);
							$smarty->assign("product_properties",$product_properties);
							$smarty->assign("properties",$shop->get_catalog_properties($product_content['parent']));

							$productProperties = $shop->getProductProperties($product_content['id'], 'size', '0', 1);
							$returnProperties = array(
								'sizes' => array(),
								'sizes_brand' => array(),
								'colors' => array()
							);
							if (!empty($productProperties))
							{
								foreach($productProperties as $key=>$property)
								{
									if (empty($returnProperties['sizes'][$property['value']])){
										$returnProperties['sizes'][$property['value']] = array();
									}

									$brandKey = null;
									if (!empty($property['size_brand'][0])){
										$brandKey = $property['size_brand'][0]['value'];
										$returnProperties['sizes_brand'][$brandKey] = array();
									} else {
										$brandKey = $property['value'];
										$returnProperties['sizes_brand'][$brandKey] = array();
									}

									if (!empty($property['colors']))
									{
										foreach($property['colors'] as $key2=>$color)
										{
											$color['value'] = strtolower($color['value']);
											if (!in_array($color['value'], $returnProperties['sizes'][$property['value']])){
												$returnProperties['sizes'][$property['value']][] = $color['value'];
												$returnProperties['sizes_brand'][$brandKey][] = $color['value'];
											}

											if (empty($returnProperties['colors'][$color['value']])){
												$returnProperties['colors'][$color['value']] = array();
											}

											if (!in_array($property['value'], $returnProperties['colors'][$color['value']])){
												$returnProperties['colors'][$color['value']][] = $property['value'];
											}

											//$returnProperties[$property['value']][] = (string)$color['value'];
										}
									}
								}

								$smarty->assign('propertiesJSON', json_encode($returnProperties, true));
								$smarty->assign('properties', $returnProperties);
							}

							$images = $shop->getProductImages($product_content['id']);
							$smarty->assign("images",$images);
							
							$photo = $db->get_single("SELECT * FROM fw_products_images WHERE parent='".$product_content['id']."' limit 1 ");
							$smarty->assign('photo', $photo);

							if (PRODUCT_RATING=='on' or PRODUCT_COMMENTS=='on') {
								$this_module=$db->get_single("SELECT priv FROM fw_modules WHERE name='shop' LIMIT 1");
								if (@$_SESSION['fw_user']['priv']<=$this_module['priv']) {
									$smarty->assign("show_admin_menu","true");
									$is_admin=true;
								}
								else $is_admin=false;

								if (isset($_SESSION['fw_user']['priv']) && $_SESSION['fw_user']['priv']<=9) {
									$smarty->assign("allowed_user",true);
								}
							}

							if (PRODUCT_RATING=='on') {

								$check_rating=explode(",",@$_COOKIE['fw_rating']);
								if (in_array($product_content['id'],$check_rating)) $smarty->assign("rating_done","true");

								$smarty->assign("rating","on");
							}

							if ($cat_list[$f]['full_title']!='/') {
								$nav_titles=explode("/",$cat_list[$f]['full_title']);
								$nav_urls=explode("/",$cat_list[$f]['full_url']);
								unset($nav_titles[count($nav_titles)-1]);
								unset($nav_urls[count($nav_urls)-1]);
								for ($l=0;$l<count($nav_titles);$l++) {
									$navigation[]=array("url" => $nav_urls[$l],"title" => trim($nav_titles[$l]));
								}
							}

							$navigation[]=array("url" => $product_content['id'],"title" => $product_content['name']);
							$navigation[]=array("url" => $product_content['id'],"title" => $product_content['name']);

							//unset($url[$n]);
							//print_r($navigation);
							$template='product_details.html';
						}
					}
				}
			}
		}
}



}



?>
