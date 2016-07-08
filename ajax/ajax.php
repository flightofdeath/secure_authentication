<?php
require_once '../includes/function/function.php';
require_once '../classes/class_db.php';
require_once '../classes/class_user.php';
require_once '../includes/function/db.php';
if(isset($_POST['section'])){
	$section=rs('/[^a-z\_]/u',$_POST['section'],15);
	switch($section){
		case 'check_login':
			if(isset($_POST['login'])){
				$login=rs('/[^A-Za-z0-9]/u',$_POST['login'],20);
				$user=new class_user();
				echo $user->action_before_entering($login);
			}
		break;
	}
	unset($section);
}
?>