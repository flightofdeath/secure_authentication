<?php
header("Content-type: text/html; charset=UTF-8");
require_once './classes/class_db.php';
require_once './includes/function/db.php';
require_once './includes/function/function.php';
require_once './classes/class_user.php';
if(isset($_POST['login'])){
	$user=new class_user(true,$_POST['login'],$_POST['password'],$_POST['remember_me']);
	if($user->get_id()=='0'){
		echo 'Вы ввели не правильно логин или пароль';
	}
}
else{
	$user=new class_user();
}
if(isset($_POST['user_exit'])){
	$user->user_exit();
}
echo '<!DOCTYPE HTML>
<html>
<head>
<meta http-equiv="content-type" content="text/html;charset=UTF-8">
<title>Безопасная аутентификация без использования https</title>
<script type="text/javascript" language="javascript" src="./scripts/md5.js"></script>
<script type="text/javascript" language="javascript" src="./scripts/jsencrypt.min.js"></script>
<script type="text/javascript" language="javascript" src="./scripts/script.js"></script>
</head>
<body>
<H1>Безопасная аутентификация без использования https</H1>
<div id="form_auth">';
if($user->get_permission()=='guest'){
	echo '<form action="./" method="post" onsubmit="auth(this);return false;"><p id="message_error"></p>
	<p>Логин: <input type="text" value="" id="login" name="login" maxlength="20"></p>
	<p>Пароль: <input type="password" value="" id="password" name="password" maxlength="30"></p>
	<p><input type="checkbox" id="remember_me" name="remember_me"> запомнить меня</p>
	<input type="submit" value="Войти"></form>';
}
else{
	echo $user->get_id(),' ',$user->get_permission(),' ',$user->get_login();
	echo '<form action="./" method="post"><input type="submit" name="user_exit" value="Выход"></form>';
}
if($user->get_permission('read_text_1')){
	echo '<p>text1</p>';
}
if($user->get_permission('read_text_2')){
	echo '<p>text2</p>';
}
if($user->get_permission('read_text_2')){
	echo '<p>text3</p>';
}
echo '</div>
</body>
</html>';
unset($db);
?>