<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Хыиуду
 * Date: 25.07.13
 * Time: 18:42
 * To change this template use File | Settings | File Templates.
 */

ini_set('error_reporting', E_ALL ^ E_NOTICE);
error_reporting(E_ALL ^ E_NOTICE);
require_once('../aura/functions.inc.php');
require_once('cAutodoc.php');
require_once('cVector.php');
//die('123');

// Флаг, заставляющий все инструменты работать 5 секунд
$ACCELERATION = 0;
// Флаг - будут ли примерные значение в зависимости от кровопотерь
$ESTIMATED = 1;

// Адрес скрипта с инфой о пациенте
$diagnoz_url = "http://".$_SERVER['HTTP_HOST'].preg_replace('~(.*\/)[^\/]+~', '$1', $_SERVER['PHP_SELF']).'get_problems.php';

if (!isset($SESS_ID))
	$SESS_ID = 1;
session_id($SESS_ID);

session_start();

$curr_time = microtime(1);
if ($_SESSION['prev_call'])
	$time_spent = $curr_time - $_SESSION['prev_call'];
$_SESSION['prev_call'] = $curr_time;
$_SESSION['messages'] = array();


// Что происходит, когда применили неправильный инструмент
function wrong_tool_used($tool, $target = '') {
	switch ($tool)  {
		case 'press':
			change_system_stress($target, 100);
			break;
		case 'burn':
			change_system_stress($target, 100);
			break;
		case 'shock':
			change_system_stress($target, 300);
			break;
	}
}

// Зависимость скорости кровопотери от температуры, давления, пульса
function bloodloss_quotient()   {
	$diag = $_SESSION['diag'];
	$blood = $_SESSION['blood'];
	if ($diag['temp']<=36.6)
		$qt= 1;
	else
		$qt=exp(-($diag['temp']-36.6)/13);
	$AD = bound($diag['AD'], 60, 160, 'fit');
	$qAD= $AD/120;
	$qp = $diag['pulse']/60;
	$qbl = 1/((5000 - $blood['left'])/1000+1);
	return $qAD*$qp*$qt*$qbl;
}

/**
 * Меняет цвет системы в сторону указанного. $new_color - либо массив, либо 'crit', либо 'heal'
 * @param $system_id
 * @param $new_color
 */
function change_system_color($system_id, $value, $new_color, $descr = '')    {
//	Отсечка на случай, если попытаются менять цвет/стресс главного разреза
	if (!array_key_exists($system_id, Autodoc::$systems))
		return;

//	Отказавшее - не перекрашивается, остается в крите!
	if ($_SESSION['systems'][$system_id]['stress'] >=1000)
		return;

//	Проверяем - а не заморожен ли орган?
	$freeze_time = $_SESSION['effects'][$system_id]['freeze'];
	if ($freeze_time>0) { // Есть заморозка
		if (microtime(1) > $freeze_time)  // заморозка уже прошла
			unset($_SESSION['effects'][$system_id]['freeze']);
		else    // Еще не прошла - изменение цвета не работает
			return;
	}



	$cur_col = $_SESSION['systems'][$system_id]['color'];
	$crit_col = $_SESSION['crit_color'];
	if ($new_color == 'heal')
		$direction = Vect::diffVector($crit_col, $cur_col);
	elseif ($new_color == 'crit')
		$direction = Vect::diffVector($cur_col, $crit_col);
	else
		$direction = Vect::diffVector($cur_col, $new_color);
	$changes = Vect::normalizeVector($direction, $value);
	if ($new_color == 'heal')   {
		$out_col = Vect::addVector($cur_col, $changes);

	}
	else {
		if ($new_color == 'crit')
			$target_col = $crit_col;
		else
			$target_col = $new_color;
		$out_col = Array(0,0,0);
		for ($i=0; $i<3; $i++)
			if (abs($cur_col[$i] - $target_col[$i])<abs($changes[$i]))
				$out_col[$i]=$target_col[$i];
			else
				$out_col[$i] = $cur_col[$i]+$changes[$i];
	}
	$_SESSION['systems'][$system_id]['color'] = Vect::boundVector($out_col,0,100,'fit');
	$value = round($value, 2);
//	if ($descr)
//		Autodoc::message("Меняем цвет системы $system_id на $value в направлении ".(is_array($new_color)? "(".join(', ', $new_color).')': $new_color)
//	.($descr ? ' - '.$descr : ''), 0,0);
}

function change_system_stress($system_id, $value, $descr = '')   {
//	Отсечка на случай, если попытаются менять цвет/стресс главного разреза
	if (!array_key_exists($system_id, Autodoc::$systems))
		return;

//	При отказавшем органе - не роляет!
	if ($_SESSION['systems'][$system_id]['stress'] == 1000)
		return;

	boundAdd($_SESSION['systems'][$system_id]['stress'], $value, 0, 1000);
	if ($_SESSION['systems'][$system_id]['stress'] == 1000) // Орган/система отказали - ставим крит.цвет
		$_SESSION['systems'][$system_id]['color'] = $_SESSION['crit_color'];
//	$value = round($value, 2);
//	$_SESSION['messages'][] = "Меняем стресс системы $system_id на $value".($descr ? ' - '.$descr : '');
}

function kill_patient($msg) {
   // Memento mori
	global $data;
	Autodoc::message($msg,1,1);
	$data['death']=1;
}

