<?php

/**
 * Created by JetBrains PhpStorm.
 * User: Хыиуду
 * Date: 27.07.13
 * Time: 21:08
 * To change this template use File | Settings | File Templates.
 */

include_once('cAutodoc.php');
include_once('../aura/functions.inc.php');

if (isset($_REQUEST['psycho']))
	$user = 'psycho';
elseif (isset($_REQUEST['overseer']))
	$user = 'overseer';
else
	$user = 'surgeon';

header('Content-type: text/html; charset=UTF-8');
if (!isset($process_url))
	$process_url = 'http://'.$_SERVER['HTTP_HOST'].'/autodoc/process.php';
?>
<script type="text/javascript" src="/../js/jquery-2.0.3.min.js"></script>
<script type="text/javascript" src="/../js/jquery.json-2.4.min.js"></script>
<script type="text/javascript" src="/../js/menuvert.js"></script>
<link href="main.css?<?=md5(time())?>" media="all" rel="stylesheet" type="text/css" />
<script type="text/javascript">
	oper_int=0;
	var system_names = {
		<? foreach (Autodoc::$systems as $system => $system_data)
				$tmp[]="$system: '$system_data[name]'";
			echo join(', ',$tmp);
		?>
	};
</script>

<? if ($user!='overseer') { ?>
<div id='wrapper' class=left>
<? } ?>

<? if ($user=='surgeon') { ?>
	<div class="screenLockWrapper" id='surgeon_lock'>
		<div class="tcell">
			<div id="screenLockMessage">Нехватка электроэнергии</div>
		</div>
	</div>
<? } ?>

<? if ($user=='psycho') { ?>
	<div class="screenLockWrapper" id='psycho_lock'>
		<div class="tcell">
			<div id="screenLockMessage">Нехватка электроэнергии</div>
		</div>
	</div>
<? } ?>

	<div id='start_operation_controls'>
	<? if ($user!='psycho') echo "<button id=start_operation >Начать операцию</button>";
		if ($user != 'surgeon') echo "<button id=join_operation >Присоединиться к операции</button>";
	?>
	</div>

	<? if ($user!='psycho') { ?>
	<div id=surgeon_tools>


		<div style="width:200px">
			<ul class="menu_vert">
				<? foreach (Autodoc::$tools as $tool => $tooldata)    {
				if (!$tooldata['targets']) //бесцелевой инструмент, напр., дренаж
					$out.="
			<li><a id='$tool' href='#'>$tooldata[name]</a></li>\n";
				else    {

					$out.="
			<li><a".($tooldata['length_starting'] ? " id=$tool" : '').">$tooldata[name]</a>
				<ul>\n";
					foreach ($tooldata['targets'] as $target)
						switch ($target)    {
							case 'main': $out.="				<li><a id=${tool}_main href='#'>Основной разрез</a></li>\n";
								break;
							case 'limbs':
								foreach (Autodoc::$systems as $system=>$system_data)
									if ($system_data['is_limb'])
										$out.="				<li><a id={$tool}_{$system} href='#'>$system_data[name]</a></li>\n";
								break;
							case 'systems':
								foreach (Autodoc::$systems as $system=>$system_data)
									if (!$system_data['is_limb'] && $system!='immunity')
										$out.="				<li><a id={$tool}_{$system} href='#'>$system_data[name]</a></li>\n";
								break;
							case 'implant': $out.="				<li><a id=${tool}_implant href='#'>Район импланта</a></li>\n";
								break;
						}
					$out.="			</ul>
			</li>\n";
					if ($tooldata['length_starting'])
						$out.="				<li><a id='{$tool}_enabled' style='display: none; color: red; font-weight: bold'>$tooldata[name]</a>
							<ul>
								<li><a id='{$tool}_target'></a></li>
								<li><a id='{$tool}_disable' href='#'>Отключение</a></li>
							</ul>
						</li>\n";
				}
			}
				echo $out;
				?>


			</ul>
			<div id=menu_cover>
				<h3>Применяется инструмент</h3>
				<span class='data_output current_tool_name'></span>
				<h3>Цель:</h3>
				<span class=current_tool_target></span>
				<div id=tool_progress_wrapper>
					<div id=tool_progress></div>
				</div>
				<span id=current_tool_time_remain></span>
			</div>
		</div>



		<button id=end_operation>Закончить операцию</button><br/>
	</div>
	<? } // Только для хирурга ?>


	<div id=medicines>
		<?
		foreach (Autodoc::$medicines as $code => $meddata)
			if ($code && !$meddata['invisible'])
				echo "
			<button class='bts medicines' title='$meddata[desc]' id='med_$code'>Бакто-".$meddata['name']." (".$meddata['bakto_price'].")</button><br>\n";
		?>
	</div>


		<table border=1 id=params class=left>
			<tr><td>Время операции<td><span class=data_output id=operation_elapsed></span></tr>
			<tr><td>Потрачено бактогеля<td><span class=data_output id=baktogel_used></span></tr>
			<tr><td>Кровяное давление<td class=diag_val id=AD_color><span class=data_output id=AD></span></tr>
			<tr><td>Температура<td class=diag_val id=temp_color><span class=data_output id=temp></span></tr>
			<tr><td>Пульс<td class=diag_val id=pulse_color><span class=data_output id=pulse></span></tr>
	<? if ($user != 'surgeon')  { ?>
			<tr><td>Инструмент<td><span class=current_tool_name></span> <span id=current_tool_time_remain><span></tr>
			<tr><td>Цель</td><td><span class=current_tool_target><span></tr>
			<tr><td>Критический цвет<td id=crit_color></tr>
	<? } ?>
			<tr><td>Кровь (осталось/потери)<td><span id=blood_left></span>/<span id=blood_lost></span></tr>

		</table>


		<table id=systems border=1 class=right>
			<thead><td>Система (цвет/расстояние до крита)</td><td>Нагрузка</td></thead>
			<?
			foreach (Autodoc::$systems as $system=>$system_data)
				echo "
			<tr id='${system}_bg'><td>$system_data[name] <span id='${system}_data'></span>
				<td class=stress_val id='${system}_stress'>&nbsp;</td>";
			?>
		</table>


