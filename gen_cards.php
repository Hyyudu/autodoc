<?php
/**
 * Created by JetBrains PhpStorm.
 * User: ������
 * Date: 11.11.13
 * Time: 16:08
 * To change this template use File | Settings | File Templates.
 */
/*
$rads = Array(
	'�� ���������� ������ ��������������. ��� �������������� ��� ������ �� ��������� ������ ���������. ���: SMD',
	'� ���� �������� ������ � ���� ������ ������������. ��������� ������ �������. ���: UIS',
	'���� �������, ��� ��� � �������, ���� �� �� ��������� ��������� - ����� �������� ������ ��������� ������, ���� ����� ���������. ���: PLK',
	'� ���� �������� �������� ����. ����� ������ ������� ���� �� �����. ���: REK',
	'� ���� ����� ������ �������� ������ ������. �� �� ������ ����� ������, ���� �������������. ���: QUE',
	'������ ��������, �� ������� � �������. ����� ���� ������ ��������� � ����, �� ��������� ������������� ��� ������������ ��������� �� ��� �� ������. ���: FOD',
	'� ������ ������������ �������, �� ��������� ������������, ������ �������� ������. ����� �� ������� � ���� ���������� ������ ������������. ���: WBR',
	'�������� ���������� �������� ����, �������������� ������������� ������. ���� �������� ����������, �� ����� ������� �� ���� �� �� ������� ��������. ���� ��������� �������, ��� ���-�� ������. ���: DBR',
	'�� ����������, ��� � ���� ���������� ���������� ���. ���������� ��� ������������ ����� �������. �� �������, ��������� ������� ���������� ���-�� ������, �� ��������� � ������. ���: WNU',
	'���������� �������� ���� ������ ���� ����� �����������. ����� ����� ���� ������ ����� �����, ������� ���������� � �������. ���� ������������ ������. ���: DNU',
	'���� ���� ����� �������� ��������� ��������. �� ��������� � ����, �� ���� ������ �� ��������� ���� � �� �� ������ ������������� ��������������. �������� ����������� ������ 10 �����. ���: DNU',
	'� ���� ��������� ������� �����������. �� ������������ �������� ��� ����������. ������� ������ �������� �� �������� - �� �������� � ��. ��� ����� ������� ������� ��� �������� �� ������� � ������� �� ��������� ������. ���: FBR',
);

$hypo = Array(
	'�� ����� � ������� ������, ��������� �������. ���: MSQ',
	'� ���� ���������� ������������. ���� ������ ������ ���������. ���: HXL',
	'�� ���������� ������� ����������. ���� ������ ����� ���������. ���: OLJ',
	'�� ���������� �� ��� ������� ���������, ��� ������. ���� ������� ������ ������. ���: MDF',
	'� ���� �������� ������ ������. ����� ���� � ������� ����� ����� ����������. ���: LSR',
	'�� ���������� �� ��� ������� ��������, ��� ������. ���� ����� ������ ����� �������������� �� ���-����, � ���� �������� �������� � ������������� ��������. ���: AVM',
	'�� ����� � ������� ������, �������� ������, ����� ������ ��-��� ���. ��������� �� ���������� ��������, ��� �� ����������. ���: DBT',
	'�� ������� �������� ���� � ���� �������� ������������� ����. ���� ������ ������ � ������ ��������. ���: DBR',
	'� ���� ������� �������������� � ������������� � ������������. ���� ����� ������ ������, ��������� �� ��������� ����������. ���: WBT',
	'�� ��������� ��������������� � ������������. �� �� ������ �� �� ��� ����� �����������������. ���� ������ ������������� ��� ����������� ������. ���: DNU',
	'� ���� ������ ����� ������. ������ ��������� ����� �� ������� ��������. ���: DSS',
	'������ ��������� ����� �� ������� ��������. �� �� ���������, ��� �� ����������. ������������� ��� ����������� ������ �� ����������. ���: FNU',
	'���� ������ ������. � ���� ���������� ������������, ��������� ����� ������� �������. ���: WBR',
);

$trauma = Array(
	'�� ������ ���� ������� ����� ������ ����. ������ ���������, �� ������� ���-������ � ���� ����� ������. ���: NKS',
	'�� ��������� ����. �������������� ��������, ��������. ���: LDO',
	'������� ����� �� ���. ���� ���������� ���-������, � �� ����� ����� �� �����������. ���: CBZ',
	'����� �� ���� ����, �� ��������������. ��� �����, ������ ����� �������. ���: NDL',
	'������ �������� �������� ����. ������������ ����� ����� ������ �� ����������. ���: ESL',
	'� ���� ������������ ������� � ������. ���������� ������� � �������� ����. ���: YBR',
	'������� ������� ���� � ����� ��� ������ �����. ������� �������, �������. �� ��������, ������ ���������� �� �������. ���: YLU',
	'����� ���������� ����� � ���� ������ ����� �������. ����� �� ������� ���� �������� ������� ������. ���: YST',
	'������� ����. ���������� ���� ��� ����� � ��� �������������. ���� ���� ���-�� �������������, �� ������ ������� �����. ���: YOD',
	'������� ����. �� �� ������ ������ � ����� �������� �������� ���������� ����. ���: XOD',
	'� ������ �������, � ������ �� ��������� ������� ����������� ������. ���: XNU',
	'������� ������������ �� ���� �� �������������� ����������. ��� ��������� �������� � ����������� ������. ���� ���������� - ���������� ���������� �������� � ������������� �������������� ������ ������ �������. ���: XSS',
);

for ($i=0; $i<200; $i++)    {
	$r = $rads[rand(0, count($rads)-1)];
	$h = $hypo[rand(0, count($hypo)-1)];
	$t = $trauma[rand(0, count($trauma)-1)];
$temp = "../images/backs/margin1.png
---
../images/backs/back1.png
===
../images/fronts/skull.png
---
������-�����!
===
{i}../images/icons/checkbox.png{/i} ��������
---
$r
---
{i}../images/icons/checkbox.png{/i} ��������
---
$h
---
{i}../images/icons/checkbox.png{/i} ��������
---
$t
===";
$url = 'trauma/trauma'.$i.'.txt';
file_put_contents($url, iconv('UTF-8', 'Windows-1251', $temp));
echo $url."\t1<br>";
*/