function start_operation()  {

	global $diagnoz_url;
	global $curr_time;
	//	Начать операцию
	foreach ($_SESSION as $key=>$value) unset ($_SESSION[$key]);
//	Чистим всю сессию
	$_SESSION['operation_start'] = $curr_time;
	foreach (Autodoc::$tools as $tool => $tooldata)
		if ($tooldata['is_switchable'])
			$_SESSION['active_tools'][$tool] = false;

	$data = json_decode(file_get_contents($diagnoz_url),true);

//	Определяем диагностические показатели
	$_SESSION['diag'] = $data['diag'];
	if (isset($data['diag']['pulse']))
		$_SESSION['diag']['pulse'] = $data['diag']['pulse'];
	else
		$_SESSION['diag']['pulse'] = Autodoc::getPulse(floatval($data['diag']['AD']),  floatval($data['diag']['temp']));
	$_SESSION['diag']['AD_gap'] = 1.5;

//	Определяем критический цвет болезней
	$crit_col_arr = array();
	if ($data['diseases'])  {
		foreach ($data['diseases'] as &$disease) {
			foreach ($disease['systems'] as $system_name => $power)
				$disease['power'] += $power;
			$crit_col_arr[]=Array($disease['color'], $disease['power']);
		}
		$_SESSION['crit_color'] = Vect::mean_color($crit_col_arr);
	}
	else{
		$_SESSION['crit_color'] = Array(0,0,0);
	}
	$_SESSION['html_crit_color'] = rgb2html100($_SESSION['crit_color']);

//	Определяем состояние систем и органов
	foreach (Autodoc::$systems as $system_name => $asystem)  {
		$system = $asystem;
		$system['stress'] = intval($data['state'][$system_name]);
		if ($system['stress'] >= 1000)  {
			$system['stress'] = 1000;
			$system['color'] = $_SESSION['crit_color'];
		}
		else    {   // Система не отказала
//		Определяем цвет системы на момент начала операции. Равен средневзвешенному цвету от дефолтного цвета системы
//      и всех цветов болезней, которые завязаны на эту систеу
			$cols = Array();
			$cols[]=Array($asystem['color'], 1000);
			if ($data['diseases'])
			foreach ($data['diseases'] as &$disease)
				if (in_array($system_name, $disease['systems']))
					$cols[] = Array($disease['color'], $data['state'][$system_name]);
			$system['color'] = Vect::mean_color($cols);
		}
		$system['html_color'] = rgb2html100($system['color']);
		$_SESSION['systems'][$system_name] = $system;
	}

	$_SESSION['used_tracks'] = array();

//	Определяем начальные кровопотери
	$_SESSION['blood'] = Array(
		'left' => 5000 - $data['bloodloss'], // Осталось в организме - изначально 5 литров
		'lost' => intval($data['bloodloss']),    // Потеряно, но еще не откачано,
	);
	/*
	$maxlen=0;
	foreach ($data['diseases'] as $disease)
		if ($maxlen < $disease['time_elapsed'])
			$maxlen = $disease['time_elapsed'];
	$_SESSION['blood']['lost'] = 1800*log($maxlen/600 + 1);
	$_SESSION['blood']['left'] -= $_SESSION['blood']['lost'];
	*/

//	Записываем стартовое состояние
	$_SESSION['start_patient_state'] = $data;
	$_SESSION['operation_in_process'] = 1;

	Autodoc::message(date("H:i:s. Начата операция"),1,1);

//	Для вируса Х
	if ($data['diseases'])
	foreach ($data['diseases'] as $dis) {
		if (substr($dis['id'],0,3) == 'IVX')    {
			Autodoc::message("В крови пациента обнаружен характерный Вирус Х. Потенциальная опасность для жизни: крайне высокая. Бакто-препараты модифицированы. ",1,1);
		}
//		if ($dis['id'] == 'MYT')
//			Autodoc::message("Обнаружены необратимые генетические мутации. Реакция на стандартные медикаменты и инструменты непредсказуема. Проведение операции на свой страх и риск", 1, 1);
	}
}

