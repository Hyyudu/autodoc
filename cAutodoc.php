<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Хыиуду
 * Date: 09.08.13
 * Time: 18:33
 * To change this template use File | Settings | File Templates.
 */

class Autodoc {
	/******************* КУЧА СЛУЖЕБНОЙ ФИГНИ ************************/
	protected static $_instance;  //экземпляр объекта

	public static function getInstance() { // получить экземпляр данного класса
		if (self::$_instance === null) { // если экземпляр данного класса  не создан
			self::$_instance = new self;  // создаем экземпляр данного класса
		}
		return self::$_instance; // возвращаем экземпляр данного класса
	}

	private  function __construct() { 	}

	private function __clone() {} //запрещаем клонирование объекта модификатором private

	private function __wakeup() {}//запрещаем клонирование объекта модификатором private
	/******************* КОНЕЦ КУЧИ СЛУЖЕБНОЙ ФИГНИ ************************/

	const STRESS_HEAL_RATE = 3;
//	Скорость лечения систем/органов - количество единиц стресса в секунду при идеальной Т/АД/Пульсе
	const COLOR_HEAL_RATE = 1;
//	Скорость лечения цвета систем/органов - количество единиц цвета в секунду при идеальной Т/АД/Пульсе
	const STRESS_DAMAGE_RATE = 1;
//	Скорость повреждения систем/органов - количество единиц стресса в секунду при Т/АД/Пульсе на выходе за границы нормы
	const COLOR_DAMAGE_RATE = 1;
//	Скорость ухудшения цвета систем/органов - количество единиц цвета в секунду при Т/АД/Пульсе на выходе за границы нормы
	const BLOODLOSS_QUOTIENT = 0.78;
	// Скорость кровопотерь
	const HEALING_TOOL_SYSTEM_STRESS_RESTORE = 300;
//	Сколько единиц стресса теряет полностью вылеченный инструментом "Заживление" орган/система
	const TOOL_COLOR_DAMAGE_BASE = 20;
//	Чем больше, тем сильнее дамажится система от инструментов
	const MAX_TOOL_DAMAGE_QUOTIENT = 200;
//	Максимальный коэффициент повреждения от инструмента в единицу времени
	const TOOL_DAMAGE_RATE = 0.1;
// Коэффициент повреждения от работы инструмента
	const TOOL_COLOR_CHANGE_RATE = 3;
//	На сколько изменяется цвет целевой системы в единицу времени
	const FREEZE_TIME_QUOT = 7;
//	Во сколько раз дольше действует анестезия/заморозка по сравнению с тем, сколько ее держали.
	const TRACK_POWER_QUOT = 2;
//	Во сколько раз усиливается воздействие треков
	const IMPLANT_BREAK_PER_SECOND = 50;
//	На сколько за секунду повреждается имплант без заморозки
	const TOOL_SLOW_QUOT_PER_LITER = 0.1;
//	На сколько замедляется работа инструментов за каждый литр потерянной крови
	const TRACK_POWER_PRICE = 5;
//	Цена единицы силы трека в единицах энергопотребления в секунду
	const DRUG_POWER_PRICE = 0.5;
//	Цена единицы бактогеля лекарства в единицах энергопотребления в секунду
	const TOOL_POWER_QUOT = 2;
//	Множитель энергопотребления инструментов
	const VIRUS_X_ED = 10;
//	кол-во заболевших вирусом Х

	/**
	 * Как системы воздействуют друг на друга.
	 * Читать так: 'pulse_AD' => 0.3, - значит, изменение пульса на 1 изменит АД в ту же сторону на 0.3
	 * У температуры такие показатели, потому что она измеряется в десятых, не забываем
	 */
	public static $system_feedback = Array(
		'pulse_AD' => 0.3,
		'pulse_temp' => 0.03,
		'AD_pulse'=>0.3,
		'AD_temp'=>0.01,
		'temp_pulse' => 3,
		'temp_AD' => 1,
	);

	public static $incurables = Array(
		"IPA",  //Пандорум
//		'QUE', //Неха
	);

