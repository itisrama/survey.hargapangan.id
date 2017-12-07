<?php
// No direct access
defined('_JEXEC') or die('Restricted access');

$objPHPExcel = $this->objPHPExcel;

$period = JHtml::date($this->state->get('filter.start_date'), 'd F Y').' - ';
$period .= JHtml::date($this->state->get('filter.end_date'), 'd F Y');

$countProvinces	= 0;
$countRegencies	= 0;
$countMarkets	= 0;
foreach($this->items as $province) {
	$countProvinces++;
	foreach ($province->children as $regency) {
		$countRegencies ++;
		$countMarkets += $regency->count;
	}
}

$selectedProv = array_intersect_key($this->provinces, array_keys($this->items));

// Styles
$style			= new stdClass();
$style->center	= array('alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER));
$style->right	= array('alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_RIGHT));
$style->left	= array('alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT));
$style->bold	= array('font' => array('bold'  => true));
$style->percent	= array('code' => PHPExcel_Style_NumberFormat::FORMAT_PERCENTAGE);

$rowNum = 1;

// Title
$objPHPExcel->getActiveSheet()->mergeCells('A'.$rowNum.':N'.$rowNum);
$objPHPExcel->getActiveSheet()->SetCellValue('A'.$rowNum, JText::_('COM_GTPIHPS_HEADER_PERFORMANCE_REPORT'));
$objPHPExcel->getActiveSheet()->getStyle('A'.$rowNum)->getFont()->setSize(14);
$objPHPExcel->getActiveSheet()->getStyle('A'.$rowNum)->applyFromArray($style->bold);

$rowNum ++;

// Filter period
$objPHPExcel->getActiveSheet()->mergeCells('A'.$rowNum.':B'.$rowNum);
$objPHPExcel->getActiveSheet()->SetCellValue('A'.$rowNum, JText::_('COM_GTPIHPS_FIELD_PERIOD'));
$objPHPExcel->getActiveSheet()->mergeCells('C'.$rowNum.':N'.$rowNum);
$objPHPExcel->getActiveSheet()->SetCellValue('C'.$rowNum, $period);

$rowNum++;

// Filter period counted
$objPHPExcel->getActiveSheet()->mergeCells('A'.$rowNum.':B'.$rowNum);
$objPHPExcel->getActiveSheet()->SetCellValue('A'.$rowNum, JText::_('COM_GTPIHPS_FIELD_COUNTED_PERIOD'));
$objPHPExcel->getActiveSheet()->mergeCells('C'.$rowNum.':N'.$rowNum);
$objPHPExcel->getActiveSheet()->SetCellValue('C'.$rowNum, sprintf(JText::_('COM_GTPIHPS_N_DAYS'), $this->dayCount));

$rowNum++;

// Filter region
$objPHPExcel->getActiveSheet()->mergeCells('A'.$rowNum.':B'.$rowNum);
$objPHPExcel->getActiveSheet()->SetCellValue('A'.$rowNum, JText::_('COM_GTPIHPS_FIELD_PROVINCE'));

foreach (array_chunk($selectedProv, 5) as $provinces) {
	$objPHPExcel->getActiveSheet()->mergeCells('C'.$rowNum.':N'.$rowNum);
	$objPHPExcel->getActiveSheet()->SetCellValue('C'.$rowNum, implode(', ', $provinces));
	$rowNum++;
}

// Title Meta
$objPHPExcel->getActiveSheet()->mergeCells('A'.$rowNum.':N'.$rowNum);
$objPHPExcel->getActiveSheet()->SetCellValue('A'.$rowNum, JText::_('COM_GTPIHPS_HEADER_PERFORMANCE_REPORT_META'));
$objPHPExcel->getActiveSheet()->getStyle('A'.$rowNum)->getFont()->setSize(12);
$objPHPExcel->getActiveSheet()->getStyle('A'.$rowNum)->applyFromArray($style->bold);

$rowNum ++;

// Total provinsi
$objPHPExcel->getActiveSheet()->mergeCells('A'.$rowNum.':B'.$rowNum);
$objPHPExcel->getActiveSheet()->SetCellValue('A'.$rowNum, JText::_('COM_GTPIHPS_FIELD_PROVINCE'));
$objPHPExcel->getActiveSheet()->mergeCells('C'.$rowNum.':N'.$rowNum);
$objPHPExcel->getActiveSheet()->SetCellValue('C'.$rowNum, $countProvinces);
$objPHPExcel->getActiveSheet()->getStyle('C'.$rowNum.':N'.$rowNum)->applyFromArray($style->left);

$rowNum++;

// Total kab/kota
$objPHPExcel->getActiveSheet()->mergeCells('A'.$rowNum.':B'.$rowNum);
$objPHPExcel->getActiveSheet()->SetCellValue('A'.$rowNum, JText::_('COM_GTPIHPS_FIELD_REGENCY'));
$objPHPExcel->getActiveSheet()->mergeCells('C'.$rowNum.':N'.$rowNum);
$objPHPExcel->getActiveSheet()->SetCellValue('C'.$rowNum, $countRegencies);
$objPHPExcel->getActiveSheet()->getStyle('C'.$rowNum.':N'.$rowNum)->applyFromArray($style->left);

$rowNum++;

// Total pasar
$objPHPExcel->getActiveSheet()->mergeCells('A'.$rowNum.':B'.$rowNum);
$objPHPExcel->getActiveSheet()->SetCellValue('A'.$rowNum, JText::_('COM_GTPIHPS_FIELD_MARKET'));
$objPHPExcel->getActiveSheet()->mergeCells('C'.$rowNum.':N'.$rowNum);
$objPHPExcel->getActiveSheet()->SetCellValue('C'.$rowNum, $countMarkets);
$objPHPExcel->getActiveSheet()->getStyle('C'.$rowNum.':N'.$rowNum)->applyFromArray($style->left);

$rowNum++;
$rowNum++;


// Header stylin
$objPHPExcel->getActiveSheet()->getStyle('A'.$rowNum.':'.'N'.$rowNum)->applyFromArray($style->center);
$objPHPExcel->getActiveSheet()->getStyle('A'.$rowNum.':'.'N'.$rowNum)->applyFromArray($style->bold);

// Header writin
$objPHPExcel->getActiveSheet()->SetCellValue('A'.$rowNum, JText::_('COM_GTPIHPS_FIELD_NUM'));
$objPHPExcel->getActiveSheet()->SetCellValue('B'.$rowNum, JText::_('COM_GTPIHPS_FIELD_PROVINCE_PERFORMANCE'));
$objPHPExcel->getActiveSheet()->SetCellValue('C'.$rowNum, JText::_('COM_GTPIHPS_FIELD_TOTAL_DATA'));
$objPHPExcel->getActiveSheet()->SetCellValue('D'.$rowNum, JText::_('COM_GTPIHPS_FIELD_ON_TIME'));
$objPHPExcel->getActiveSheet()->SetCellValue('E'.$rowNum, JText::_('COM_GTPIHPS_FIELD_LATE'));
$objPHPExcel->getActiveSheet()->SetCellValue('F'.$rowNum, JText::_('COM_GTPIHPS_FIELD_REGENCY_PERFORMANCE'));
$objPHPExcel->getActiveSheet()->SetCellValue('G'.$rowNum, JText::_('COM_GTPIHPS_FIELD_TOTAL_DATA'));
$objPHPExcel->getActiveSheet()->SetCellValue('H'.$rowNum, JText::_('COM_GTPIHPS_FIELD_ON_TIME'));
$objPHPExcel->getActiveSheet()->SetCellValue('I'.$rowNum, JText::_('COM_GTPIHPS_FIELD_LATE'));
$objPHPExcel->getActiveSheet()->SetCellValue('J'.$rowNum, JText::_('COM_GTPIHPS_FIELD_MARKET_PERFORMANCE'));
$objPHPExcel->getActiveSheet()->SetCellValue('K'.$rowNum, JText::_('COM_GTPIHPS_FIELD_TOTAL_DATA'));
$objPHPExcel->getActiveSheet()->SetCellValue('L'.$rowNum, JText::_('COM_GTPIHPS_FIELD_ON_TIME'));
$objPHPExcel->getActiveSheet()->SetCellValue('M'.$rowNum, JText::_('COM_GTPIHPS_FIELD_LATE'));
$objPHPExcel->getActiveSheet()->SetCellValue('N'.$rowNum, JText::_('COM_GTPIHPS_FIELD_DESCRIPTION'));

$objPHPExcel->getActiveSheet()->getRowDimension($rowNum)->setRowHeight('30px');

// Wrapping
$objPHPExcel->getActiveSheet()->getStyle('A'.$rowNum.':'.'N'.$rowNum)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

$rowDataStart = $rowNum+1;
// Add the data
$i = 1;
foreach($this->items as $province){
	$rowNum++;

	$rowNum2 = $rowNum + $province->count -1;
	$objPHPExcel->getActiveSheet()->mergeCells('A'.$rowNum.':A'.$rowNum2);
	$objPHPExcel->getActiveSheet()->mergeCells('B'.$rowNum.':B'.$rowNum2);
	$objPHPExcel->getActiveSheet()->mergeCells('C'.$rowNum.':C'.$rowNum2);
	$objPHPExcel->getActiveSheet()->mergeCells('D'.$rowNum.':D'.$rowNum2);
	$objPHPExcel->getActiveSheet()->mergeCells('E'.$rowNum.':E'.$rowNum2);
	
	$objPHPExcel->getActiveSheet()->SetCellValue('A'.$rowNum, $i++);
	$objPHPExcel->getActiveSheet()->SetCellValue('B'.$rowNum, $province->name);
	$objPHPExcel->getActiveSheet()->SetCellValue('C'.$rowNum, $province->desc);
	$objPHPExcel->getActiveSheet()->SetCellValue('D'.$rowNum, $province->ontime/100);
	$objPHPExcel->getActiveSheet()->SetCellValue('E'.$rowNum, $province->late/100);
	$objPHPExcel->getActiveSheet()->getStyle('D'.$rowNum)->getNumberFormat()->applyFromArray($style->percent);
	$objPHPExcel->getActiveSheet()->getStyle('E'.$rowNum)->getNumberFormat()->applyFromArray($style->percent);
	
	$j = 0;
	foreach($province->children as $regency){
		if($j>0) $rowNum++;
		$j++;

		$rowNum3 = $rowNum + $regency->count -1;

		$objPHPExcel->getActiveSheet()->mergeCells('F'.$rowNum.':F'.$rowNum3);
		$objPHPExcel->getActiveSheet()->mergeCells('G'.$rowNum.':G'.$rowNum3);
		$objPHPExcel->getActiveSheet()->mergeCells('H'.$rowNum.':H'.$rowNum3);
		$objPHPExcel->getActiveSheet()->mergeCells('I'.$rowNum.':I'.$rowNum3);

		$objPHPExcel->getActiveSheet()->SetCellValue('F'.$rowNum, $regency->name);
		$objPHPExcel->getActiveSheet()->SetCellValue('G'.$rowNum, $regency->desc);
		$objPHPExcel->getActiveSheet()->SetCellValue('H'.$rowNum, $regency->ontime/100);
		$objPHPExcel->getActiveSheet()->SetCellValue('I'.$rowNum, $regency->late/100);
		$objPHPExcel->getActiveSheet()->getStyle('H'.$rowNum)->getNumberFormat()->applyFromArray($style->percent);
		$objPHPExcel->getActiveSheet()->getStyle('I'.$rowNum)->getNumberFormat()->applyFromArray($style->percent);

		$k = 0;
		foreach($regency->children as $market){
			if($k>0) $rowNum++;
			$k++;

			$objPHPExcel->getActiveSheet()->SetCellValue('J'.$rowNum, $market->name);
			$objPHPExcel->getActiveSheet()->SetCellValue('K'.$rowNum, $market->desc);
			$objPHPExcel->getActiveSheet()->SetCellValue('L'.$rowNum, $market->ontime/100);
			$objPHPExcel->getActiveSheet()->SetCellValue('M'.$rowNum, $market->late/100);
			$objPHPExcel->getActiveSheet()->SetCellValue('N'.$rowNum, '');
			$objPHPExcel->getActiveSheet()->getStyle('L'.$rowNum)->getNumberFormat()->applyFromArray($style->percent);
			$objPHPExcel->getActiveSheet()->getStyle('M'.$rowNum)->getNumberFormat()->applyFromArray($style->percent);
		}
	}
}
$objPHPExcel->getActiveSheet()->getStyle('A'.($rowDataStart-1).':'.'N'.$rowNum)->getAlignment()->setWrapText(true);

// Borders the table
$styleBorder = array(
	'borders' => array(
		'allborders' => array(
			'style' => PHPExcel_Style_Border::BORDER_THIN
		)
	)
);

$objPHPExcel->getActiveSheet()->getStyle('A'.($rowDataStart-1).':'.'N'.$rowNum)->applyFromArray($styleBorder);
$objPHPExcel->getActiveSheet()->getStyle('A'.($rowDataStart-1).':'.'M'.$rowNum)->applyFromArray($style->center);
$objPHPExcel->getActiveSheet()->getStyle('B'.($rowDataStart-1).':'.'B'.$rowNum)->applyFromArray($style->left);
$objPHPExcel->getActiveSheet()->getStyle('F'.($rowDataStart-1).':'.'F'.$rowNum)->applyFromArray($style->left);
$objPHPExcel->getActiveSheet()->getStyle('J'.($rowDataStart-1).':'.'J'.$rowNum)->applyFromArray($style->left);
$objPHPExcel->getActiveSheet()->getStyle('A'.($rowDataStart-1).':'.'N'.($rowDataStart-1))->applyFromArray(
	array('borders' => array('bottom' => array('style' => PHPExcel_Style_Border::BORDER_THICK)))
);
$objPHPExcel->getActiveSheet()->getStyle('A'.($rowDataStart).':'.'M'.$rowNum)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_TOP);

$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(5);
$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(25);
$objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(9);
$objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(9);
$objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(9);
$objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(25);
$objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(9);
$objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth(9);
$objPHPExcel->getActiveSheet()->getColumnDimension('I')->setWidth(9);
$objPHPExcel->getActiveSheet()->getColumnDimension('J')->setWidth(25);
$objPHPExcel->getActiveSheet()->getColumnDimension('K')->setWidth(9);
$objPHPExcel->getActiveSheet()->getColumnDimension('L')->setWidth(9);
$objPHPExcel->getActiveSheet()->getColumnDimension('M')->setWidth(9);
$objPHPExcel->getActiveSheet()->getColumnDimension('N')->setWidth(30);
?>
