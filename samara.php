<?	require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
	CModule::IncludeModule('iblock');
	$dom = new DOMDocument();
	
	function stripWhitespaces($string) {
		$old_string = $string;
		$string = strip_tags($string);
		$string = preg_replace('/([^\pL\pN\pP\pS\pZ])|([\xC2\xA0])/u', ' ', $string);
		$string = str_replace('  ',' ', $string);
		$string = trim($string);

		if ($string === $old_string) {
			return $string;
		} else {
			return stripWhitespaces($string); 
		}
	}
	
	function isMon($string) {
		if (strpos(strtolower($string), 'янва') !== false) return '01';
		if (strpos(strtolower($string), 'февр') !== false) return '02';
		if (strpos(strtolower($string), 'март') !== false) return '03';
		if (strpos(strtolower($string), 'апрел') !== false) return '04';
		if (strpos(strtolower($string), 'мая') !== false || strpos(strtolower($string), 'май') !== false) return '05';
		if (strpos(strtolower($string), 'июн') !== false) return '06';
		if (strpos(strtolower($string), 'июл') !== false) return '07';
		if (strpos(strtolower($string), 'авгус') !== false) return '08';
		if (strpos(strtolower($string), 'сентя') !== false) return '09';
		if (strpos(strtolower($string), 'октя') !== false) return '10';
		if (strpos(strtolower($string), 'нояб') !== false) return '11';
		if (strpos(strtolower($string), 'декаб') !== false) return '12';
	}
	
	function isTime($string) {
		$string = preg_replace("/\s+/", " ", $string);
		$arr = explode(" ", $string);
		foreach ($arr as $slovo) {
			if (strpos(strtolower($slovo), ':') !== false) return $slovo;
		}
	}
	
	function isDay($string) {
		$string = trim(preg_replace("/\s+/", " ", $string));
		$arr = explode(" ", $string);
		if (strlen($arr[0]) == 1) return '0' . $arr[0];
		if (strlen($arr[0]) == 2) return $arr[0];
	}
	
	function isMesto($string) {
		list(, $mesto) = explode('час.', $string);
		$mesto = trim($mesto);
		$mesto = trim(preg_replace("/\s+/", " ", $mesto));
		if (strlen($mesto) > 0) {
			$mesto = trim($mesto, '()') . ')';
		}
		return $mesto;
	}
	
	$week = [];
	//$week = array('2017-Апрель-4', '2017-Май-5', '2017-Июнь-4', '2017-Июль-5', '2017-Август-4', '2017-Сентябрь-4', '2017-Октябрь-5', '2017-Ноябрь-4', '2017-Декабрь-4');
	//$week = array_merge($week, array('2018-Январь-3', '2018-Февраль-4', '2018-Март-4', '2018-Апрель-1', '2018-Июнь-1', '2018-Июль-2'));
	$week = array_merge($week, array('2019-Июнь-1'));
	echo "<pre>week: "; print_r($week); echo "</pre>\n";
	
	$monn_arr = array('Январь' => '01', 'Февраль' => '02', 'Март' => '03', 'Апрель' => '04', 'Май' => '05', 'Июнь' => '06', 'Июль' => '07', 'Август' => '08', 'Сентябрь' => '09', 'Октябрь' => '10', 'Ноябрь' => '11', 'Декабрь' => '12', );
	
	for ($i = 0; $i < count($week); $i++) {
		list($year, $mon, $p) = explode('-', $week[$i]);
		for ($j = 1; $j <= $p; $j++) {
			echo "<pre>url-------------------------------------------------------------------------> "; print_r("https://kirovskiy.gordumasamara.ru/week/$year/$monn_arr[$mon]/?period=$j"); echo "</pre>\n";
			
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, "https://kirovskiy.gordumasamara.ru/week/$year/$monn_arr[$mon]/?period=$j");
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			$res = curl_exec($ch);
			curl_close($ch);

			$dom->loadHTML($res);
			$xpath = new DomXpath($dom);
			
			$li = $xpath->query('//*[@class="active"]')->item(2);
				
			$res = CIBlockSection::GetList(Array(), Array('IBLOCK_ID' => 59), false, Array('ID', 'NAME'));
			while($arRes = $res->Fetch()) {
				if (str_replace(' ', '', $arRes['NAME']) == str_replace(' ', '', $li->textContent)) {
					$section_id = $arRes['ID'];
					break;
				}
			}
			echo "<pre>section_id: "; print_r($section_id . '/' . trim($li->textContent)); echo "/</pre>\n";
			
			$href = $xpath->query('//*[@class="plan-file"]')->item(0)->getElementsByTagName('a')->item(0)->getAttribute('href');
			echo "<pre>href: "; print_r($href); echo "</pre>\n";
			
			$bs = new CIBlockSection;
			$arFields = Array(
				'IBLOCK_ID' => 59,
				'UF_FILE' => CFile::MakeFileArray($href),
			);
			$res = $bs->Update($section_id, $arFields);
			if(!$res){
				echo 'CIBlockSection Update:' . $bs->LAST_ERROR . '<br>';
			}

			foreach ($dom->getElementsByTagName('table')->item(0)->getElementsByTagName('tr') as $k => $tr) {
				if ($k == 0) continue; 
				//echo "<pre>tr: "; print_r($tr->textContent); echo "</pre>\n";

				$td_arr = Array();
				foreach ($tr->getElementsByTagName('td') as $td) {
					$td = strip_tags($td->textContent);
					$td = stripWhitespaces($td);
					$td_arr[] = $td;
				}
				echo "<pre>td_arr: "; print_r($td_arr); echo "</pre>\n";
				
				$timeZone = new \DateTimeZone('Europe/Saratov');
				$dt = '';
				if (strpos(strtolower($td_arr[0]), ':') !== false && strpos(strtolower($td_arr[0]), 'час') !== false) {
					$dt = $year . '-' . isMon($td_arr[0]) . '-' .  isDay($td_arr[0]) . ' ' . isTime($td_arr[0]) . ':00';
					echo "<pre>dt: "; print_r($dt); echo "</pre>\n";
					//$dt = strtotime($dt);
					echo "<pre>dt: "; print_r($dt); echo "</pre>\n";
					$dt = new \Bitrix\Main\Type\DateTime($dt, 'Y-m-d H:i:s', $timeZone);
					echo "<pre>dt: "; print_r($dt); echo "</pre>\n";
				}
				
				$mesto = '';
				$mesto = isMesto($td_arr[0]);
				echo "<pre>mesto:"; print_r($mesto); echo "</pre>\n";
				
				$PROP[361] = $td_arr[0];
				$PROP[314] = $td_arr[2];
				$PROP[360] = $td_arr[3];
				$PROP[362] = "$year-$monn_arr[$mon]-$j-$k";
				$PROP[312] = $dt;
				$PROP[313] = $mesto;

				$el = new CIBlockElement;
				$arLoadProductArray = Array(
					"PROPERTY_VALUES" => $PROP,
					"NAME" => preg_replace("/\s+/", " ", $td_arr[1]),
					"ACTIVE" => "Y",
					"IBLOCK_ID" => 59,
					'IBLOCK_SECTION_ID' => $section_id,
					"CODE" => Cutil::translit($td_arr[1], 'ru', array("replace_space" => "-", "replace_other" => "-")),
				);
				if (empty($arLoadProductArray['NAME'])) $arLoadProductArray['NAME'] = "$year-$monn_arr[$mon]-$j-$k";

				$res = CIBlockElement::GetList(Array(), Array('IBLOCK_ID' => 59, 'PROPERTY_ID_CODE' => "$year-$monn_arr[$mon]-$j-$k"), false, false, Array('ID'));
				while($arFields = $res->Fetch()) {
					$element_id = trim($arFields['ID']); 
				}
				echo "<pre>element_id: "; print_r($element_id); echo "</pre>\n";
				
				if (empty($element_id)) {
					if($ress = $el->Add($arLoadProductArray)) {
						echo 'CIBlockElement Add: ' . $ress . '<br>';
					}
					else {
						echo 'CIBlockElement Error Add: ' . $el->LAST_ERROR . '<br>';
					}
				} else {
					if($ress = $el->Update($element_id, $arLoadProductArray)) {
						echo 'CIBlockElement Update: ' . $element_id . '<br>';
					}
					else {
						echo 'CIBlockElement Error Update: ' . $el->LAST_ERROR . '<br>';
					}
				}
				
			}
		}
		echo '<hr>';
		//die();
	}