	public static $disease_data = Array(
		'IVX0' => Array( // вирус Х
			'codes' => Array(),
			'diag' => Array('AD' => 120, 'temp' => 36.8)
		),
		'IVX1'=> Array(
			'codes' => Array('WBT'),
			'diag' => Array('AD' => 125, 'temp' => 36.4)
		),
		'IVX2'=> Array(
			'codes' => Array('WBT', 'WHT', 'WNU'),
			'diag' => Array('AD' => 80, 'temp' => 35.9)
		),
		'IVX3'=> Array(
			'codes' => Array('WBT', 'WHT', 'BNU'),
			'diag' => Array('AD' => 70, 'temp' => 35.7)
		),
		'IPA' => Array(     // Пандорум
			'codes' => Array(),
			'diag' => Array('AD' => 130, 'temp' => 37),
		),
//		'QUE' => Array(
//			'codes' => 'DNU', 'DOD',
//			'diag' => Array('AD' => 140, 'temp' => 37.5)
//		)
//		'MYT' => Array(
//			'codes' => Array('YNU', 'JNU'),
//			'diag' => Array('AD' => 130, 'temp' => 37.2)
//		)
	);


	public static function getPulse($AD, $temp)  {
		$ideal = Autodoc::$ideal;
		$ADk = Autodoc::$system_feedback['AD_pulse'];
		$tempk = Autodoc::$system_feedback['temp_pulse'];
		$ret= $ideal['pulse'] + ($AD-$ideal['AD'])*$ADk + ($temp - $ideal['temp'])*$tempk;
		return $ret;
	}


//	Проблемные состояния (болезни)
	public static $problems = Array(
		'beaten' => Array(
			'color' => Array(70, 70, 30),
			'curable' => 0
		),
		'limb_damage' => Array(
			'color' => array(80,60,30),
			'curable' => 1,
		),
		'body_wound_low' => Array(
			'color' => array(80,50,30),
			'curable' => 1,
		),
		'body_wound_high' => Array(
			'color' => array(80,50,30),
			'curable' => 1,
		),
		'head_damage' => Array(
			'color' => array(60,80,30),
			'curable' => 1,
		),
		'stun' => Array(
			'color' => array(50,90,30),
			'curable' => 0,
		),
		'near_death' => Array(
			'color' => array(20,90,90),
			'curable' => 1,
		),

	);

//	Идеальное состояние пациента
	public static $ideal  = Array(
		'AD' =>115,
		'temp' => 36.6,
		'pulse'=>60
	);

//	Радиус разброса показателей на литр неоткачанной крови
	public static $estimate_range = Array(
		'AD' => 20,
		'pulse' => 15,
		'temp' => 0.7
	);

//	Инструменты хирурга
	public static $tools = Array(
		'cut' => Array(
			'length' =>15,
			'name' => 'Разрез',
			'damage' => 2,
			'color' => Array(100,0,0),
			'targets' => Array ('main', 'limbs'),
			'power_working' => 10,
			'changes' => Array(
				'AD' => -30,
				'pulse' => +20
			),
			'influence' => Array(
				'heart' => 50,
				'sss' => 80,
				'neuro' => 80,
//				'immunity' => 10,
			)
		),
		'dilatator' => Array(
			'is_switchable' => 1,
			'length_starting' =>20,
			'color' => Array(80,80,0),
			'damage' => 1.5,
			'length_ending' => 15,
			'targets' => Array ('limbs', 'systems'),
			'name' => 'Дилататор',
			'power_starting' => 5,
			'power_enabled' => 1,
			'power_ending' => 5,
			'changes_starting' => Array(
				'AD' => +20,
				'pulse' => -5
			),
			'changes_enabled' => Array(),
			'changes_ending' => Array(
				'AD' => -15,
				'pulse' => -5
			),
			'influence' => Array(
				'heart' => 50,
				'stomach' => 50,
				'lungs' => 50,
				'move' => 100,
			)
		),
		'healing' => Array(
			'length' => 20,
			'damage' => 0.2,
			'color' => Array(10,70,95),
			'targets' => Array ('main', 'limbs', 'systems'),
			'power_working' => 25,
			'name' => 'Заживление',
			'bakto_set' => 15,
			'changes' => Array(
				'AD' => +20,
				'temp' => +1.2,
				'pulse' => -10
			),
			'influence' => Array(
				'heart' => 50,
				'breath' => 70,
				'neuro' => 40,
//				'immunity' => 15,
			)
		),
		'shock' => Array(
			'length' => 10,
			'damage' => 2,
			'color' => Array(0,90,0),
			'power_working' => 30,
			'name' => 'Разряд',
			'targets' => Array ('systems','limbs', ),
			'changes' => Array(
 // Сначала ++60 АД, 40 пульс. А потом запускается процесс aftershock, который эти параметры за 10 секунд уронит ниже начального
			),
			'influence' => Array(
				'heart' => 100,
				'lungs' => 100,
				'sss' => 100,
				'breath' => 50,
				'move' => 100,
				'brain' => 100,
				'neuro' => 50,
//				'immunity' => 20,
			)
		),
		'anestesia' => Array(
			'is_switchable' => 1,
			'length_starting' =>15,
			'length_ending'=>10,
			'damage' => 2,
			'name' => 'Анестезия',
			'targets' => Array ('limbs', 'systems'),
			'power_starting' => 4,
			'power_enabled' => 30,
			'power_ending' => 0,
			'bakto_enabled' => 10,
			'changes_starting' => Array(),
			'changes_enabled' => Array(
				'AD' => -5,
				'temp' => -0.02,
				'pulse' => -6
			),
			'changes_ending' => Array(
				'AD' => +25,
				'temp' => +0.5,
				'pulse' => +20
			),
			'influence' => Array(
				'brain' => 70,
				'neuro' => 50,
//				'immunity' => 30,
			)
		),
		'drenage' => Array(
			'length' =>15,
			'name' => 'Дренаж',
			'damage' => 0.8,
			'power_working' => 10,
			'changes' => Array(
				'AD' => -5,
			),
			'influence' => Array(
				'breath' => 10,
				'move' => 60,
//				'digest' => 60,
			)
		),
		'burn' => Array(
			'length' =>5,
			'damage' => 1.2,
			'name' => 'Прижигание',
			'power_working' => 8,
			'targets' => Array ('main', 'limbs'),
			'color' => Array(60,20,60),
			'changes' => Array(
				'pulse' => +2,
				'temp' => +0.1
			),
			'influence' => Array(
				'heart' => 20,
				'stomach' =>20 ,
				'lungs' =>20,
				'sss' => 20,
				'breath' => 40,
				'move' => 20,
				'brain' => 40,
				'neuro' => 40,
//				'immunity' => 10,
			)
		),
		'press' => Array(
			'length' =>5,
			'name' => 'Зажим',
			'damage' => 1.4,
			'power_working' => 8,
			'color' => Array(60,20,60),
			'targets' => Array ('main', 'limbs'),
			'changes' => Array(
				'AD' => +2,
				'temp' => +0.1
			),
			'influence' => Array(
				'heart' => 20,
				'stomach' => 10,
				'lungs' => 10,
				'sss' => 20,
				'breath' => 10,
//				'digest' => 40,
				'brain' => 10,
				'neuro' => 10,
			)
		),
		'freeze' => Array(
			'is_switchable' => 1,
			'length_starting' =>10,
			'length_ending'=>5,
			'damage' => 2,
			'name' => 'Заморозка',
			'power_starting' => 4,
			'power_working' => 30,
			'power_ending' => 0,
			'targets' => Array ('main', 'limbs'),
			'changes_starting' => Array(),
			'changes_enabled' => Array(
				'AD' => +5,
				'temp' => +0.02,
				'pulse' => +5
			),
			'changes_ending' => Array(
				'AD' => -25,
				'temp' => -0.5,
				'pulse' => -20
			),
			'influence' => Array(
				'heart' => 100,
				'stomach' => 60,
				'lungs' => 60,
				'sss' => 80,
				'breath' => 100,
//				'digest' => 100,
				'brain' => 80,
				'neuro' => 60,
//				'immunity' => 30,
			)
		),
		'transplant' => Array(
			'length' => 25,
			'name' => 'Трансплантация',
			'damage' => 1,
			'power_working' => 25,
			'bakto_set' => 300,
			'color' => Array(50,50,50),
			'targets' => Array ('limbs'),
			'changes' => Array(
				'pulse' => -20,
				'temp' => +1
			),
			'influence' => Array(
				'heart' => 20,
				'stomach' => 20,
				'lungs' => 20,
				'sss' => 20,
				'breath' => 20,
//				'digest' => 20,
				'brain' => 20,
				'neuro' => 20,
			)
		),
	);

//	Список систем и органов
	public static $systems = Array(
		'heart' => Array(
			'color' => Array(60, 30, 30),
			"name"=>"Сердце",
			'name_vin' => 'сердце',
			'is_limb'=>1,
		),

		'stomach' => Array(
			'color' => Array(70, 60, 100),
			'name' => "Желудок",
			'name_vin' => 'желудок',
			'is_limb'=>1,
		),
		'lungs' => Array(
			'color' => Array(90, 40, 70),
			'name' => "Легкие",
			'name_vin' => 'легкие',
			'is_limb'=>1,
		),
		'sss'=>Array(
			'color' => Array(70, 40, 50),
			'name'=> 'Сердечно-сосудистая система',
			'name_vin' => 'сердечно-сосудистую систему',
		),
		/*'digest'=>Array(
			'color' => Array(10, 90, 40),
			'name'=> 'Пищеварительная система'
		),*/
		'breath'=>Array(
			'color' => Array(0, 50, 20),
			'name'=> 'Дыхательная система',
			'name_vin' => 'дыхательную систему',
		),
		'move'=>Array(
			'color' => Array(30, 10, 60),
			'name'=> 'Опорно-двигательный аппарат',
			'name_vin'=> 'опорно-двигательный аппарат'
		),
		'brain'=>Array(
			'color' => Array(0, 30, 100),
			'name'=> 'Мозг',
			'name_vin'=> 'мозг'
		),
		'neuro'=>Array(
			'color' => Array(50, 50, 0),
			'name'=> 'Нервная система',
			'name_vin'=> 'нервную систему'
		),
		/*'immunity' => Array(
			'color' => Array(20,90,30),
			'name' => 'Иммунная система'
		)*/
	);