function end_operation()    {
//	Проверяем, нет ли органов в отказе
	foreach ($_SESSION['systems'] as $system=>$system_data)
		if (round($system_data['stress']) >= 1000) {
			Autodoc::message('Невозможно завершить операцию, пока в состоянии отказа находится '.$system_data['name'],1,1);
			return;
		}
//	Если все цело, но основной разрез все еще есть
	if ($_SESSION['wounds']['main'])    {
		Autodoc::message('Невозможно завершить операцию, пока не заживлен основной разрез',1,1);
		return;
	}
//	Начинаем завершать операцию
	$_SESSION['operation_in_process'] = 0;
	Autodoc::message(date("H:i:s. Операция закончена"),1,1);

//	Массив выходных сообщений
	$res_msg = Array();

	$start_state = $_SESSION['start_patient_state'];
	$start_stress = array_sum(array_values($start_state['state']));
	$end_stress = 0;

//	Смотрим осложнения, заодно считаем полный стресс в конце
	$bloodloss = 5000 - $_SESSION['blood']['left'];
	$wound_troubles = 0;
	$no_wound_troubles = 0;
	foreach ($_SESSION['systems'] as $system=>$system_data) {
		$end_stress+=$system_data['stress'];
		if ($system_data['stress'] >=500 && $start_state['state'][$system]<500) {
//			Даем осложнения на систему
			if ($bloodloss >=1000)  {
				$bloodloss -= 1000;
				$wound_troubles++;
			}
			else
				$no_wound_troubles++;
		}
	}

	if ($wound_troubles)
		$res_msg[] = "Тяжелые осложнения при операции! Пациент должен взять ".right_end($wound_troubles, 'травма-карту', 'травма-карты', 'травма-карт')."
		 и активировать на ".right_end($wound_troubles, 'ней', 'них', 'них', false)." полоску Избиение";
	if ($no_wound_troubles)
		$res_msg[] = "Осложнения при операции! Пациент должен взять несколько травма-карт
		 и активировать на них в общей сложности".right_end($no_wound_troubles, 'любую полоску', 'любые полоски', 'любых полосок');

//	Смотрим, что вылечено
	foreach ($start_state['diseases'] as $disease)  {
		if (in_array($disease['id'], Autodoc::$incurables)) {
			$unhealed[] = $disease['id'];
			continue;
		}
		$healed = 1;
		foreach ($disease['systems'] as $system => $value)
			if ($_SESSION['systems'][$system]['stress'] >=$value){
				$healed = 0; break;
			}
		if ($healed)    {
			if (array_key_exists($disease['id'], Autodoc::$disease_data))
				$healed_cards[]=$disease['id'];
			elseif ($disease['id']!='NUL')
				$healed_stripes[] = $disease['id'];
		}
	}

//	Смотрим, сколько фейков можно вылечить
	$fakes = $start_state['fakes'];
	$fakes_to_heal = round(count($fakes) * (1-$end_stress/$start_stress));
	shuffle($fakes);
	for ($i=0; $i<$fakes_to_heal; $i++)
		if ($fakes[$i] != 'NUL')
			$healed_stripes[]= $fakes[$i];

	if ($healed_cards)
		$res_msg[] = "Перестали действовать карты с кодами: ".join(', ', $healed_cards);
	if ($healed_stripes)
		$res_msg[] =  'Перестали действовать полоски с кодами: '.join(', ', $healed_stripes);
	if ($unhealed)
		$res_msg[] =  'Продолжают действовать карточки с кодами: '.join(', ', $unhealed);

	Autodoc::message(join("\n", $res_msg), 0,1);
}

function take_drug($drug, $curr_time)   {
	$curr_drug = Autodoc::$medicines[$drug];
	$_SESSION['baktogel_used']+=$curr_drug['bakto_price'];
	$_SESSION['active_drugs'] [] = Array(
		'type' => $drug,
		'start_time' =>$curr_time,
		'length' => $curr_drug['length'],
		'elapsed_time' => 0,
		'left_time' => $curr_drug['length']
	);
	if (!$curr_drug['invisible'])
		Autodoc::message($_SESSION['operation_elapsed'].'. Применен препарат Бакто-'.$curr_drug['name'], 1, 1);

}






// Время операции
$operation_elapsed = gmdate('H:i:s', round(microtime(1) - $_SESSION['operation_start']));
$_SESSION['operation_elapsed'] = $operation_elapsed;
$voltage = Array();

$data = array();

$req = $_REQUEST['req'];
// Получаем текущий запрос
if ($req == 'start_operation')  {
	start_operation();
}

elseif ($req == 'end_operation')    {
	end_operation();
}

// Запрос на трек
elseif (preg_match('~^psy_~', $req))    {
	$track = substr($req, 4);
	$target = $_REQUEST['target'];
	if ($track != 'stop')   {   //  Включение трека
//		Если он не запускался раньше
		if (!in_array($track, $_SESSION['used_tracks']))    {
			$_SESSION['used_tracks'][] = $track;
			Autodoc::message($operation_elapsed.". Применен трек $track", 1,1);
			$_SESSION['active_track'] = Array('track' => $track, 'target' => $target, 'url' => Autodoc::$tracks[$track]['url']);
		}
	}
	else    {   //  Остановка трека
		$_SESSION['active_track'] = '';
	}
}

