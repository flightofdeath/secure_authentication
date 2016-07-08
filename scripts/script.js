function auth(auth_form){
	document.getElementById('message_error').innerHTML='';
	if(auth_form.login.value!='' && auth_form.password.value!=''){
		if(window.XMLHttpRequest){
			xmlhttp=new XMLHttpRequest();
		}
		else{
			xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
		}
		xmlhttp.onreadystatechange=function(){
			if(xmlhttp.readyState==4 && xmlhttp.status==200){
				var result=xmlhttp.responseText;
				if(result!='error'){
					var result_array=result.split(';');
					var hash=hex_md5(hex_md5(auth_form.password.value)+result_array[1]);
					if(result_array[0]=='RSA'){
						var crypt=new JSEncrypt();
						crypt.setPublicKey(result_array[2]);
						hash=crypt.encrypt(hash);
					}
					auth_form.password.value=hash;
					auth_form.submit();
				}
				else{
					document.getElementById('message_error').innerHTML='Такого логина или пароля не существует';
				}
			}
		}
		xmlhttp.open("POST","./ajax/ajax.php",true);
		xmlhttp.setRequestHeader("Content-type","application/x-www-form-urlencoded;charset=UTF-8");
		xmlhttp.send('section=check_login&login='+auth_form.login.value);
	}
	else{
		document.getElementById('message_error').innerHTML='Поля не могут быть пустыми';
	}
}