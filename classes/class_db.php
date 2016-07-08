<?php
/********************************************************************
*	Copyright (c) Death, 2016										*
*	https://github.com/flightofdeath/class_db						*
*																	*
*						Класс для работы с БД						*
*																	*
*	В конструктор передаём данные:									*
*	- имя или ip сервера ($server)									*
*	- название БД ($db)												*
*	- имя пользователя ($user)										*
*	- пароль пользователя ($password)								*
*	- полные ошибки или нет ($view_full_error) - не обязательно		*
*																	*
*	Создание объекта класса:										*
*	$db=new class_db($server,$db,$user,$password,$view_full_error);	*
*																	*
*	Уничтожение класса:												*
*	unset($db);														*
*																	*
*	Методы:															*
*	-select_query													*
*	-amount_row														*
*	-insert_query													*
*	-update_query													*
*	-last_insert_id													*
*	-error															*
*																	*
********************************************************************/

class class_db{

	private $connect;			//Переменная где хранится информация о подключении к БД
	private $result;			//Переменная с результатом выполненого запроса через метод select_query
	private $view_full_error;	//Показывать полные ошибки или нет, по умолчанию false

	//Конструктор
	public function __construct($server,$db,$user,$password,$view_full_error=false){
		$this->view_full_error=$view_full_error;
		$this->connect=new mysqli($server,$user,$password,$db);	//Подключаемся
		!$this->connect->connect_errno ? $this->connect->set_charset('UTF8') : $this->error($this->connect->connect_errno);	//Если всё нормально, то задаём кодировку, если нет, то выводим ошибку.
	}

	//Метод выполнения запроса с select'ом
	public function select_query($query){
		$this->connect->real_query($query);				//Выполняем запрос
		if(!$this->connect->errno){
			$this->result=$this->connect->use_result();	//Готовим к выводу
			$array_all='';								//Создаём пустую переменную
			while($row=$this->result->fetch_assoc()){
				$array_all[]=$row;
			}
			$this->result->free();
			return $array_all;							//Возвращаем ассоциативный массив, либо пустую переменную
		}
		else{
			$this->error($this->connect->errno);		//Выводим ошибку, если запрос некорректный
		}
	}

	//Метод для получения количества записей в запросе
	public function amount_row($query){
		$this->result=$this->connect->query($query);
		$amount_row=$this->result->num_rows;
		$this->result->free();
		return $amount_row;
	}

	//Метод для вставки записи в БД
	public function insert_query($query){
		$this->connect->real_query($query);			//Выполняем запрос
		if($this->connect->errno){
			$this->error($this->connect->errno);	//Выводим ошибку, если запрос некорректный
		}
	}

	//Метод для обновления записи в БД
	public function update_query($query){
		$this->insert_query($query);				//Используем для обновления метод insert_query
	}

	//Метод для удаления записи в БД
	public function delete_query($query){
		$this->insert_query($query);				//Используем для удаления метод insert_query
	}

	//Метод для получения последнего добавленного id после выполнения метода insert_query
	public function last_insert_id(){
		return $this->connect->insert_id;			//Возвращаем ID последней записи
	}
	
	//Метод для вывода ошибок
	private function error($error){
		if($this->view_full_error){
			if($error=='2002' || $error=='1049' || $error=='1044' || $error=='1045'){
				trigger_error($error.' #'.$this->connect->connect_error,E_USER_WARNING);	//Эти ошибки связаны с подключением к БД
			}
			else{
				trigger_error($error.' #'.$this->connect->error,E_USER_WARNING);			//Все остальные ошибки связанные с выполнением запросов
			}
		}
		switch($error){
			case '1136':
				echo 'Количество добавляемых столбцов не совпадает с количеством добавляемых значений.';
			break;
			case '1044':
			case '1045':
				echo 'Доступ к базе данных запрещён.';
			break;
			case '1049':
				echo 'Нет соединения с базой данных.';
			break;
			case '1052':
				echo 'Выбираемая колонка не однозначна.';
			break;
			case '1054':
				echo 'Ошибка в запросе, неизвестное поле.';
			break;
			case '1064':
				echo 'Ошибка в синтаксисе запроса.';
			break;
			case '1146':
				echo 'Ошибка в запросе, неизвестная таблица.';
			break;
			case '2002':
				echo 'Нет соединения с сервером баз данных.';
			break;
			default:
				echo 'Неизвестная ошибка базы данных.';
			break;
		}
		exit('Дальнейшая работа данной страницы невозможна, обратитесь к администратору сайта.');	//Останавливаем выполнение скрипта
	}

	//Деструктор
	public function __destruct(){
		!$this->connect->connect_errno ? $this->connect->close() : false;	//Закрываем соединение с БД при вызове деструктора, если к БД подключились без проблем, иначе ничего не делаем
	}

}
?>