// Если активировали какой-то инструмент
elseif (preg_match('~^sur_~', $req))    {
	$tool = substr($req, 4);
	$target = $_REQUEST['target'];
	$curr_tool = Autodoc::$tools[$tool];

//	Проверяем, что этот инструмент вообще технически возможно сейчас использовать
	$valid_tool=1;
	$errmsg ='';
	if ($tool!= 'drenage' && !$target)
		$errmsg ="Невозможно применить инструмент ".$curr_tool['name']." без указания цели!";
	elseif (!$_SESSION['wounds']['main'] && !($tool == 'cut' && $target == 'main'))    {
//		Если не сделан основной разрез - нельзя делать инчего, кроме разреза и разряда
		$errmsg = "Невозможно применить инструмент ".$curr_tool['name']." таким образом, пока не сделан основной разрез";
	}
	elseif ($_SESSION['wounds']['main'] && $tool == 'cut' && $target == 'main')    {
//		Пытаемся сделать второй основной разрез
		$errmsg = "Основной разрез уже сделан";
	}
	elseif ($tool == 'transplant' && !$_SESSION['wounds'][$target]['healing'])  {
//		Пытаемся трансплантировать орган, когда нет еще разреза
		$errmsg = 'Перед тем, как трансплантировать '.Autodoc::$systems[$target]['name'].', нужно сделать там разрез, не прижигая капилляры!';
	}
	elseif ($tool == 'transplant' && $_SESSION['wounds'][$target]['burn']['qty'] <2)  {
//		Пытаемся трансплантировать орган, когда прижгли все капилляры
		$errmsg = 'Нельзя трансплантировать '.Autodoc::$systems[$target]['name'].', если на месте разреза прижжены капилляры! Заживите разрез и сделайте новый!';
	}

	elseif ($tool == 'healing' && $target == 'main' && $_SESSION['active_tools']['dilatator']['target'] )
		$errmsg = "Нельзя заживлять основной разрез, пока дилататор не будет отключен!";

	elseif (
		(in_array($tool, array( 'transplant','freeze', 'anestesia',  'shock'))
			|| (in_array($tool, array('cut','press', 'burn', 'healing')) && $target !='main')
		) && $_SESSION['active_tools']['dilatator']['target'] != $target)    {
//		Без дилататора нельзя делать ничего, кроме резки, прижигания и зажима основного разреза
		$errmsg = Autodoc::$systems[$target]['name']." не может быть целью инструмента ".$curr_tool['name']." без включенного в этом месте дилататора";
	}
//	Несмотря ни на что, если инструмент включен - его можно выключить
	if (in_array($tool, array('freeze','anestesia', 'dilatator')) && $_SESSION['active_tools'][$tool] && $target=='disable')    {
		$errmsg = '';
	}

	if ($errmsg)    {
		$valid_tool = 0;
		Autodoc::message($errmsg,1,1);
	}


	if ($valid_tool)   {
//Если действие логически непротиворечиво - делаем его
		$_SESSION['baktogel_used'] += $curr_tool['bakto_set'];
		if ($curr_tool['is_switchable'])    {
	//		Штука, которая включается или выключается
			if (!$_SESSION['active_tools'][$tool])  {
	//			Начинаем включать штуку
				$curr_tool['action'] = 'starting';
				$curr_tool['name'].=' - включение';
			}
			else    {
	//			Начинаем выключать штуку
				$active_tool = $_SESSION['active_tools'][$tool];
				$curr_tool['action'] = 'ending';
				$curr_tool['name'].=' - отключение';
//              Если это анестезия или заморозка - даем на орган анестезию или заморозку
				if ($tool != 'dilatator')   {
					$_SESSION['effects'][$active_tool['target']][$tool] = $curr_time + ($curr_time - $active_tool['time_start']) * Autodoc::FREEZE_TIME_QUOT;
				}
				$_SESSION['active_tools'][$tool] = false;
				Autodoc::message($operation_elapsed.'. Деактивирован инструмент '.preg_replace('~(.*?) .*~', '$1', $curr_tool['name']),1,1);
			}
			$curr_tool['length'] = $curr_tool['length_'.$curr_tool['action']];
			$curr_tool['changes'] = $curr_tool['changes_'.$curr_tool['action']];
			$curr_tool['power'] = $curr_tool['power_'.$curr_tool['action']];
		}
		else    {
			$curr_tool['power'] = $curr_tool['power_working'];
		}

//		Начинаем сохранять значение дамага для импланта, если идет трансплантация
		if ($tool == 'transplant')  {
			$_SESSION['implant_stress'] = 0;
		}

//		Определяем время, которое будет работать инструмент
		$time_length = $curr_tool['length'];
//		Чем больше внутри крови, тем медленнее работают все внутренние инструменты
		if (!($tool == 'cut' && $target == 'main'))
			$time_length*= (1 + $_SESSION['blood']['lost'] / 1000 * Autodoc::TOOL_SLOW_QUOT_PER_LITER);
//		 Влияние синхронизации хирурга - не реализовано
		if (isset($_SESSION['surgeon_sync']) && $_SESSION['surgeon_sync'] > 0)
			$time_length *= pow(2, (70-$_SESSION['surgeon_sync'])/30);
	//	Заглушка для скорости
		if ($ACCELERATION)
			$time_length = 5;


		$_SESSION['current_tool'] = array_merge($curr_tool, Array(
			'type' => $tool,
			'name' => Autodoc::$tools[$tool]['name'],
			'target' => $target,
			'target_name' => Autodoc::getSystemName($target),
			'start' => $curr_time,

			'end' =>$curr_time + $time_length,
		));
//		print_r($_SESSION['current_tool']); die();

		foreach ($curr_tool['changes'] as $param => $value)
			if ($value)
				if (is_numeric($value))
					$_SESSION['current_tool']['changes'][$param] = $value/$curr_tool['length'];
	} // of if ($valid_tool)
}

elseif (preg_match('~^med_~', $req))    { // Вкололи лекарство
	$drug = substr($req, 4);
	take_drug($drug, $curr_time);
}

// Массив изменений АД, Т, пульса
$delta = Array();
$delta_influable = Array(); // изменения от лекарств

