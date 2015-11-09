<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Хыиуду
 * Date: 10.10.13
 * Time: 12:41
 * To change this template use File | Settings | File Templates.
 */

include_once('cAutodoc.php');

$damages = Array(
	'F' => 1000,
	'B' => 1000,
	'X' => 1000,
	'W' => 500,
	'D' => 505,
	'Y' => 500,
	'J' => 300
);

function heals($letter)   {
	if ($letter == 'J') return 300;
	return 450;
}

$systems = Array(
	'SS' => 'sss',
	'BT' => 'breath',
	'BR' => 'brain',
	'NU' => 'neuro',
	'OD' => 'move',
	'HT' => 'heart',
	'ST' => 'stomach',
	'LU' => 'lungs'
);


$problem_colors = require_once('problems.php');

$diag = Autodoc::$ideal;

$codes = strtoupper(file_get_contents('codes.txt')).' NUL';
$codes = preg_split('~[, ]+~i', $codes);
foreach ($codes as &$code)  {
	preg_match('~(.*?)\=([\d\.]+)~', $code, $match);
	if ($match) {
		if ($match[1] == 'PULSE') $match[1] = 'pulse';
		if ($match[1] == 'TEMP') $match[1] = 'temp';
		$trample_diag[$match[1]] = $match[2];
		continue;
	}
	$sys_code = substr($code, 1, 2);
	if (strpos('FBXWDYJ', $code[0]) !== false && array_key_exists($sys_code, $systems))  { // Повреждение системы
		$subcodes[] = $code;
		$disease = Array(
			'id' => $code,
			'systems' => Array($systems[$sys_code] => heals($code[0]))
		);
	}   // Обычное поражение
	elseif (Autodoc::$disease_data[$code])    {  // болезнь
		$disease = Autodoc::$disease_data[$code];
//		Проверяем диагностику - если она хуже текущей, ставим ее как активную
		foreach ($disease['diag'] as $param=>$value)
			if (abs($value - Autodoc::$ideal[$param]) > abs($diag[$param] - Autodoc::$ideal[$param]))
				$diag[$param] = $value;
//      Определяем пораженные системы
		$dis_systems = Array();
		foreach ($disease['codes'] as $subcode) {
			$sys_code = substr($subcode, 1,2);
			if ($sys_code == 'RA')  {
				$sys_code = array_keys($systems);
				$sys_code = $sys_code[rand(0, count($sys_code)-1)];
				$subcode = $subcode[0].$sys_code;
			}
			$subcodes[] = $subcode;
			$dis_systems[$systems[$sys_code]] = heals($subcode[0]);
		}
		$disease = Array(
			'id' => $code,
			'systems' => $dis_systems
		);
	}   // Болезнь
	else    {
		$fakes[] = $code;
		$disease=null;
	}
	if ($disease)   {
		if (!$problem_colors[$code])    {
	//		Если раньше не попадалась такая проблема - генерируем для нее цвет
			if ($code[0] == 'X') $start = 16;
			elseif ($code[0] == 'Y') $start = 10;
			else $start = 0;
			$problem_colors[$code] = Array(rand($start,20)* 5, rand(0,20)*5, rand(0,20)*5);
		}
		$disease['color'] = $problem_colors[$code];
		$diseases[]= $disease;
	}

}

// Загоняем обратно в файл
$problem_colors_txt="<?php\n return Array(\n";
foreach ($problem_colors as $code=>$color)
	$problem_colors_txt.="      '$code' => Array(".join(', ', $color)."), \n";
$problem_colors_txt.=");";
file_put_contents('problems.php', $problem_colors_txt);

$bloodloss = Array();

foreach ($subcodes as $code)    {
	$sys_damage[substr($code, 1, 2)][] = $damages[$code[0]];
	if ($code[0] == 'X')
		$bloodloss[] = 1000;
	elseif ($code[0] == 'Y')
		$bloodloss[] = 500;
}
// Считаем нагрузку систем
foreach ($sys_damage as $system => $arr)
	$state[$systems[$system]] = min(1000, max($arr)+100*(count($arr)-1));

$total_stress = array_sum(array_values($state));

$bloodloss = array_sum($bloodloss) * pow(0.9, count($bloodloss)-1);

// Хреначим рандомные изменения АД и Т
$ideal = Autodoc::$ideal;
foreach (Array('AD' => 1.5, 'temp' => 1/3) as $param=>$mult)    {
	if ($param == 'AD')
		$range_bl = $bloodloss/100;
	else
		$range_bl = 0;
	$range = round($total_stress/100*$mult);
	$idl = $ideal[$param];
	if ($param == 'temp')
		$idl*=10;
	$value = rand($idl-$range - $range_bl, $idl+$range);
	if ($param == 'temp')
		$value/=10;
	if (abs($value - Autodoc::$ideal[$param]) > abs($diag[$param] - Autodoc::$ideal[$param]))
		$diag[$param] = $value;
}
if ($trample_diag)
	foreach ($trample_diag as $param => $value)
		if (array_key_exists($param, $diag))
			$diag[$param] = $value;

$data = array(
	'diseases' => $diseases,
	'fakes' => $fakes,
	'state' => $state,
	'bloodloss' => $bloodloss,
	'diag' => $diag
);
//print_r($data);

		/*

		$data=Array
Array
(
    [diseases] => Array
        (
            [0] => Array
                (
                    [id] => DSS
                    [systems] => Array
                        (
                            [sss] => 500
                        )

                    [color] => Array
                        (
                            [0] => 80
                            [1] => 70
                            [2] => 5
                        )

                )

            [1] => Array
                (
                    [id] => JSS
                    [systems] => Array
                        (
                            [sss] => 300
                        )

                    [color] => Array
                        (
                            [0] => 20
                            [1] => 95
                            [2] => 30
                        )

                )

            [2] => Array
                (
                    [id] => XST
                    [systems] => Array
                        (
                            [stomach] => 500
                        )

                    [color] => Array
                        (
                            [0] => 95
                            [1] => 65
                            [2] => 65
                        )

                )

            [3] => Array
                (
                    [id] => WNU
                    [systems] => Array
                        (
                            [neuro] => 500
                        )

                    [color] => Array
                        (
                            [0] => 80
                            [1] => 10
                            [2] => 25
                        )

                )

            [4] => Array
                (
                    [id] => YST
                    [systems] => Array
                        (
                            [stomach] => 500
                        )

                    [color] => Array
                        (
                            [0] => 55
                            [1] => 100
                            [2] => 5
                        )

                )

            [5] => Array
                (
                    [id] => IVX1
                    [systems] => Array
                        (
                            [move] => 500
                        )

                    [color] => Array
                        (
                            [0] => 70
                            [1] => 80
                            [2] => 90
                        )

                )

        )

    [state] => Array
        (
            [sss] => 600
            [stomach] => 1000
            [neuro] => 500
            [move] => 500
        )

    [bloodloss] => 1350
    [diag] => Array
        (
            [AD] => 153
            [temp] => 36.3
            [pulse] => 60
        )

)
	*/


echo json_encode($data);