<?php
/****************************************************************************************************
*	Copyright (c) Death, 2016																		*
*	http://deathcoder.ru																			*
*	https://github.com/flightofdeath/secure_authentication											*
*																									*
*																									*
*	Создание объекта класса																			*
*	$user=new class_user();																			*
*	$user=new class_user($enter,$login,$hash_password,$remember_me);								*
*	$enter,$login,$hash_password,$remember_me - необязательные переменные, используются только		*
*												при аутентификации через форму						*
*																									*
*	$_SESSION['login']			-	логин в сессии													*
*	$_SESSION['random_number']	-	случайное число для защиты id юзера в куках						*
*	$_COOKIE['id']				-	зашифрованный id юзера случайным числом							*
*	$_COOKIE['hash']			-	информация о браузере для идентификации юзера, что это			*
*									именно его куки, а не созданные злоумышленником вручную			*
*	$_COOKIE['hash2']			-	хэш логина														*
*																									*
*	Методы:																							*
*	- decryption_or_encryption_id($id,$random_number,$action)										*
*	- enter_check_user($login,$hash_password,$remember_me)											*
*	- check_session($login,$random_number,$crypt_id,$hash,$hash2)									*
*	- check_remember_user($crypt_id,$hash,$hash2)													*
*	- get_id()																						*
*	- get_login()																					*
*	- get_permission($name=null)																	*
*	- action_before_entering($login)																*
*	- check_login($login)																			*
*	- check_hash_password($id_user,$hash_password)													*
*	- generate_keys()																				*
*	- decrypt_RSA($private_key,$hash_password)														*
*	- permission()																					*
*	- delete_cookies()																				*
*	- user_exit()																					*
****************************************************************************************************/

class class_user{
	
	private $use_RSA=true;					//Использовать RSA или нет, true or false
	private $id=0;							//id пользователя
	private $login='';						//Логин
	private $permission='guest';			//Название прав, по умолчанию гость
	private $permission_array=array(		//Права пользователя, может быть сколько угодно прав, для примера 3
		'read_text_1'=>1,
		'read_text_2'=>0,
		'read_text_3'=>0
	);
	private $time_remember=7776000;	//Сколько сохранять куки в секундах (90 дней)

	//Конструктор
	public function __construct($enter=false,$login='',$hash_password='',$remember_me=''){
		session_start();
		if($enter){
			//Проверяем данные, введённые через форму
			$this->enter_check_user($login,$hash_password,$remember_me);
		}
		else{
			if(isset($_SESSION['login']) && isset($_SESSION['random_number']) && isset($_COOKIE['id']) && isset($_COOKIE['hash']) && isset($_COOKIE['hash2'])){
				//Проверяем данные, если существуют куки и сессия для этого пользователя
				$this->check_session($_SESSION['login'],$_SESSION['random_number'],$_COOKIE['id'],$_COOKIE['hash'],$_COOKIE['hash2']);
			}
			else{
				if(isset($_COOKIE['id']) && isset($_COOKIE['hash']) && isset($_COOKIE['hash2'])){
					//Проверяем данные, если существуют только куки
					$this->check_remember_user($_COOKIE['id'],$_COOKIE['hash'],$_COOKIE['hash2']);
				}
			}
		}
	}
	
	//Метод для дешифрования или шифрования id юзера
	private function decryption_or_encryption_id($id,$random_number,$action){
		$random_number_array=str_split($random_number,2);
		$amount_number_of_array=count($random_number_array);
		if($amount_number_of_array==2){
			if($action=='decryption'){
				return ($id-$random_number_array[1])/$random_number_array[0];
			}
			else{
				return $id*$random_number_array[0]+$random_number_array[1];
			}
		}
	}