		// В карте указано изменение параметра в у.е. за секунду. 1 у.е. - это 0,01 единица АД, 0,01 единица пульса или 0.001 градус
	public static $medicines = Array(
		'frestizol' => Array(
			'name' => "Фрестизол",
			'desc' => 'Слабое жаропонижающее',
			'bakto_price' => 8,
			'length' => 50,
			'card' => Array(
				'temp' => Array(
					0 => 0,
					10 => -50,
					30 => -50,
					50 => 0,
					51 => 0
				)
			)
		),
		'hamiltan' => Array(
			'name' => "Гамильтан",
			'desc' => 'Повышает давление и пульс',
			'bakto_price' => 15,
			'length' => 90,
			'card' => Array(
				'AD' => Array(
					0 => 0,
					10 => 200,
					30 => 50,
					60 => 0,
					80 => 0,
					90 => -50,
					91 => 0,
					92 =>0
				),
				'pulse' => Array(
					0=> 0,
					10 => 50,
					30 => 100,
					50 => 50,
					60 => 0,
					92=>0
				)
			)
		),
		'aftershock' => Array(
			'name' => 'Последствия разряда',
			'invisible'=> 1,
			'length'=> 11,
			'card' => Array(
				'AD' => Array(
					0 => 0,
					1 => -1600,
					11 => 0,
					12 =>0
				),
				'pulse' => Array(
					0 => 0,
					1 => -1000,
					11 => 0,
					12 =>0
				)
			)
		),
		'nemiden' => Array(
			'name' => "Немиден",
			'desc' => 'Кроветворное средство, слегка повышает температуру',
			'bakto_price' => 15,
			'length' => 100,
			'card' => Array(
				'blood' => Array(
					0 => 0,
					10 => 10,
					90 => 10,
					100 => 0,
					101 =>0
				),
				'temp' => Array(
					0 => 0,
					10 => 10,
					90 => 10,
					100 => 0,
					101 =>0
				),
			)
		),
		'zalten' => Array(
			'name' => "Зальтен",
			'desc' => 'Уменьшает скорость кровопотерь, слегка понижает давление',
			'bakto_price' => 25,
			'length' => 240,
			'card' => Array(
				'bloodloss' => Array(
					0 => 1,
					20 => 0.5,
					220 => 0.5,
					240 => 1,
					241 => 1
				),
				'AD' => Array(
					0 => 0,
					10 => -10,
					30 => -10,
					50 => 0,
					80 => 0,

				),
			)
		),
		'relanon' => Array(
			'name' => "Реланон",
			'desc' => 'Резко повышает АД, понижает пульс',
			'bakto_price' => 12,
			'length' => 60,
			'card' => Array(
				'AD' => Array(
					0 => 0,
					2 => 100,
					10 => 100,
					40 => 50,
					60 => 0,
					92 =>0
				),
				'pulse' => Array(
					0=> 0,
					10 => -50,
					50 => -50,
					60 => 0,
					92=>0
				)
			)
		),
		'tiklozan' => Array(
			'name' => "Тиклозан",
			'desc' => 'Мощное жаропонижающее, слегка понижает давление',
			'bakto_price' => 16,
			'length' => 50,
			'card' => Array(
				'temp' => Array(
					0 => 0,
					5 => -200,
					10 => -100,
					40 => -50,
					50 => 0,
					51 => 0
				),
				'AD' => Array(
					0 => 0,
					10 => -50,
					40 => -30,
					50 => 0,
					51 =>0
				),
			)
		),
		'cardolit' => Array(
			'name' => "Кардолит",
			'desc' => 'Понижает пульс',
			'bakto_price' => 12,
			'length' => 50,
			'card' => Array(
				'pulse' => Array(
					0 => 0,
					5 => -200,
					10 => -100,
					30 => -50,
					40 => 0,
					50 => 0,
					51 => 0
				),
			)
		),
	);

