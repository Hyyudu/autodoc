<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Хыиуду
 * Date: 12.11.13
 * Time: 15:20
 * To change this template use File | Settings | File Templates.
 */
header('Content-Type: text/html; charset=utf-8');
if (!isset($_POST['codes']))   {
?>
<body bgcolor=white>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<form action='input_codes.php' method=POST>
	Пожалуйста, введите в эту строку трехбуквенные коды с ваших активных карточек (например, WSS, TKA).
	Все коды вводятся латиницей, разделяются пробелами и/или запятыми.
	Если на вашей карточке несколько кодов есть с цифрами (например, IVX0, IVX2, IPA1, IPA2, IPB1),
	то вводите трехбуквенные коды с максимальными числами (в этом примере IVX2, IPA2, IPB1).<br>
	Ваши коды: <input name=codes size=200><br>
	<input type=submit value="Отправить данные">

</form>
</body>
<?}
else    {
	header('Refresh: 5');
	echo "Ваши данные записаны. Отправляйтесь на операционный стол. Ни пуха, ни пера!";
	file_put_contents('codes.txt', $_POST['codes']);
}
?>