	//Метод для проверки входа по логину и хэшу пароля, при успешной проверке записываем данные в сессию и куки
	private function enter_check_user($login,$hash_password,$remember_me){
		global $db;
		$login=rs('/[^A-Za-z0-9]/u',$login,20);
		$id_user=$this->check_login($login);
		if($id_user!='0'){
			if(!$this->use_RSA){
				$hash_password=rs('/[^0-9a-z]/u',$hash_password,32);
			}
			else{
				$hash_password=rs('/[$\"\'\`\~\<\>\(\)\*\%\^\#\@\!]/u',$hash_password,172);
				//Достаём приватный ключ, для дешифровки хэша пароля
				$user_private_key_array=$db->select_query("
					SELECT
						`private_key`
					FROM
						`users`
					WHERE
						`id`='".$id_user."'
				");
				$hash_password=$this->decrypt_RSA($user_private_key_array[0]['private_key'],$hash_password);
				unset($user_private_key_array);
				//Удаляем приватный ключ, так как он больше не нужен
				$db->update_query("
					UPDATE
						`users`
					SET
						`private_key`=NULL
					WHERE
						`id`='".$id_user."'
				");
			}
			if($this->check_hash_password($id_user,$hash_password)){
				$random_number=mt_rand(10,99).mt_rand(10,99);
				$crypt_id=$this->decryption_or_encryption_id($id_user,$random_number,'encryption');
				$this->id=$id_user;
				$this->login=$login;
				$this->permission();
				$_SESSION['login']=$login;
				$_SESSION['random_number']=$random_number;
				if($remember_me!=''){
					//Если была поставлена галочка "запомнить меня", то записываем данные в БД и задаём время жизни кукам
					$hash=md5($_SERVER['HTTP_USER_AGENT']);
					setcookie('id',$crypt_id,time()+$this->time_remember);
					setcookie('hash',$hash,time()+$this->time_remember);
					setcookie('hash2',md5($login),time()+$this->time_remember);
					$db->insert_query("
						INSERT INTO
							`users_remember`(
								`id_user`,
								`id_crypt`,
								`hash`,
								`time`
							)
							values(
								'".$id_user."',
								'".$crypt_id."',
								'".$hash."',
								'".time()."'
							)
					");
				}
				else{
					setcookie('id',$crypt_id);
					setcookie('hash',md5($_SERVER['HTTP_USER_AGENT']));
					setcookie('hash2',md5($login));
				}
			}
		}
	}

	//Метод для проверки пользователя по сессии и кукам, при неудачной проверке куки удаляются
	private function check_session($login,$random_number,$crypt_id,$hash,$hash2){
		global $db;
		$login=rs('/[^A-Za-z0-9]/u',$login,20);
		$random_number=rs('/[^0-9]/u',$random_number,4);
		$crypt_id=rs('/[^0-9]/u',$crypt_id,10);
		$hash=rs('/[^0-9a-z]/u',$hash,32);
		$hash2=rs('/[^0-9a-z]/u',$hash2,32);
		if(!empty($login) && !empty($random_number) && !empty($crypt_id) && !empty($hash) && !empty($hash2)){
			if($hash==md5($_SERVER['HTTP_USER_AGENT'])){
				if($hash2==md5($login)){
					$id_user=$this->check_login($login);
					if($id_user!='0'){
						if($this->decryption_or_encryption_id($crypt_id,$random_number,'decryption')==$id_user){
							$this->id=$id_user;
							$this->login=$login;
							$this->permission();
						}
						else{
							$this->delete_cookies();
						}
					}
					else{
						$this->delete_cookies();
					}
				}
				else{
					$this->delete_cookies();
				}
			}
			else{
				$this->delete_cookies();
			}
		}
		else{
			$this->delete_cookies();
		}
	}

	//Метод для проверки пользователя по кукам, который поставил галочку "запомнить меня"
	private function check_remember_user($crypt_id,$hash,$hash2){
		global $db;
		$crypt_id=rs('/[^0-9]/u',$crypt_id,10);
		$hash=rs('/[^0-9a-z]/u',$hash,32);
		$hash2=rs('/[^0-9a-z]/u',$hash2,32);
		if(md5($_SERVER['HTTP_USER_AGENT'])==$hash){
			$user_remember_array=$db->select_query("
				SELECT
					`id`,
					`id_user`,
					`time`
				FROM
					`users_remember`
				WHERE
					`id_crypt`='".$crypt_id."' and
					`hash`='".$hash."'
			");
			if(is_array($user_remember_array)){
				if($user_remember_array[0]['time']<time()-$this->time_remember){
					//если запись устарела, то удаляем её
					$db->delete_query("
						DELETE FROM
							`users_remember`
						WHERE
							`id`='".$user_remember_array[0]['id']."'
					");
					$this->delete_cookies();
				}
				else{
					$user_login_array=$db->select_query("
						SELECT
							`login`
						FROM
							`users`
						WHERE
							`id`='".$user_remember_array[0]['id_user']."'
					");
					if($hash2==md5($user_login_array[0]['login'])){
						$random_number=mt_rand(10,99).mt_rand(10,99);
						$crypt_id=$this->decryption_or_encryption_id($user_remember_array[0]['id_user'],$random_number,'encryption');
						$db->update_query("
							UPDATE
								`users_remember`
							SET
								`id_crypt`='".$crypt_id."'
							WHERE
								`id`='".$user_remember_array[0]['id']."'
						");
						$this->id=$user_remember_array[0]['id_user'];
						$this->login=$user_login_array[0]['login'];
						$this->permission();
						$_SESSION['login']=$user_login_array[0]['login'];
						$_SESSION['random_number']=$random_number;
						setcookie('id',$crypt_id,time()+$this->time_remember);
					}
					else{
						//Удаляем куки, ибо хэш логина не совспал с хэшом логина из БД
						$this->delete_cookies();
					}
				}
			}
			else{
				//Удаляем куки, ибо по ним не нашли запись в БД, возможно срок уже вышел и из базы запись была удалена
				$this->delete_cookies();
			}
		}
		else{
			//Удаляем куки, ибо они не принадлежат владельцу
			$this->delete_cookies();
		}
	}

	//Метод для возвращения id пользователя
	public function get_id(){
		return $this->id;
	}

	//Метод для возвращения логина пользователя
	public function get_login(){
		return $this->login;
	}

	//Метод для возвращения разрешений пользователя
	public function get_permission($name=null){
		if(empty($name)){
			return $this->permission;
		}
		else{
			return $this->permission_array[$name];
		}
	}

	//Метод для проверки логина, формирования времени обращения и генерации публичного и приватного ключей перед отправкой формы
	public function action_before_entering($login){
		global $db;
		$login=rs('/[^A-Za-z0-9]/u',$login,20);
		$id_user=$this->check_login($login);
		if($id_user!='0'){
			$keys_array=array('','');		//пустой массив под открытый и закрытый ключи
			if($this->use_RSA){
				$keys_array=$this->generate_keys();
			}
			$time=time();
			$db->update_query("
				UPDATE
					`users`
				SET
					`time`='".$time."',
					`private_key`='".$keys_array[1]."'
				WHERE
					`id`='".$id_user."'
			");
			if($this->use_RSA){
				//Возвращаем время и публичный ключ
				return 'RSA;'.$time.';'.$keys_array[0];
			}
			else{
				//Возвращаем только время
				return 'not_RSA;'.$time;
			}
		}
		else{
			//Ошибка, пользователь не найден в БД по логину
			return 'error';
		}
	}

	//Метод для проверки логина
	private function check_login($login){
		global $db;
		$user_check=$db->select_query("
			SELECT
				`id`
			FROM
				`users`
			WHERE
				`login`='".$login."'
			LIMIT 1
		");
		if(is_array($user_check)){
			return $user_check[0]['id'];
		}
		else{
			return '0';
		}
	}

	//Метод для сверки хэшей паролей
	private function check_hash_password($id_user,$hash_password){
		global $db;
		$hash_and_time_array=$db->select_query("
			SELECT
				`hash`,
				`time`
			FROM
				`users`
			WHERE
				`id`='".$id_user."'
		");
		$db_user_hash=md5($hash_and_time_array[0]['hash'].$hash_and_time_array[0]['time']);
		if($db_user_hash==$hash_password){
			return true;
		}
		else{
			return false;
		}
	}

	//Метод для генерации приватного и публичного ключей
	private function generate_keys(){
		global $db;
		$config=openssl_pkey_new(
			array(
				'private_key_type'=>OPENSSL_KEYTYPE_RSA,
				'private_key_bits'=>1024
			)
		);
		$private_key='';
		openssl_pkey_export($config,$private_key);				//Приватный ключ
		$public_key_array=openssl_pkey_get_details($config);	//Публичный ключ
		return array($public_key_array['key'],$private_key);
	}

	//Метод для расшифровки текста, зашифрованного алгоритмом RSA
	private function decrypt_RSA($private_key,$hash_password){
		$private_key=openssl_get_privatekey($private_key);
		openssl_private_decrypt(base64_decode($hash_password),$result,$private_key);
		return $result;
	}

	//Метод для записи прав в переменные
	private function permission(){
		global $db;
		//Значенний может быть множество, для примера используются всего 3
		$permission_array=$db->select_query("
			SELECT
				`permission`.`name`,
				`permission`.`read_text_1`,
				`permission`.`read_text_2`,
				`permission`.`read_text_3`
			FROM
				`users`
					INNER JOIN `permission` ON `permission`.`id`=`users`.`id_permission`
			WHERE
				`users`.`id`='".$this->id."'
		");
		$this->permission=$permission_array[0]['name'];
		$this->permission_array['read_text_1']=$permission_array[0]['read_text_1'];
		$this->permission_array['read_text_2']=$permission_array[0]['read_text_2'];
		$this->permission_array['read_text_3']=$permission_array[0]['read_text_3'];
	}

	//Метод для удаления куки
	private function delete_cookies(){
		setcookie('id','');
		setcookie('hash','');
		setcookie('hash2','');
	}

	//Метод для выхода пользователя
	public function user_exit(){
		global $db;
		//Удаляем запись в БД, удаляем куки, очищаем переменные
		$crypt_id=rs('/[^0-9]/u',$_COOKIE['id'],10);
		$hash=rs('/[^0-9a-z]/u',$_COOKIE['hash'],32);
		$db->delete_query("
			DELETE FROM
				`users_remember`
			WHERE
				`id_user`='".$this->id."' and
				`id_crypt`='".$crypt_id."' and
				`hash`='".$hash."'
		");
		$this->delete_cookies();
		$this->id=0;
		$this->login='';
		$this->permission='guest';
		$this->permission_array['read_text_1']=1;
		$this->permission_array['read_text_2']=0;
		$this->permission_array['read_text_3']=0;
	}

	//Деструктор
	public function __destruct(){
	}
}
?>