	public static $diag_results = Array(
		'temp' => Array(
			'levels' => array(
				'low_death' => 33,
				'low_terminal' => 34,
				'low_norm' => 35.5,
				'high_norm' => 38.2,
				'high_terminal' => 40.5,
				'high_death' => 42
			),
			'high_flu' => array(
				'brain' => 4,
				'neuro' => 4,
				'heart' => 1,
				'sss' => 2
			),
			'low_flu' => array(
				'heart' => 2,
				'neuro' => 3,
				'move' => 4
			),
		),
		'AD' => Array(
			'levels' => array(
				'low_death' => 35,
				'low_terminal' => 50,
				'low_norm' => 85,
				'high_norm' => 140,
				'high_terminal' => 200,
				'high_death' => 260
			),
			'high_flu' => array(
				'sss'=> 5,
				'lungs' => 3,
				'heart' => 3
			),
			'low_flu' => array(
				'brain' => 3,
				'heart' => 4,
				'stomach' => 3,
				'lungs' => 3,
				'sss' => 3,
				'neuro' => 4
			),
		),
		'pulse' => Array(
			'levels' => array(
				'low_death' => 25,
				'low_terminal' => 35,
				'low_norm' => 50,
				'high_norm' => 100,
				'high_terminal' => 140,
				'high_death' => 170
			),
			'high_flu' => array(
				'sss'=> 5,
				'lungs' => 1,
				'heart' => 5,
				'brain' => 4
			),
			'low_flu' => array(
				'brain' => 3,
				'heart' => 4,
				'stomach' => 2,
				'lungs' => 2,
				'move' => 2,
				'sss' => 3,
				'neuro' => 4
			),
		),
	);

