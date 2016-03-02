<?php /* Smarty version 2.6.11, created on 2016-02-29 18:51:01
         compiled from index.html */ ?>
<?php require_once(SMARTY_CORE_DIR . 'core.load_plugins.php');
smarty_core_load_plugins(array('plugins' => array(array('function', 'fetch', 'index.html', 36, false),array('function', 'eval', 'index.html', 38, false),)), $this); ?>
<!DOCTYPE html>
<html lang="ru">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="shortcut icon" href="/favicon.png" type="image/x-icon">
    <title><?php echo $this->_tpl_vars['page_title']; ?>
</title>
    <meta name="Keywords" content="<?php echo $this->_tpl_vars['meta_keywords']; ?>
">
    <meta name="Description" content="<?php echo $this->_tpl_vars['meta_description']; ?>
">
    <link href="/templates/css/styles.css" rel="stylesheet">
    <!--[if lt IE 9]>
      <script src="/templates/js/libs/html5shiv.min.js"></script>
      <script src="/templates/js/libs/respond.min.js"></script>
    <![endif]-->
  </head>
<body>

<?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => (@TPL_PATH)."blocks/top_auth.html", 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
  $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => (@TPL_PATH)."blocks/top_header.html", 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?>

<div class="container">

  <?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => (@TPL_PATH)."blocks/top_search.html", 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?>

  <?php if ($this->_tpl_vars['current_url'] != 'home'): ?>
    <?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => (@TPL_PATH)."blocks/breadcrumbs.html", 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?>
  <?php endif; ?>

  
  <?php if ($this->_tpl_vars['content'] == '' || ! $this->_tpl_vars['content']): ?>
  <?php $this->assign('content', ""); ?>
  <?php echo smarty_function_fetch(array('file' => $this->_tpl_vars['template'],'assign' => 'content'), $this);?>

  <?php endif; ?>
  <?php echo smarty_function_eval(array('var' => $this->_tpl_vars['content']), $this);?>



</div>

<?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => (@TPL_PATH)."blocks/footer.html", 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?>
<script src="/templates/js/libs/jquery.min.js"></script>
<script src="/templates/js/scripts.js"></script>
</body>
</html>