<?php
	$this->load->model('Ledger_model');
	if(!isset($ledger_start_date)&&!isset($ledger_end_date)){
		$ledger_start_date = '';
		$ledger_end_date = '';
	}
	
	if ( ! $print_preview)
	{
		echo form_open('report/ledgerst/' . $ledger_id);
		echo "<p>";
		echo form_input_ledger('ledger_id', $ledger_id);
		if($ledger_id > 0){
			echo "<br />".'<label for="ledger_start_date">Start Date: </label>';
			$start_data= array('name'=>'ledger_start_date','id'=>'ledger_start_date','value'=>$ledger_start_date);
			echo form_input($start_data);			
			echo "(e.g 'DD/MM/YYYY')";
			echo "<br />".'<label for="ledger_end_date">End Date: &nbsp;</label>';
			$end_data= array('name'=>'ledger_end_date','id'=>'ledger_end_date','value'=>$ledger_end_date);
			echo form_input($end_data);
			echo "(e.g 'DD/MM/YYYY')";
			echo "<br />";
		}
		echo " ";
		echo form_submit('submit', 'Show');
		echo "</p>";
		echo form_close();
	}
$pagination_counter = $this->config->item('row_count');
	/* Pagination configuration */
	if ( ! $print_preview)
	{
		$pagination_counter = $this->config->item('row_count');
		$page_count = (int)$this->uri->segment(4);
		$page_count = $this->input->xss_clean($page_count);
		if ( ! $page_count)
			$page_count = "0";
		$config['base_url'] = site_url('report/ledgerst/' . $ledger_id);
		$config['num_links'] = 10;
		$config['per_page'] = $pagination_counter;
		$config['uri_segment'] = 4;
		$config['total_rows'] = (int)$this->db->from('entries')->join('entry_items', 'entries.id = entry_items.entry_id')->where('entry_items.ledger_id', $ledger_id)->count_all_results();
		$config['full_tag_open'] = '<ul id="pagination-flickr">';
		$config['full_close_open'] = '</ul>';
		$config['num_tag_open'] = '<li>';
		$config['num_tag_close'] = '</li>';
		$config['cur_tag_open'] = '<li class="active">';
		$config['cur_tag_close'] = '</li>';
		$config['next_link'] = 'Next &#187;';
		$config['next_tag_open'] = '<li class="next">';
		$config['next_tag_close'] = '</li>';
		$config['prev_link'] = '&#171; Previous';
		$config['prev_tag_open'] = '<li class="previous">';
		$config['prev_tag_close'] = '</li>';
		$config['first_link'] = 'First';
		$config['first_tag_open'] = '<li class="first">';
		$config['first_tag_close'] = '</li>';
		$config['last_link'] = 'Last';
		$config['last_tag_open'] = '<li class="last">';
		$config['last_tag_close'] = '</li>';
		$this->pagination->initialize($config);
	}

	if ($ledger_id != 0)
	{
		list ($opbalance, $optype) = $this->Ledger_model->get_op_balance($ledger_id); /* Opening Balance */
		$clbalance = $this->Ledger_model->get_ledger_balance($ledger_id); /* Final Closing Balance */

		/* Ledger Summary */
		echo "<table class=\"ledger-summary\">";
		echo "<tr>";
		echo "<td><b>Opening Balance</b></td><td>" . convert_opening($opbalance, $optype) . "</td>";
		echo "</tr>";
		echo "<tr>";
		echo "<td><b>Closing Balance</b></td><td>" . convert_amount_dc($clbalance) . "</td>";
		echo "</tr>";
		echo "</table>";
		echo "<br />";		
		if ( ! $print_preview) {
			$this->db->select('entries.id as entries_id, entries.number as entries_number, entries.date as entries_date, entries.narration as entries_narration, entries.entry_type as entries_entry_type, entry_items.amount as entry_items_amount, entry_items.dc as entry_items_dc');
			$this->db->from('entries')->join('entry_items', 'entries.id = entry_items.entry_id')->where('entry_items.ledger_id', $ledger_id);
			if($ledger_start_date!=''||$ledger_end_date!=''){	
				$ledger_start_date=$this->Ledger_model->convertDate($ledger_start_date,'y-m-d');		
				$ledger_end_date=$this->Ledger_model->convertDate($ledger_end_date,'y-m-d');	
				$ledger_start_date=date('Y-m-d', strtotime($ledger_start_date));
				$ledger_end_date=date('Y-m-d', strtotime($ledger_end_date));
				$this->db->where("(entries.date BETWEEN '$ledger_start_date' AND '$ledger_end_date')");
			}
			$this->db->order_by('entries.date', 'asc')->order_by('entries.number', 'asc')->limit($pagination_counter, $page_count);			
			$ledgerst_q = $this->db->get();//echo $this->db->last_query();
			
			echo "<table border=0 cellpadding=5 class=\"simple-table ledgerst-table\">";

		echo "<thead><tr><th>Date</th><th>No.</th><th>Description</th><th>Type</th><th>Debit</th><th>Credit</th><th>Balance</th></tr></thead>";
		$odd_even = "odd";

		$cur_balance = 0;

		if ($page_count <= 0)
		{
			/* Opening balance */
			if ($optype == "D")
			{
				echo "<tr class=\"tr-balance\"><td colspan=6>Opening Balance</td><td>" . convert_opening($opbalance, $optype) . "</td></tr>";
				$cur_balance = float_ops($cur_balance, $opbalance, '+');
			} else {
				echo "<tr class=\"tr-balance\"><td colspan=6>Opening Balance</td><td>" . convert_opening($opbalance, $optype) . "</td></tr>";
				$cur_balance = float_ops($cur_balance, $opbalance, '-');
			}
		} else {
			/* Opening balance */
			if ($optype == "D")
			{
				$cur_balance = float_ops($cur_balance, $opbalance, '+');
			} else {
				$cur_balance = float_ops($cur_balance, $opbalance, '-');
			}

			/* Calculating previous balance */
			$this->db->select('entries.id as entries_id, entries.number as entries_number, entries.date as entries_date, entries.entry_type as entries_entry_type, entry_items.amount as entry_items_amount, entry_items.dc as entry_items_dc');
			$this->db->from('entries')->join('entry_items', 'entries.id = entry_items.entry_id')->where('entry_items.ledger_id', $ledger_id)->order_by('entries.date', 'asc')->order_by('entries.number', 'asc')->limit($page_count, 0);
			$prevbal_q = $this->db->get();
			foreach ($prevbal_q->result() as $row )
			{
				if ($row->entry_items_dc == "D")
					$cur_balance = float_ops($cur_balance, $row->entry_items_amount, '+');
				else
					$cur_balance = float_ops($cur_balance, $row->entry_items_amount, '-');
			}

			/* Show new current total */
			echo "<tr class=\"tr-balance\"><td colspan=6>Opening</td><td>" . convert_amount_dc($cur_balance) . "</td></tr>";
		}

		foreach ($ledgerst_q->result() as $row)
		{
			$current_entry_type = entry_type_info($row->entries_entry_type);

			echo "<tr class=\"tr-" . $odd_even . "\">";
			echo "<td>";
			echo date_mysql_to_php_display($row->entries_date);
			echo "</td>";
			echo "<td>";
			echo anchor('entry/view/' . $current_entry_type['label'] . '/' . $row->entries_id, full_entry_number($row->entries_entry_type, $row->entries_number), array('title' => 'View ' . ' Entry', 'class' => 'anchor-link-a'));
			echo "</td>";

			/* Getting opposite Ledger name */
			echo "<td>";
			echo $this->Ledger_model->get_opp_ledger_name($row->entries_id, $current_entry_type['label'], $row->entry_items_dc, 'html');
			if ($row->entries_narration)
				echo "<div class=\"small-font\">" . character_limiter($row->entries_narration, 50) . "</div>";
			echo "</td>";

			echo "<td>";
			echo $current_entry_type['name'];
			echo "</td>";
			if ($row->entry_items_dc == "D")
			{
				$cur_balance = float_ops($cur_balance, $row->entry_items_amount, '+');
				echo "<td align='right'>";
				//echo convert_dc($row->entry_items_dc);
				echo " ";
				echo number_format($row->entry_items_amount);
				echo "</td>";
				echo "<td></td>";
			} else {
				$cur_balance = float_ops($cur_balance, $row->entry_items_amount, '-');
				echo "<td></td>";
				echo "<td align='right'>";
				//echo convert_dc($row->entry_items_dc);
				echo " ";
				echo number_format($row->entry_items_amount);
				echo "</td>";
			}
			echo "<td align='right'>";
			echo convert_amount_dc($cur_balance);
			echo "</td>";
			echo "</tr>";
			$odd_even = ($odd_even == "odd") ? "even" : "odd";
		}

		/* Current Page Closing Balance */
		echo "<tr class=\"tr-balance\"><td colspan=6>Closing</td> <td align='right'>" .  convert_amount_dc($cur_balance) . "</td></tr>";
		echo "</table>";
			
			
		} else {
			$page_count = 0;
			$this->db->select('entries.id as entries_id, entries.number as entries_number, entries.date as entries_date, entries.narration as entries_narration, entries.entry_type as entries_entry_type, entry_items.amount as entry_items_amount, entry_items.dc as entry_items_dc');
			$this->db->from('entries')->join('entry_items', 'entries.id = entry_items.entry_id')->where('entry_items.ledger_id', $ledger_id);
			if($_SESSION['sdate']!=''||$_SESSION['edate']!=''){
				$sdate=$_SESSION['sdate'];
				$edate=$_SESSION['edate'];
				$sdate=$this->Ledger_model->convertDate($sdate,'y-m-d');		
				$edate=$this->Ledger_model->convertDate($edate,'y-m-d');	
				$sdate=date('Y-m-d', strtotime($sdate));
				$edate=date('Y-m-d', strtotime($edate));
				$this->db->where("(entries.date BETWEEN '$sdate' AND '$edate')");
			}
			$this->db->order_by('entries.date', 'asc')->order_by('entries.number', 'asc')->limit($pagination_counter, $page_count);			
			$ledgerst_q = $this->db->get();//echo $this->db->last_query();
			
			
			/*$this->db->select('entries.id as entries_id, entries.number as entries_number, entries.date as entries_date, entries.narration as entries_narration, entries.entry_type as entries_entry_type, entry_items.amount as entry_items_amount, entry_items.dc as entry_items_dc');
			$this->db->from('entries')->join('entry_items', 'entries.id = entry_items.entry_id')->where('entry_items.ledger_id', $ledger_id)->order_by('entries.date', 'asc')->order_by('entries.number', 'asc');
			$ledgerst_q = $this->db->get();*/
			
				echo "<table width='100%' border=0 cellpadding=5 class=\"simple-table ledgerst-table\">";

		echo "<thead><tr><th>Date</th><th>No.</th><th>Description</th><th>Debit</th><th>Credit</th><th>Balance</th></tr></thead>";
		$odd_even = "odd";

		$cur_balance = 0;

		if ($page_count <= 0)
		{
			/* Opening balance */
			if ($optype == "D")
			{
				echo "<tr class=\"tr-balance\"><td colspan=5>Opening Balance</td><td align='center'>" . convert_opening($opbalance, $optype) . "</td></tr>";
				$cur_balance = float_ops($cur_balance, $opbalance, '+');
			} else {
				echo "<tr class=\"tr-balance\"><td colspan=5>Opening Balance</td><td align='center'>" . convert_opening($opbalance, $optype) . "</td></tr>";
				$cur_balance = float_ops($cur_balance, $opbalance, '-');
			}
		} else {
			/* Opening balance */
			if ($optype == "D")
			{
				$cur_balance = float_ops($cur_balance, $opbalance, '+');
			} else {
				$cur_balance = float_ops($cur_balance, $opbalance, '-');
			}

			/* Calculating previous balance */
			$this->db->select('entries.id as entries_id, entries.number as entries_number, entries.date as entries_date, entries.entry_type as entries_entry_type, entry_items.amount as entry_items_amount, entry_items.dc as entry_items_dc');
			$this->db->from('entries')->join('entry_items', 'entries.id = entry_items.entry_id')->where('entry_items.ledger_id', $ledger_id)->order_by('entries.date', 'asc')->order_by('entries.number', 'asc')->limit($page_count, 0);
			$prevbal_q = $this->db->get();
			foreach ($prevbal_q->result() as $row )
			{
				if ($row->entry_items_dc == "D")
					$cur_balance = float_ops($cur_balance, $row->entry_items_amount, '+');
				else
					$cur_balance = float_ops($cur_balance, $row->entry_items_amount, '-');
			}

			/* Show new current total */
			echo "<tr class=\"tr-balance\"><td colspan=6>Opening</td><td align='center'>" . convert_amount_dc($cur_balance) . "</td></tr>";
		}

		foreach ($ledgerst_q->result() as $row)
		{
			$current_entry_type = entry_type_info($row->entries_entry_type);

			echo "<tr class=\"tr-" . $odd_even . "\">";
			echo "<td>";
			echo date_mysql_to_php_display($row->entries_date);
			echo "</td>";
			echo "<td align='center'>";
			echo anchor('entry/view/' . $current_entry_type['label'] . '/' . $row->entries_id, full_entry_number($row->entries_entry_type, $row->entries_number), array('title' => 'View ' . ' Entry', 'class' => 'anchor-link-a'));
			echo "</td>";

			/* Getting opposite Ledger name */
			echo "<td>";
			echo $this->Ledger_model->get_opp_ledger_name($row->entries_id, $current_entry_type['label'], $row->entry_items_dc, 'html');
			if ($row->entries_narration)
				echo " " . character_limiter($row->entries_narration, 50);
			echo "</td>";

			/*echo "<td>";
			echo $current_entry_type['name'];
			echo "</td>";*/
			if ($row->entry_items_dc == "D")
			{
				$cur_balance = float_ops($cur_balance, $row->entry_items_amount, '+');
				echo "<td align='center'>";
				//echo convert_dc($row->entry_items_dc);
				echo " ";
				echo number_format($row->entry_items_amount);
				echo "</td>";
				echo "<td></td>";
			} else {
				$cur_balance = float_ops($cur_balance, $row->entry_items_amount, '-');
				echo "<td align='center'></td>";
				echo "<td align='center'>";
				//echo convert_dc($row->entry_items_dc);
				echo " ";
				echo number_format($row->entry_items_amount);
				echo "</td>";
			}
			echo "<td align='center'>";
			echo convert_amount_dc($cur_balance);
			echo "</td>";
			echo "</tr>";
			$odd_even = ($odd_even == "odd") ? "even" : "odd";
		}

		/* Current Page Closing Balance */
		echo "<tr class=\"tr-balance\"><td colspan=5>Closing Balance</td> <td align='center'>" .  convert_amount_dc($cur_balance) . "</td></tr>";
		echo "</table>";
			
			
		}

		
		
		
		
		
		
		
		
		
	}
?>
<?php if ( ! $print_preview) { ?>
<div id="pagination-container"><?php echo $this->pagination->create_links(); ?></div>
<?php } ?>
<script type="application/javascript">
	$(function() {		
        $("#ledger_start_date").datepicker({dateFormat: 'yy-mm-dd'});
        $("#ledger_end_date").datepicker({dateFormat: 'yy-mm-dd'});
	});
</script>