	public static $tracks = Array(
		'Cerulean Skies' => Array(
			'url' => 'black_cerulean.mp3',
			'color' => Array(0,0,0),
			'power' => 5
		),
		'Cascades' => Array(
			'url' => 'blue_cascades.mp3',
			'color' => Array(0,0,100),
			'power' => 5
		),
		'Cell Cycle' => Array(
			'url' => 'green_cellcycle.mp3',
			'color' => Array(0,100,0),
			'power' => 5
		),
		'Neo' => Array(
			'url' => 'cyan_neo.mp3',
			'color' => Array(0,100,100),
			'power' => 5
		),
		'Cadastral Survey' => Array(
			'url' => 'red_cadastral.mp3',
			'color' => Array(100,0,0),
			'power' => 5
		),
		'Games for Two' => Array(
			'url' => 'magenta_games42.mp3',
			'color' => Array(100,0,100),
			'power' => 5
		),
		'Prime Movers' => Array(
			'url' => 'yellow_primemovers.mp3',
			'color' => Array(100,100,0),
			'power' => 5
		),
		'White Nights' => Array(
			'url' => 'white_whitenights.mp3',
			'color' => Array(100,100,100),
			'power' => 5
		),
		'Anesidora' => Array(
			'url' => 'gray_anesidora.mp3',
			'color' => Array(50,50,50),
			'power' => 5
		),
	);

