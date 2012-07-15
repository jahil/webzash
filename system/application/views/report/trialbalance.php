<style>
	table.simple-table td{
		border-bottom: 1px solid #BBBBBB;
	}
	table.simple-table th{border-bottom: 1px solid #BBBBBB;border-top: 1px solid #BBBBBB;}
</style>
<?php
//echo phpinfo();
	$this->load->library('accountlist');
	echo "<table width='100%'>";
	echo "<tr valign=\"top\">";
	$asset = new Accountlist();
	echo "<td>";
	$asset->init(0);
	echo "<table width='100%' border=0 cellpadding=5 cellspacing=\"0\" class=\"simple-table\">";
	echo "<thead><tr><th width='30%'>Ledger Account</th><th align='right'>Opening Balance (Dr)</th><th align='right'>Opening Balance (Cr)</th><th align='right'>Current Balance (Dr)</th><th align='right'>Current Balance (Cr)</th><th align='right'>Closing Balance (Dr)</th><th align='right'>Closing Balance (Cr)</th></tr></thead>";
	$asset->account_st_short(-1,'trial');
	$this->load->model('Ledger_model');
	$dr_op_total=$this->Ledger_model->get_trailTotal('drop');
	$cr_op_total=$this->Ledger_model->get_trailTotal('crop');
	$dr_cu_total=$this->Ledger_model->get_trailTotal('drcu');
	$cr_cu_total=$this->Ledger_model->get_trailTotal('crcu');
	$dr_cl_total=$this->Ledger_model->get_trailTotal('drcl');
	$cr_cl_total=$this->Ledger_model->get_trailTotal('crcl');
	echo "<tr class=\"tr-total\"><td><b>TOTAL</b> ";
	if (float_ops($dr_cl_total, -$cr_cl_total, '=='))
		echo "<img src=\"" . asset_url() . "images/icons/match.png\">";
	else
		echo "<img src=\"" . asset_url() . "images/icons/nomatch.png\">";
	echo "</td>
	<td align='right'><b>" . convert_cur($dr_op_total) . "</b></td>
	<td align='right'><b>" . convert_cur($cr_op_total) . "</b></td>
	<td align='right'><b>" . convert_cur($dr_cu_total) . "</b></td>
	<td align='right'><b>" . convert_cur($cr_cu_total) . "</b></td>
	<td align='right'><b>" . convert_cur($dr_cl_total) . "</b></td>
	<td align='right'><b>" . convert_cur(-$cr_cl_total) . "</b></td>
	</tr>";
	echo "</table>";
	echo "</td>";
	echo "</tr>";	
	echo "</table>";	
?>
<?php
	/*$temp_dr_total = 0;
	$temp_cr_total = 0;

	echo "<table border=0 cellpadding=5 class=\"simple-table trial-balance-table\">";
	echo "<thead><tr><th>Ledger Account</th><th>O/P Balance</th><th>C/L Balance</th><th>Dr Total</th><th>Cr Total</th></tr></thead>";
	$this->load->model('Ledger_model');
	$all_ledgers = $this->Ledger_model->get_all_ledgers();
	$odd_even = "odd";
	foreach ($all_ledgers as $ledger_id => $ledger_name)
	{
		if ($ledger_id == 0) continue;
		echo "<tr class=\"tr-" . $odd_even . "\">";

		echo "<td>";
		echo  anchor('report/ledgerst/' . $ledger_id, $ledger_name, array('title' => $ledger_name . ' Ledger Statement', 'class' => 'anchor-link-a'));
		echo "</td>";

		echo "<td>";
		list ($opbal_amount, $opbal_type) = $this->Ledger_model->get_op_balance($ledger_id);
		echo convert_opening($opbal_amount, $opbal_type);
		echo "</td>";

		echo "<td>";
		$clbal_amount = $this->Ledger_model->get_ledger_balance($ledger_id);
		echo convert_amount_dc($clbal_amount);
		echo "</td>";

		echo "<td>";
		$dr_total = $this->Ledger_model->get_dr_total($ledger_id);
		if ($dr_total)
		{
			echo convert_cur($dr_total);
			$temp_dr_total = float_ops($temp_dr_total, $dr_total, '+');
		} else {
			echo "0";
		}
		echo "</td>";
		echo "<td>";
		$cr_total = $this->Ledger_model->get_cr_total($ledger_id);
		if ($cr_total)
		{
			echo convert_cur($cr_total);
			$temp_cr_total = float_ops($temp_cr_total, $cr_total, '+');
		} else {
			echo "0";
		}
		echo "</td>";
		echo "</tr>";
		$odd_even = ($odd_even == "odd") ? "even" : "odd";
	}
	echo "<tr class=\"tr-total\"><td colspan=3>TOTAL ";
	if (float_ops($temp_dr_total, $temp_cr_total, '=='))
		echo "<img src=\"" . asset_url() . "images/icons/match.png\">";
	else
		echo "<img src=\"" . asset_url() . "images/icons/nomatch.png\">";
	echo "</td><td>Dr " . convert_cur($temp_dr_total) . "</td><td>Cr " . convert_cur($temp_cr_total) . "</td></tr>";
	echo "</table>";*/?>