$conds = "Б=2
Беспомощность = 3
Беспомощность = 4
Беспомощность = 5
Беспомощность > 2
Беспомощность > 3
Беспомощность > 4
Беспомощность < 3
Беспомощность < 4
Беспомощность < 5
Жестокость = 2
Жестокость = 3
Жестокость = 4
Жестокость = 5
Жестокость > 2
Жестокость > 3
Жестокость > 4
Жестокость < 3
Жестокость < 4
Жестокость < 5
Эго = 2
Эго = 3
Эго = 4
Эго = 5
Эго > 2
Эго > 3
Эго > 4
Эго < 3
Эго < 4
Эго < 5";

$panic = "(возьми одну травмокарту. Отыгрывай с неё любой эффект по выбору. Чёрт знает, где ты его подхватил)
(твоё последнее воспоминание произошло не с тобой, а вон с тем человеком. Ну надо же!)
Где мои близкие?! Кто-то здесь убил моих близких!
За мной кто-то наблюдает. На корабль проник шпион. Я точно помню. Странно, что до сих пор ничего не было об этом известно. Это как-то связано с моей профессией. Это чувство долгого и упорного взгляда в спину... Нельзя оборачиваться. Нельзя!
Я вспомнил. Я - шпион. Абсолютно точно, чёрт побери! Я проник сюда, чтобы предотвратить катастрофу. Корабль обречён. Это не просто частный рейс. Они здесь везут что-то очень страшное, крайне страшное. Поэтому я здесь. Я здесь, чтобы это остановить. Любой ценой!
Самые близкие люди врали мне. Никому нельзя доверять. Никому нельзя доверять. Никому нельзя доверять. Никому нельзя доверять. Никому нельзя…
Все люди в крио-капсулах мертвы. Нужно их всех обесточить и не тратить энергию. А может, я их и убил? А может, они все были больные?!
Нельзя открывать шлюз! Никак нельзя! Все приборы врут! Кто знает, где мы на самом деле находимся? Кто знает, что нас ждёт снаружи?!  А вдруг мы на самом деле под водой?
Что это было? Что-то произошло в соседнем отсеке! Я же слышал! Кто-нибудь ещё слышал? Надо проверить! ...А теперь в коридоре! Там явно какие-то проблемы. Почему постоянно что-то происходит? Я даже знаю, что я слышал. Сейчас скажу.
В соседнем отсеке что-то происходит. И не просто \"что-то\". Нет-нет, там не проблемы. Там действительно сговор! Против нас! Друзья, они все сговариваются у нас за спиной!
Один человек из тех, что вокруг - иллюзия. Да-да, совершенно точно, он не настоящий. Галлюцинация. Если его игнорировать - он исчезнет. Пусть он исчезнет!
Голоса говорят со мной. Они мешают! Возможно, это Бездна? Да как эти чёртовы безднопоклонники это выносят?! Да они настоящие психи!
Я слышу голоса, и они советуют мудрые вещи. Да-да. Это так странно. К сожалению, они говорят очень тихо, и окружающие люди мешают их слышать. Почему они не могут немного помолчать? Это же реально важные сведения!
Этот человек не тот, за кого себя выдаёт. Я знаю точно, что он - это не он. Не знаю, зачем ему это надо, но надоследить за ним.
Я буду таскать с собой то, что можно использовать как оружие. И не буду сидеть спиной к двери. Просто так. Я же не знаю всех этих людей.
Я слежу за всеми вами. За всееми. Обо всём происходящем надо докладывать капитану, корпорату или любому облечённому властью человеку.
По кораблю мелькают неясные тени. Вон, вон одна, скрылась за углом! Вы что, не видите?! На корабле монстры! Мутанты! Я никак не могу их достать. Я вижу, как они проносятся за дверью, но когда выхожу - там уже никого. Куда они деваются? Забираются в вентиляцию?
На самом деле я уже умер. Я мёртв. Это так. Что мне с этим делать?
Кто бы с кем ни дрался, ни ломал оборудование, ни кричал - всё нормально. Наконец-то эти люди ведут себя как надо, цивилизованно. Любо-дорого глядеть.
Мне было Откровение. И теперь я должен донести свет Истины до окружающих. Они ведь ещё не знают...";
$conds = explode("\n", $conds);
$panic = explode("\n", $panic);

$sample = file_get_contents('pandorum_sample.txt');

for ($i=0; $i<112; $i++)    {
	shuffle($conds);
	shuffle($panic);
	$text = $sample;
	for ($j=1; $j<=4; $j++) {
		$text = str_replace("{open$j}", $conds[$j], $text);
		$text = str_replace("{strip$j}", $panic[$j], $text);
	}
	$text = iconv('UTF-8', 'Windows-1251', $text);
	$url = 'pandorum/pandorum'.$i.'.txt';
	file_put_contents($url, $text);
	echo $url." 1<br>";
}