	public static function getRandomSystem()   {
		$arr = array_keys($_SESSION['systems']);
		do  {
			$x = $arr[rand(0, count($arr)-1)];
		}
		while ($_SESSION['systems'][$x]['stress'] == 1000);
		return $x;
	}

	public static function getTouchedSystemList($param, $arr, $percent)  {
		$count = max(1,round(count($arr)*$percent));
		$out = array();
		for ($i=0; $i<$count; $i++) {
			$system = Vect::rand_by_weight($arr);
			$out[]=$system;
			unset($arr[$system]);
		}
//		$_SESSION['messages'][] = "Список систем для $param на проценте $percent - ".join(', ', $out);
		return $out;
	}

	public static function getDiagColor($param, $value) {
		$value_colors = Array(
			'low_death'=>'#000000',
			'low_terminal' => '#FF0000',
			'low_norm' => '#00DD00',
			'high_norm' => '#00DD00',
			'high_terminal' => '#FF0000',
			'high_death'=>'#000000',
		);
		$levels = Autodoc::$diag_results[$param]['levels'];
		foreach ($levels as $level => $num)
			$arr[$num] = $value_colors[$level];
		$vals = array_keys($arr);
		if ($value <= min($vals) || $value >= max($vals))
			return '#000000';
		$arr[($levels['low_terminal']+$levels['low_norm'])/2] = '#DDDD00';
		$arr[($levels['high_terminal']+$levels['high_norm'])/2] = '#DDDD00';
		ksort($arr);
		return polynom_value($value, $arr);
	}

	public static function message($text, $instant=1, $log=0)   {
		if ($instant)
			$_SESSION['messages'][] = $text;
		if ($log)
			$_SESSION['log_messages'][] = $text;
	}

	public static function getSystemName($system)   {
		if ($data = Autodoc::$systems[$system])
			return $data['name'];
		if ($system == 'main')
			return 'Основной разрез';
	}

	public static function getDamageQuot($system)   {
		$length = Vect::length(Vect::diffVector($_SESSION['systems'][$system]['color'], $_SESSION['crit_color']));
		if ($length == 0)
			return Autodoc::MAX_TOOL_DAMAGE_QUOTIENT;
		return min(Autodoc::MAX_TOOL_DAMAGE_QUOTIENT, Autodoc::TOOL_COLOR_DAMAGE_BASE/$length);
	}

	public static function processAD($AD)   {
		return round($AD).'/'.round($AD/$_SESSION['diag']['AD_gap']);
	}

}