// Инструмент в стадии включения/выключения
if ($_SESSION['current_tool'])   {
//	Проверяем, идет ли сейчас работа и не пора ли ее кокнуть
	$curr_tool = $_SESSION['current_tool'];
	$target = $curr_tool['target'];
	if ($curr_time >= $curr_tool['end'])    {
		if ($curr_tool['action'] == 'starting')  {
//			Если мы инициировали ее включение - она начинает работать
			$_SESSION['active_tools'][$curr_tool['type']] = Array(
				'target' =>$curr_tool['target'],
				'time_start' =>$curr_time,
			);
			Autodoc::message($operation_elapsed.'. Активирован инструмент '.preg_replace('~(.*?) .*~', '$1', $curr_tool['name']).'. Цель - '.$curr_tool['target_name'], 1,1);
		}
		elseif ($curr_tool['action']!='ending') {
			Autodoc::message($operation_elapsed.'. Применен инструмент '.$curr_tool['name'].'. Цель - '.$curr_tool['target_name'],1,1);

			$tool = $curr_tool['type'];
//			Начинаем разбирать эффекты примененного инструмента
			switch ($tool)    {

				case 'cut':
					if ($target == 'main')   {
						$_SESSION['wounds']['main'] = Array(
							'press' => Array('qty' => 2, 'size'=>32),
							'burn' => Array ('qty' => 2, 'size' => 16),
							'healing' => Array('qty' => 1, 'size' => 4)
						);
					}
					else {
//						Если разрез на этом органе еще не делался
						if (!isset($_SESSION['wounds'][$target]))
							$_SESSION['wounds'][$target] = Array(
								'press' => Array('qty' => 2, 'size'=>30),
								'burn' => Array ('qty' => 2, 'size' => 15),
								'healing' => Array('qty' => 1, 'size' => 10)
							);
						else {  // Делаем еще один разрез на этом же органе
							$_SESSION['wounds'][$target]['press']['qty'] +=2;
							$_SESSION['wounds'][$target]['burn']['qty'] +=2;
							$_SESSION['wounds'][$target]['healing']['qty'] +=1;
						}
					}
				break; //cut

				case 'press':
				case 'burn':
					$wd = $_SESSION['wounds'][$target][$tool];
//				Если на этом органе есть разрез, требующий зажима/прижигания
					if ($wd['qty'] >0)  {
						$_SESSION['wounds'][$target][$tool]['qty']--;
						if ($_SESSION['wounds'][$target][$tool]['qty'] == 0)
							unset($_SESSION['wounds'][$target][$tool]);
						if (!$_SESSION['wounds'][$target])
							unset($_SESSION['wounds'][$target]);
					}
					else
						wrong_tool_used($tool, $target);
//					Если это прижигание на замороженный орган - снимаем заморозку
					if ($tool == 'burn' && $_SESSION['effects'][$target]['freeze'] >0)
						unset($_SESSION['effects'][$target]['freeze']);
				break; //press, burn

				case 'healing':
					$wd = $_SESSION['wounds'][$target];
//					Лечим разом весь разрез со всеми сосудами
					$_SESSION['wounds'][$target]['press']['qty'] -=2;
					$_SESSION['wounds'][$target]['burn']['qty'] -=2;
					if ($_SESSION['wounds'][$target]['healing']['qty'] > 0)
						$_SESSION['wounds'][$target]['healing']['qty'] -=1;
					else
						$_SESSION['wounds'][$target]['disease']['qty'] -=1;
					foreach ($_SESSION['wounds'][$target] as $tmp_tool => $tmp_tooldata)
						if ($tmp_tooldata['qty'] <=0)
							unset($_SESSION['wounds'][$target][$tmp_tool]);
					if (!$_SESSION['wounds'][$target])  {
						unset($_SESSION['wounds'][$target]);
						if (array_key_exists($target, Autodoc::$systems))
							change_system_stress($target, -Autodoc::HEALING_TOOL_SYSTEM_STRESS_RESTORE);
					}
					break; //healing

				case 'drenage': // При отключенном дилататоре отсасывает половину потерянной крови, при включенном - всю
					if ($_SESSION['active_tools']['dilatator'])
						$_SESSION['blood']['lost'] = 0;
					else
						$_SESSION['blood']['lost']/=2;
				break; // drenage

				case 'shock':
					$delta['AD']+=60;
					$delta['pulse']+=40;
					take_drug('aftershock', $curr_time);    // отходняк после шока
					if ($_SESSION['systems'][$target]['stress']== 1000 && Autodoc::$systems['target']['is_limb']!=1 )
						$_SESSION['systems'][$target]['stress'] = 0;
					else
						wrong_tool_used('shock', $target);
				break;

				case 'transplant':
					$_SESSION['systems'][$target]['stress'] = $_SESSION['implant_stress'];
					$_SESSION['systems'][$target]['color'] = Array(rand(0,100), rand(0,100), rand(0,100));
				break;

			}
		}
		$_SESSION['current_tool'] = Array();
	} // Завершение работы инструмента
	else{
		$tool = $_SESSION['current_tool'];
//      Если делаем трансплантацию, а заморозка уже прошла - имплант начинает повреждаться
		if ($tool['type'] == 'transplant' && $_SESSION['effects'][$target]['freeze'] < $curr_time)
			$_SESSION['implant_stress'] += Autodoc::IMPLANT_BREAK_PER_SECOND * $time_spent;
//		Меняем цвет системы/органа, на которую действует инструмент
		if (array_key_exists($target, Autodoc::$systems))   {
			change_system_color($tool['target'], Autodoc::TOOL_COLOR_CHANGE_RATE*$time_spent, $tool['color']);
		}
//		Наносим повреждения по всем системам
		if ($curr_tool['influence'])    {
			$curr_tool['influence'][$target] = 100;
			foreach ($curr_tool['influence'] as $inf_system => $inf_quot)   {
				$illness_quot = Autodoc::getDamageQuot($inf_system);
				$damage = $inf_quot * $illness_quot * Autodoc::TOOL_DAMAGE_RATE * $time_spent;
				change_system_stress($inf_system, $damage);
//				Autodoc::message("Инструмент $curr_tool[name] повреждает систему $inf_system на $damage",1,0);
			}
		}

		$data['current_tool'] = $_SESSION['current_tool'];
		if ($data['current_tool']){
			unset($data['current_tool']['changes']);
			$data['current_tool'] = array_merge($data['current_tool'], Array(
				'time_remain' => gmdate("i:s", $tool['end'] - $curr_time),
				'html_color' => $tool['color'] ? rgb2html100($tool['color']):'#FFFFFF',
				'completed' => 100* ($curr_time - $tool['start'])/($tool['end'] - $tool['start']).'%'
			));
		}

	}
}
// Инструмент в стадии включения/выключения


