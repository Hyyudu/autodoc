<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Хыиуду
 * Date: 09.08.13
 * Time: 18:33
 * To change this template use File | Settings | File Templates.
 */

class Vect {
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

	public static function length($arr)    {
		return sqrt($arr[0]*$arr[0] + $arr[1]*$arr[1] + $arr[2]*$arr[2]);
	}

	public static function normalizeVector($arr, $to_len=1)    {
		$len = Vect::length($arr)/$to_len;
		if ($len)
			return Array($arr[0]/$len, $arr[1]/$len, $arr[2]/$len);
		else
			return Array(0,0,0);
		print_r(debug_backtrace());
		die('Division by zero');
	}

	public static function diffVector($arr1, $arr2)    {
		return Array($arr2[0]-$arr1[0], $arr2[1]-$arr1[1], $arr2[2]-$arr1[2]);
	}

	public static function boundVector($arr, $min, $max, $type='fit')   {
		foreach ($arr as &$item)
			$item = bound($item, $min, $max, $type);
		return $arr;
	}

	public static function addVector($arr1, $arr2) {
		return Array($arr2[0]+$arr1[0], $arr2[1]+$arr1[1], $arr2[2]+$arr1[2]);
	}

	public static function mulVector($arr, $x) {
		return Array($arr[0]*$x, $arr[1]*$x, $arr[2]*$x);
	}

	public static function mean_color($arr)   {
		$out = Array(0,0,0);
		$sum=0;
		foreach ($arr as $item) {
			for ($i=0; $i<3; $i++)
				$out[$i]+= $item[0][$i]*$item[1];
			$sum+=$item[1];
		}
		if ($sum == 0)  {
			print_r(debug_backtrace());
			die();
		}
		for ($i=0; $i<3; $i++)  {
			$out[$i] = round($out[$i]/$sum);
		}
		return $out;
	}

	/**
	 * Выбирает рандомный элемент в соответствии с весом
	 * Для Array('a'=>4, 'b'=>1) в 4 случаях из 5 вернет а, в одном - b
	 * @param $arr
	 * @return int|string
	 */
	public static function rand_by_weight($arr) {
		$total_weight = array_sum(array_values($arr));
		$x = rand(1,$total_weight);
		$sum = 0;
		foreach ($arr as $key => $weight)   {
			$sum+=$weight;
			if ($sum>=$x)
				return $key;
		}
	}
}