<? if ($user != 'surgeon')  { ?>
	<div class=clear></div>
	<div id=tracks class=left>
			<?
			foreach (Autodoc::$tracks as $track=>$track_data)    {
				echo "
				<input type=radio name=track id='{$track}_radio' value='$track'>
				<label id='{$track}_label' for='{$track}_radio' style='background-color: ".
					rgb2html100($track_data['color']).
					"; color:".(array_sum($track_data['color'])>100?'black':'white')."'>$track (".join (',', $track_data['color']).")</label><br>\n";
			}
			echo "Область применения: <br><select name=track_target id='track_target' style='width: 200px'>\n";
			foreach (Autodoc::$systems as $system => $system_data)
				echo "<option value=$system>$system_data[name]</option>\n";
			echo "</select> <br>
			<button id='start_track'>Запустить трек</button>
			<button style='display:none' id='stop_track'>Остановить трек</button>";
			?>
	</div>
<? } ?>

	<div class=clear></div>
	<audio id=audio loop></audio>
	<span id=main_message></span>
	<div id=log>

		<textarea id=log_messages rows=10 cols="60" readonly="readonly"></textarea>
	</div>
	<div class=clear></div>

<? if ($user!='overseer') { ?>
</div>
<? } ?>
<pre id='voltage' class='left'>

</pre>