// Ищем кровотечения.
$wound_size=0;
if ($_SESSION['wounds'])
	foreach  ($_SESSION['wounds'] as $place => $wounddata)  {
		foreach ($wounddata as $tool => $arr)
			$wound_size+= $arr['qty']*$arr['size'];
	}


// Обработка изменений от текущего инструмента
if ($_SESSION['current_tool'])   {

	foreach ($_SESSION['current_tool']['changes'] as $param=>$value)    {
		$value *=$time_spent;
		$delta[$param]+=$value;
	}
	$voltage['surgeon']['current_tool'] += $_SESSION['current_tool']['power'] * Autodoc::TOOL_POWER_QUOT;
}


//Обработка изменений от включенных инструментов - заморозок, анестезий
if ($_SESSION['active_tools'])
	foreach ($_SESSION['active_tools'] as $tool => $arr)
		if ($arr)    {
			foreach (Autodoc::$tools[$tool]['changes_enabled'] as $param => $value)
				$delta[$param] += $value * $time_spent;
//			Тратим бактагель
			$_SESSION['baktogel_used'] += round(Autodoc::$tools[$tool]['bakto_enabled'] * $time_spent);
//			Ставим эффект заморозки или анестезии на следующую секунду
			$_SESSION['effects'][$arr['target']][$tool] = $curr_time+1;
			$voltage['surgeon']['active_tools'] += Autodoc::$tools[$tool]['power_enabled'] * Autodoc::TOOL_POWER_QUOT;
		}


// Обработка изменений от текущего трека
if ($_SESSION['active_track'])  {
	$track = $_SESSION['active_track'];
	$track = array_merge($track, Autodoc::$tracks[$track['track']]);
	$target = $track['target'];
//	 Проверяем  - если система, на которую мы действуем, под анестезией - сразу хреначим нужный цвет
	$anestesia_time = $_SESSION['effects'][$target]['anestesia'];
	if ($anestesia_time > 0)    {
		if ($anestesia_time < $curr_time)   // уже истекло
			unset($_SESSION['effects'][$target]['anestesia']);
		else    // не истекло - сразу ставим нужный цвет
			$_SESSION['systems'][$target]['color'] = $track['color'];
	}
	else    {   // Работаем по обычной схеме
		change_system_color($target, $track['power'] * Autodoc::TRACK_POWER_QUOT * $time_spent, $track['color']);
	}
	$voltage['psycho']['track'] = $track['power'] * Autodoc::TRACK_POWER_QUOT * Autodoc::TRACK_POWER_PRICE;
}


// Обработка изменений от лекарств.
if (is_array($_SESSION['active_drugs']) && count($_SESSION['active_drugs'])>0)
foreach($_SESSION['active_drugs'] as $i=>$drugdata)   {
//	Если время работы истекло - убиваем
	$drugtime = $curr_time - $drugdata['start_time'];
	if ($drugtime > $drugdata['length'])
		unset($_SESSION['active_drugs'][$i]);
	else    {
		$_SESSION['active_drugs'][$i]['elapsed_time'] = $drugtime;
		$_SESSION['active_drugs'][$i]['left_time'] = $drugdata['length'] - $drugtime;
		foreach (Autodoc::$medicines[$drugdata['type']]['card'] as $param => $arr)   {
			$change_val = polynom_value($drugtime+$time_spent/2, $arr);
			if ($param == 'temp')
				$change_val*=$time_spent*0.001;
			elseif ($param == 'AD' || $param=='pulse')
				$change_val*=$time_spent*0.01;
			if ($param == 'bloodloss')
				$delta[$param] = $delta[$param] ? $delta[$param] * $change_val : $change_val;
			elseif ($param == 'blood')
				$delta[$param] += $change_val;
			elseif (in_array($param, array_keys(Autodoc::$systems)))
				change_system_stress($param, $change_val);
			else
				$delta_influable[$param] += $change_val;

			$data['healing'][$param] = $delta[$param] + $delta_influable[$param];
		}
		$voltage['surgeon']['drugs'] += Autodoc::DRUG_POWER_PRICE * Autodoc::$medicines[$drugdata['type']]['bakto_price'];
	}
}


// Влияние слишком высоких/слишком низких температуры, пульса, АД
foreach ($_SESSION['diag'] as $param => $value) {
	if (!in_array($param, array('AD', 'temp', 'pulse'))) continue;
	$card = Autodoc::$diag_results[$param];
	$levels = $card['levels'];

	if ($value >= $levels['low_norm'] && $value <= $levels['high_norm'])    {   // все хорошо - еще и полечим слегка.
		$rand_syst = Autodoc::getRandomSystem();
		$ideal_val = Autodoc::$ideal[$param];
		if ($value <= $ideal_val)
			$eff = 1-abs(($value-$ideal_val)/($levels['low_norm']-$ideal_val));
		else
			$eff = 1-abs(($value-$ideal_val)/($levels['high_norm']-$ideal_val));
//		$_SESSION['messages'][] = 'Лечим систему '.$rand_syst." по параметру $param с эффективностью $eff";
//		$data['main_message'] = 'Лечим систему '.$rand_syst." по параметру $param с эффективностью $eff";

		if ($_SESSION['systems'][$rand_syst]['stress'] == 0 || rand(0, 1000)<500)
//			Исправляем цвет
			change_system_color($rand_syst, Autodoc::COLOR_HEAL_RATE, 'heal');
		else
//			лечим стресс
			change_system_stress($rand_syst, -$eff*Autodoc::STRESS_HEAL_RATE);
	}
	else {  // Параметр в зоне риска
		$terminal = 0;
		if ($value > $levels['high_norm'])
			$prefix = 'high';
		else
			$prefix = 'low';
		$flu = Autodoc::$diag_results[$param][$prefix.'_flu'];
		if ($value >= $levels['high_terminal'] || $value < $levels['low_terminal'])
			$terminal = 1;

//		 Процент расстояния до смерти
		$percent_to_death = ($levels[$prefix.'_death'] - $value) / ($levels[$prefix.'_death'] - $levels[$prefix.'_norm']);
//		Абсолютное расстояние от границы нормы до смерти
		$range = abs($levels[$prefix.'_norm'] - $levels[$prefix.'_death']);
//		Коэффициент воздействия
		$act_quotient = pow($percent_to_death*100/$range, 2.5) / 5000 + 1;
		if ($terminal == 0) { // Зона риска, но не терминалка - выбираем несколько систем и хреначим их либо по цвету, либо по стрессу
//			Расстояние до терминалки
			$percent = abs(($levels[$prefix.'_norm'] - $value) / ($levels[$prefix.'_terminal'] - $levels[$prefix.'_norm']));
			$touched_systems = Autodoc::getTouchedSystemList($param, $flu, $percent);
			foreach ($touched_systems as $system)
				if (rand(0, 1000)<500)
//			Портим цвет - без коэффициента, а то вообще жесть
					change_system_color($system, Autodoc::COLOR_DAMAGE_RATE, 'crit', "$param = ".round($value,2)." - risk");
				else
//			повышаем стресс
					change_system_stress($system, $act_quotient*Autodoc::STRESS_DAMAGE_RATE, "$param = ".round($value,2)." - risk");
		}
		else    { // Терминальная стадия
			foreach ($flu as $system=>$val)
//			Портим цвет - без коэффициента, а то вообще жесть
				change_system_color($system, Autodoc::COLOR_DAMAGE_RATE, 'crit', "$param = ".round($value,2)." - terminal");
//			повышаем стресс
				change_system_stress($system, $act_quotient*Autodoc::STRESS_DAMAGE_RATE, "$param = ".round($value,2)." - terminal");
		}
	}
}



$delta_flu=Array();
// Влияние параметров друг на друга
foreach ($delta as $from => $fromval)
	foreach ($_SESSION['diag'] as $to => &$toval)
		$delta_flu[$to] += $fromval * Autodoc::$system_feedback[$from.'_'.$to];


$value = $delta['AD'];
//	На изменение АД влияет количество крови
$bloodleft = $_SESSION['blood']['left'];
if ($bloodleft <=5000)
	if ($value>0)
		$value*=$bloodleft/5000;
	else
		$value*= (1+$bloodleft/5000);
else
	if ($value>0)
		$value*= (1+pow(($bloodleft-5000)/10, 1.7)/1000);
	else
		$value /= (1+pow(($bloodleft-5000)/10, 1.7)/1000);
$delta['AD'] = $value;


// Переносим полученную на этом шагу разницу параметров в сессию.
foreach ($delta as $param => $value)    {
	if (array_key_exists($param, $_SESSION['diag']))
		$_SESSION['diag'][$param] += $value;
	elseif ($param == 'blood')
		$_SESSION['blood']['left'] += $value;
}

// Применяем кровопотери
$bloodloss = $wound_size*bloodloss_quotient()*Autodoc::BLOODLOSS_QUOTIENT;
if ($delta['bloodloss'])
	$bloodloss*=$delta['bloodloss'];
$_SESSION['blood']['left'] -= $bloodloss;
$_SESSION['blood']['lost'] += $bloodloss;
$_SESSION['blood']['html_color'] = polynom_value($_SESSION['blood']['left'], Array(0=>'#990000', 1500 => '#990000', 2500 => '#FF0000', 3750 => '#FFFF00', 5000 =>'#00FF00', 6000 => '#FF0000', 6500 => '#FF00000'));
if ($_SESSION['blood']['left'] <=1500)
	kill_patient('Пациент умер от кровопотери');


// Переносим влияние параметров друг на друга
foreach ($delta_flu as $param => $value)    {
	$_SESSION['diag'][$param] += $value;
}

// Переносим изменения от лекарств
foreach ($delta_influable as $param => $value)    {
	$_SESSION['diag'][$param] += $value;
}


// Возможно - меняем АД гап
if (rand(0,10)>5)
	boundAdd($_SESSION['diag']['AD_gap'],  0.01*rand(-1,1),  1.4, 1.6);

foreach ($_SESSION['systems'] as $system => &$system_data)  {
	$system_data['html_color'] = rgb2html100($system_data['color']);
	$system_data['stress_color'] = polynom_value($system_data['stress'], Array(0=>'#00CC00', 800 => '#FF0000', 1000 => '#000000', 1001 => '#000000'));
	$system_data['crit_range'] = round(Vect::length(Vect::diffVector($system_data['color'], $_SESSION['crit_color'])),1);
}

// Определяем предположительные диагностические значения