<script type="text/javascript">
	$(function() {

		function start_track()    {
			var checked_track = $('#tracks input:radio:checked');
			if (checked_track.length < 1)
				return;
			var track = checked_track[0].id.replace('_radio','');
			var target = $('#track_target')[0].value;
			data = {req: 'psy_'+track, target: target};
//          Задизаблим все треки
			$('#tracks input:radio').attr('disabled', 'disabled');
			$('#track_target').attr('disabled', 'disabled');
//          Выделим текущий
			$('#'+track+'_label').first().css({fontWeight: 'bold'});
//          Меняем кнопки
			$('#stop_track, #start_track').toggle()
			$.ajax({
				url: '<?=$process_url;?>',
				data: data,
				success: function(data) {update_data($.parseJSON(data))}
			});
		}

		function stop_track()    {
//          Открываем все треки
			$('#tracks input:radio').attr('disabled', null);
			$('#track_target').attr('disabled', null);
//          Уберем выделение полужирным
			$('#tracks label').css({fontWeight: 'normal'});
//          Снимаем отметку
			var checked =  $('#tracks input:radio:checked');
			if (checked.length > 0)
				$('#tracks input:radio:checked')[0].checked = false;
//          Меняем кнопки
			$('#stop_track, #start_track').toggle()
			$.ajax({
				url: '<?=$process_url;?>',
				data: {req: 'psy_stop'},
				success: function(data) {update_data($.parseJSON(data))}
			});
		}

		function update_data(data)  {
			// Если помер - отключаем активность
			if (data.death == 1 || data.operation_in_process ==0)    {
				clearInterval(oper_int);
			}
			// Выводим всю диагностику в соответствующие поля
			$('.data_output').each(function() {
				if (typeof(data[this.id])!='undefined')
					this.innerHTML = data[this.id];
				else    {
					diag_val = data.estimate[this.id];
					<? if ($user == 'overseer') echo "diag_val += ' ('+data.diag[this.id]+')';"; ?>
					this.innerHTML= diag_val;
				}
			})
			// Раскрашиваем поля АД, Т, пульса в соответствии со значениями
			$('.diag_val').each(function() {
				$(this).css({backgroundColor : data.diag_colors[this.id]});
			})
//          Раскрашиваем ячейку с названием инструмента

			//$('#voltage')[0].innerHTML = data.voltage_text;

			$('.current_tool_name').each(function() {this.innerHTML = typeof(data.current_tool)!='undefined' ? data.current_tool.name : '';});

			if (typeof(data.current_tool) != 'undefined') {
				$('#menu_cover').show();
				$('#current_tool_name').parent().css('backgroundColor', data.current_tool.html_color)
				$('.current_tool_target')[0].innerHTML = data.current_tool.target_name;
				$('#tool_progress').css({width: data.current_tool.completed, backgroundColor: data.current_tool.html_color})
				if (data.blood.lost <1000 && typeof(data.current_tool.time_remain)!='undefined')
					$('#current_tool_time_remain')[0].innerHTML = data.current_tool.time_remain;
				else
					$('#current_tool_time_remain')[0].innerHTML = '';
			}
			else{
				$('#menu_cover').hide();
			}

			if ($('#crit_color').length >0) {
				$("#crit_color").css({backgroundColor : data.html_crit_color});
				$("#crit_color")[0].innerHTML = data.crit_color.join(', ');
			}

			$('#blood_left')[0].innerHTML = Math.round(data.blood.left);
			$('#blood_lost')[0].innerHTML = Math.round(data.blood.lost);
			$('#blood_left').css({backgroundColor: data.blood.html_color})


			<? if ($user != 'psycho') { ?>

			for (tool in data.active_tools)    {
				var target = data.active_tools[tool].target;
				if (target) {
//                  Спрятать основной пункт
					$('#'+tool).parent().hide();
//                  Показать пункт с красным болдом
					$('#'+tool+'_enabled').show();
//
					$('#'+tool+'_target')[0].innerHTML = system_names[target]
				}
				else    {
//                  Спрятать основной пункт
					$('#'+tool).parent().show();
//                  Показать пункт с красным болдом
					$('#'+tool+'_enabled').hide();
				}

			}
			<? } ?>

			if (typeof(data.current_tool)!='undefined')
				$('.surtools').attr('disabled', 'disabled');
			else
				$('.surtools').removeAttr('disabled');
			if (typeof (data.log_messages)  != 'undefined' && data.log_messages.length > 0) {
				var log=$('#log_messages');
				log[0].value =  data.log_messages.join("\n")+"\n";
				log.scrollTop(log[0].scrollHeight - log.height());
			}

			var audio = $('#audio')[0]
			if (data.active_track !== null && data.active_track != '' )   { // Есть активный трек
//				Запускаем звук, если он не запущен
				<?if ($user == 'psycho') { ?>
				if (audio.paused)   {
					audio.src = 'tracks/'+data.active_track.url;
					audio.play();
				}
				<? } ?>
				var track = data.active_track.track;
				$('#tracks input:radio').attr('disabled', 'disabled');
				$('#track_target').attr('disabled', 'disabled');
//          Выделим текущий
				$('#'+track+'_label').first().css({fontWeight: 'bold'});
//          Выберем целевую систему
				<? if ($user!='surgeon')    { ?>
				$('#track_target')[0].value = data.active_track.target;
				<? } ?>

//          Меняем кнопки
				$('#stop_track').show()
				$('#start_track').hide()
			}
			else    {   // Нет активного трека
				audio.pause();
				$('#tracks input:radio').attr('disabled', null);
				$('#track_target').attr('disabled', null);
//          Уберем выделение полужирным
				$('#tracks label').css({fontWeight: 'normal'});
//          Дизаблим все уже использованные треки
				for (var i=0; i<data.used_tracks.length; i++)   {
					$('#'+data.used_tracks[i]+'_radio').attr('disabled', 'disabled');
					$('#'+data.used_tracks[i]+'_label').css({fontStyle: 'italic', color: '#000000', backgroundColor: '#FFFFFF'});
				}
//          Меняем кнопки
				$('#stop_track').hide()
				$('#start_track').show()
			}

			if (typeof(data.messages)!='undefined' && data.messages.length>0)
				$('#main_message')[0].innerHTML = data.messages.join('<br>');

			// Перекрашиваем системы
			for (system in data.systems) {
				if (!data.systems.hasOwnProperty(system)) continue;
				var system_data = data.systems[system];
				if (typeof(system_data)=='undefined') continue;
				var col = system_data.color;
				col = "("+[Math.round(col[0]), Math.round(col[1]), Math.round(col[2])].join(', ')+")";
				$('#systems #'+system+'_stress')[0].innerHTML = system_data.stress == 1000 ? 'ОТКАЗ!' :Math.round(system_data.stress);
				$('#systems #'+system+'_data')[0].innerHTML = col+' - '+system_data.crit_range;
				$('#systems #'+system+'_bg').css({backgroundColor: system_data.html_color})
				if (system_data.color[0] + system_data.color[1] + system_data.color[2] >=220)
					$('#systems #'+system+'_bg').css({color: '#000000'})
				else
					$('#systems #'+system+'_bg').css({color: '#FFFFFF'})
				$('#systems #'+system+'_stress').css({backgroundColor: system_data.stress_color})
			}

		<? if ($user == 'overseer') {  ?>
//			$('#full_data')[0].innerHTML = data.full_data;
		<? }  ?>
		}

		function clickToolButton(id, target)    {
			data = {req: id};
			if (typeof(target) != 'undefined')
				data.target=target;
			$.ajax({
				url: '<?=$process_url;?>',
				data: data,
				success: function(data) {update_data($.parseJSON(data))}
			})
			if (id.substring(0,4) == 'sur_')
				$('.surtools').attr('disabled', 'disabled');
		}


		function refresh_data() {
			$.ajax({
				url: '<?=$process_url;?>',
				dataType: 'json',
				success: function(data){update_data(data)},
				timeout: 1200
			})
//			$.getJSON('<?=$process_url;?>', {}, function(data) {update_data(data)});
		}

		$('.menu_vert a').on('click', function(event) {
			var arr=this.id.split('_');
			clickToolButton('sur_'+arr[0], arr[1]);
			if (arr.length>1)   {
				$(this).parents('ul').first().hide();
			}
			return false
			}
		);
		$('.bts').on('click', function(event) {clickToolButton(this.id); return false});




		$('#start_operation').on('click', function() {
			$.ajax({
				url: '<?=$process_url;?>',
				data: {req: 'start_operation'},
				success: function(data) {
					var data= $.parseJSON(data); update_data(data)


					oper_int=setInterval(refresh_data, 1000);
				}
			})

			$('#start_operation_controls').hide();
		});
		$('#join_operation').on('click', function() {
			oper_int=setInterval(refresh_data, 1000);
			$('#start_operation_controls').hide();
		});

		$('#end_operation').on('click', function() {
			$.ajax({
				url: '<?=$process_url;?>',
				data: {req: 'end_operation'},
				success: function(data) {
					var data= $.parseJSON(data); update_data(data)
				}
			})
		});

		$('#start_track').on('click', start_track);
		$('#stop_track').on('click', stop_track);


		/*инициализация*/
		$('.menu_vert').liMenuVert({
			delayShow:200,		//Задержка перед появлением выпадающего меню (ms)
			delayHide:200	    //Задержка перед исчезанием выпадающего меню (ms)
		});

	});
</script>



<pre id=full_data></pre>