$est_range = Autodoc::$estimate_range;
foreach ($est_range as $param => &$value)   {
//	if ($param != 'temp') continue;
	$value *= $_SESSION['blood']['lost']/1000;
	if ($param == 'temp')
		$value = round($value,1);
	else $value = round($value);
//	value - длина диапазона

//	Не нужен разброс
	if ($param == 'temp' && $value<=0.1 || $value<=1)   {
		$out = $_SESSION['diag'][$param];
		$_SESSION['prev_est'][$param] = Array($out, $out);
		$_SESSION['prev_est_range'][$param] = 0;
		if ($param == 'temp')
			$out = round($out,1);
		elseif ($param == 'AD')
			$out = Autodoc::processAD($out);
		elseif ($param == 'pulse')
			$out = round($out);
		$estimate[$param] = $out;


	}
	else    {
		$new_est = Array();
		if ($prev_range = $_SESSION['prev_est_range'][$param])    {   // На прошлом шаге был диапазон
			$prev_est = $_SESSION['prev_est'][$param];
//			echo "prev_est = ".print_r($prev_est,1);
			$change = $value - $prev_range;
//			echo "<br>$change><br>";
			if (abs($change) < 0.1)
				$new_est = $prev_est;
			else    {
				if (rand(0,100)%2)
					$prev_est[0] -= $change;
				else
					$prev_est[1] += $change;
				if ($_SESSION['diag'][$param] >= $prev_est[0] && $_SESSION['diag'][$param] <= $prev_est[1] )
					$new_est = $prev_est;   // Все еще влезает
			}
		}
		if (!$new_est || $_SESSION['diag'][$param] < $new_est[0] || $_SESSION['diag'][$param] > $new_est[1])  {   // Вышли за старый диапазон, или его просто не было - формируем новый.
			if ($param == 'temp')
				$down = rand(0, $value*10)/10;
			else $down = rand(0, min($value, $_SESSION['diag'][$param]));
			$new_est = Array($_SESSION['diag'][$param] - $down, $_SESSION['diag'][$param] + $value - $down);
		}
		if ($param == 'AD')
			$estimate[$param] = Autodoc::processAD($new_est[0]).' - '.Autodoc::processAD($new_est[1]);
		elseif ($param == 'temp')
			$estimate[$param] = round($new_est[0],1).' - '.round($new_est[1],1);
		elseif ($param == 'pulse')
			$estimate[$param] = round($new_est[0]).' - '.round($new_est[1]);
		$_SESSION['prev_est_range'][$param] = $new_est[1] - $new_est[0];
		$_SESSION['prev_est'][$param] = $new_est;
	}
//	print_r($estimate);
//	print_r($_SESSION['prev_est']);
//	 die();
}


// Вывод инфы из сессии в выходной массив
$diag = $_SESSION['diag'];
unset($diag['AD_gap']);
foreach ($diag as $param => $value) {
	if (!in_array($param, array('AD', 'temp', 'pulse'))) continue;
//	Если стоит $ESTIMATED - делаем раскраску по середине предположительного диапазона, если нет - по точному значению
	$diag_colors[$param.'_color'] = Autodoc::getDiagColor($param, /*$ESTIMATED ? array_sum($_SESSION['prev_est'][$param])/2 :*/ $value);
	if ($value <= Autodoc::$diag_results[$param]['levels']['low_death'] || $value >= Autodoc::$diag_results[$param]['levels']['high_death']) {
		$param_falls = Array('AD' => "Кровяное давление вышло", 'pulse' => 'Пульс вышел', 'temp'=>'Температура вышла');
		kill_patient($operation_elapsed.". ".$param_falls[$param].' за критическую отметку. Пациент мертв');
	}
}

$diag['AD'] = Autodoc::processAD($diag['AD']);
$diag['temp'] = round($diag['temp'],1);
$diag['pulse'] = round($diag['pulse']);

foreach ($voltage as $doctor => &$arr)
	$arr['total'] = array_sum(array_values($arr));

	$data = array_merge( Array(
		'operation_in_process' => $_SESSION['operation_in_process'],
		'time_spent' => $time_spent,
		'curr_time' => $curr_time,
		'session_tool' => $_SESSION['current_tool'],
		'estimate' => $ESTIMATED ? $estimate : $diag,
		'diag' => $diag,
		'voltage' => $voltage,
		'voltage_text' => print_r($voltage,1),
		'active_track' => $_SESSION['active_track'],
		'used_tracks' => $_SESSION['used_tracks'],
		'effects' => $_SESSION['effects'],
		'active_tools' => $_SESSION['active_tools'],
		'wounds' => $_SESSION['wounds'],
		'bloodloss' => bloodloss_quotient(),
		'blood' => $_SESSION['blood'],
		'active_drugs' => $_SESSION['active_drugs'],
		'operation_elapsed' => $operation_elapsed,
		'baktogel_used' => $_SESSION['baktogel_used'] + (round(microtime(1) - $_SESSION['operation_start'])),
		'systems' => $_SESSION['systems'],
		'messages' => isset($_SESSION['messages']) ? $_SESSION['messages'] : array(),
		'diag_colors' => $diag_colors,
		'implant_stress' => $_SESSION['implant_stress'],
		'log_messages' => $_SESSION['log_messages'],
		'crit_color' => $_SESSION['crit_color'],
		'html_crit_color' => $_SESSION['html_crit_color'],
	), $data);


//	$data['full_data'] = print_r($data,1);
//print_r($_SESSION); die();

echo json_encode($data);