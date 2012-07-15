<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Accountlist
{
	var $id = 0;
	var $name = "";
	var $total = 0;
	var $optype = "";
	var $opbalance = 0;
	var $children_groups = array();
	var $children_ledgers = array();
	var $counter = 0;
	public static $temp_max = 0;
	public static $max_depth = 0;
	public static $csv_data = array();
	public static $csv_row = 0;

	function Accountlist()
	{
		return;
	}

	function init($id)
	{
		$CI =& get_instance();
		if ($id == 0)
		{
			$this->id = 0;
			$this->name = "None";
			$this->total = 0;

		} else {
			$CI->db->from('groups')->where('id', $id)->limit(1);
			$group_q = $CI->db->get();
			$group = $group_q->row();
			$this->id = $group->id;
			$this->name = $group->name;
			$this->total = 0;
		}
		$this->add_sub_ledgers();
		$this->add_sub_groups();
	}

	function add_sub_groups()
	{
		$CI =& get_instance();
		$CI->db->from('groups')->where('parent_id', $this->id);
		$child_group_q = $CI->db->get();
		$counter = 0;
		foreach ($child_group_q->result() as $row)
		{
			$this->children_groups[$counter] = new Accountlist();
			$this->children_groups[$counter]->init($row->id);
			$this->total = float_ops($this->total, $this->children_groups[$counter]->total, '+');
			$counter++;
		}
	}
	function add_sub_ledgers()
	{
		$CI =& get_instance();
		$CI->load->model('Ledger_model');
		$CI->db->from('ledgers')->where('group_id', $this->id);
		$child_ledger_q = $CI->db->get();
		$counter = 0;
		foreach ($child_ledger_q->result() as $row)
		{
			$this->children_ledgers[$counter]['id'] = $row->id;
			$this->children_ledgers[$counter]['name'] = $row->name;
			$this->children_ledgers[$counter]['total'] = $CI->Ledger_model->get_ledger_balance($row->id);
			list ($this->children_ledgers[$counter]['opbalance'], $this->children_ledgers[$counter]['optype']) = $CI->Ledger_model->get_op_balance($row->id);
			$this->total = float_ops($this->total, $this->children_ledgers[$counter]['total'], '+');
			$counter++;
		}
	}

	/* Display Account list in Balance sheet and Profit and Loss st */
	function account_st_short($c = 0,$st=''){
		$this->counter = $c;
		if($st==''){
			if ($this->id != 0){
				if($this->total!=0){
					echo "<tr class=\"tr-group\">";
					echo "<td class=\"td-group\">";
					echo $this->print_space($this->counter);
					echo "&nbsp;" .  $this->name;
					echo "</td>";
				echo "<td align=\"right\"><b>" . convert_amount_to_dc($this->total) .  "</b></td>";
				//echo "<td align=\"right\">" . convert_amount_to_dc($this->total) . $this->print_space($this->counter) . "</td>";
					echo "</tr>";
				}
			}
			foreach ($this->children_groups as $id => $data){
				$this->counter++;
				$data->account_st_short($this->counter);
				$this->counter--;
			}
			if (count($this->children_ledgers) > 0){
				$this->counter++;
				foreach ($this->children_ledgers as $id => $data){
					if($data['total']!=0){
						echo "<tr class=\"tr-ledger\">";
						echo "<td class=\"td-ledger\">";
						echo $this->print_space($this->counter);
						echo "&nbsp;" . anchor('report/ledgerst/' . $data['id'], $data['name'], array('title' => $data['name'] . ' Ledger Statement', 'style' => 'color:#000000'));
						echo "</td>";
						echo "<td align=\"right\">" . convert_amount_to_dc($data['total'])."</td>";
						//echo "<td align=\"right\">" . convert_amount_to_dc($data['total']) . $this->print_space($this->counter) . "</td>";
						echo "</tr>";
					}
				}
				$this->counter--;
			}
		}else{
			if ($this->id != 0){
				if($this->total!=0){
					echo "<tr class=\"tr-group\">";
					echo "<td class=\"td-group\" width='30%'>";
					echo $this->print_space($this->counter);
					if ($this->id <= 4)
						echo "&nbsp;<strong>" .  $this->name. "</strong>";
					else
						echo "&nbsp;" .  $this->name;
					echo "</td>";
					$CI =& get_instance();
					$CI->load->model('Ledger_model');
					$opBalance=$CI->Ledger_model->get_opBalance_byGroup($this->id);
					$group_dr_total=$CI->Ledger_model->get_drTotal_byGroup($this->id);
					$group_cr_total=$CI->Ledger_model->get_crTotal_byGroup($this->id);
					$clBalance=$CI->Ledger_model->get_clBalance_byGroup($this->id);
					//echo "<td>Group Account</td>";
					echo "<td class=\"td-group\" align='right'><b>";
					if($opBalance['dr_opbalance']) echo  convert_cur($opBalance['dr_opbalance']); else echo'-';
					echo "</b></td>";
					echo "<td class=\"td-group\" align='right'><b>";
					if($opBalance['cr_opbalance']) echo  convert_cur($opBalance['cr_opbalance']); else echo'-';
					echo "</b></td>";
					echo "<td class=\"td-group\" align='right'><b>";
					if($group_dr_total) echo  convert_cur($group_dr_total); else echo'-';
					echo "</b></td>";
					echo "<td class=\"td-group\" align='right'><b>";
					if($group_cr_total) echo  convert_cur($group_cr_total); else echo '-';
					echo "</b></td>";
					echo "<td class=\"td-group\" align='right'>";
					if($clBalance['dr_clbalance']) echo "<b>".convert_cur($clBalance['dr_clbalance'])."</b>";else echo '-';
					echo "</td>";
					echo "<td class=\"td-group\" align='right'>";
					if($clBalance['cr_clbalance']) echo "<b>".convert_cur(-$clBalance['cr_clbalance'])."</b>";else echo '-';
					echo "</td>";
		
					/*if ($this->id <= 4)
					{
						echo "<td class=\"td-actions\"></tr>";
					} else {
						echo "<td class=\"td-actions\">" . anchor('group/edit/' . $this->id , "Edit", array('title' => 'Edit Group', 'class' => 'red-link'));
						echo " &nbsp;" . anchor('group/delete/' . $this->id, img(array('src' => asset_url() . "images/icons/delete.png", 'border' => '0', 'alt' => 'Delete group')), array('class' => "confirmClick", 'title' => "Delete Group")) . "</td>";
					}*/
					echo "</tr>";
				}
			}
			foreach ($this->children_groups as $id => $data)
			{
				$this->counter++;			
				$data->account_st_short($this->counter, 'trial');
				$this->counter--;
			}
			if (count($this->children_ledgers) > 0){	
				$temp_dr_total = 0;
				$temp_cr_total = 0;
				$CI =& get_instance();
				$CI->load->model('Ledger_model');
				$this->counter++;
				foreach ($this->children_ledgers as $id => $data){
					if($data['total']!=0){
					echo "<tr class=\"tr-ledger\">";
					echo "<td class=\"td-ledger\" width='30%'>";
					echo $this->print_space($this->counter);
					echo "&nbsp;" . anchor('report/ledgerst/' . $data['id'], $data['name'], array('title' => $data['name'] . ' Ledger Statement', 'style' => 'color:#000000'));
					echo "</td>";
					//echo "<td>Ledger Account</td>";
					/*Dr Opening*/
					echo "<td class=\"td-ledger\" align='right'>" ;
					$convert_cur=convert_cur($data['opbalance']);
					if($data['optype']=='D' and $convert_cur!=0){echo convert_cur($data['opbalance']);}else echo '-' ;
					echo "</td>";
					/*Cr Opening*/
					echo "<td class=\"td-ledger\" align='right'>" ;
					if($data['optype']!='D' and $convert_cur!=0){echo convert_cur($data['opbalance']);}else echo '-' ;
					echo "</td>";
					/*Dr Total*/
					echo "<td class=\"td-ledger\" align='right'>";
					$dr_total = $CI->Ledger_model->get_dr_total($data['id']);
					if ($dr_total)
					{
						echo convert_cur($dr_total);
						$temp_dr_total = float_ops($temp_dr_total, $dr_total, '+');
					} else {
						echo "-";
					}
					//convert_amount_dc($data['total']) . 
					"</td>";
					/*Cr Total*/
					echo "<td class=\"td-ledger\" align='right'>";
					$cr_total = $CI->Ledger_model->get_cr_total($data['id']);
					if ($cr_total)
					{
						echo convert_cur($cr_total);
						$temp_cr_total = float_ops($temp_cr_total, $cr_total, '+');
					} else {
						echo "-";
					}
					echo "</td>";
					
					/*Closings*/
					$clbal_amount = $CI->Ledger_model->get_ledger_balance($data['id']);
					/*Dr Closing*/
					echo "<td class=\"td-ledger\" align='right'>";
					if ($clbal_amount < 0)
						echo "-";// . convert_cur(-$amount);
					else
						echo convert_cur($clbal_amount);				
					//echo convert_amount_dc($clbal_amount);
					echo "</td >";
					/*Cr Closing*/
					echo "<td class=\"td-ledger\"align='right'>";
					if ($clbal_amount < 0)
						echo convert_cur(-$clbal_amount);// . convert_cur(-$amount);
					else
						echo "-" ;				
					//echo convert_amount_dc($clbal_amount);
					echo "</td>";
					
					/*echo "<td class=\"td-actions\">" . anchor('ledger/edit/' . $data['id'], 'Edit', array('title' => "Edit Ledger", 'class' => 'red-link'));
					echo " &nbsp;" . anchor('ledger/delete/' . $data['id'], img(array('src' => asset_url() . "images/icons/delete.png", 'border' => '0', 'alt' => 'Delete Ledger')), array('class' => "confirmClick", 'title' => "Delete Ledger")) . "</td>";*/
					echo "</tr>";
					}
				}
				$this->counter--;
			}
		}
	}

	/* Display chart of accounts view */
	function account_st_main($c = 0)
	{
		$this->counter = $c;
		if ($this->id != 0)
		{
			echo "<tr class=\"tr-group\">";
			echo "<td class=\"td-group\">";
			echo $this->print_space($this->counter);
			if ($this->id <= 4)
				echo "&nbsp;<strong>" .  $this->name. "</strong>";
			else
				echo "&nbsp;" .  $this->name;
			echo "</td>";
			echo "<td>Group Account</td>";
			echo "<td>-</td>";
			echo "<td>-</td>";

			if ($this->id <= 4)
			{
				echo "<td class=\"td-actions\"></tr>";
			} else {
				echo "<td class=\"td-actions\">" . anchor('group/edit/' . $this->id , "Edit", array('title' => 'Edit Group', 'class' => 'red-link'));
				echo " &nbsp;" . anchor('group/delete/' . $this->id, img(array('src' => asset_url() . "images/icons/delete.png", 'border' => '0', 'alt' => 'Delete group')), array('class' => "confirmClick", 'title' => "Delete Group")) . "</td>";
			}
			echo "</tr>";
		}
		foreach ($this->children_groups as $id => $data)
		{
			$this->counter++;
			$data->account_st_main($this->counter);
			$this->counter--;
		}
		if (count($this->children_ledgers) > 0)
		{
			$this->counter++;
			foreach ($this->children_ledgers as $id => $data)
			{
				echo "<tr class=\"tr-ledger\">";
				echo "<td class=\"td-ledger\">";
				echo $this->print_space($this->counter);
				echo "&nbsp;" . anchor('report/ledgerst/' . $data['id'], $data['name'], array('title' => $data['name'] . ' Ledger Statement', 'style' => 'color:#000000'));
				echo "</td>";
				echo "<td>Ledger Account</td>";
				echo "<td>" . convert_opening($data['opbalance'], $data['optype']) . "</td>";
				echo "<td>" . convert_amount_dc($data['total']) . "</td>";
				echo "<td class=\"td-actions\">" . anchor('ledger/edit/' . $data['id'], 'Edit', array('title' => "Edit Ledger", 'class' => 'red-link'));
				echo " &nbsp;" . anchor('ledger/delete/' . $data['id'], img(array('src' => asset_url() . "images/icons/delete.png", 'border' => '0', 'alt' => 'Delete Ledger')), array('class' => "confirmClick", 'title' => "Delete Ledger")) . "</td>";
				echo "</tr>";
			}
			$this->counter--;
		}
	}
	function account_st_trial($c = 0){		
		$this->counter = $c;
		if ($this->id != 0)
		{
			echo "<tr class=\"tr-group\">";
			echo "<td class=\"td-group\">";
			echo $this->print_space($this->counter);
			if ($this->id <= 4)
				echo "&nbsp;<strong>" .  $this->name. "</strong>";
			else
				echo "&nbsp;" .  $this->name;
			echo "</td>";
			//echo "<td>Group Account</td>";
			echo "<td>-</td>";
			echo "<td>-</td>";
			echo "<td>-</td>";
			echo "<td>-</td>";
			echo "<td>-</td>";
			echo "<td>-</td>";

			/*if ($this->id <= 4)
			{
				echo "<td class=\"td-actions\"></tr>";
			} else {
				echo "<td class=\"td-actions\">" . anchor('group/edit/' . $this->id , "Edit", array('title' => 'Edit Group', 'class' => 'red-link'));
				echo " &nbsp;" . anchor('group/delete/' . $this->id, img(array('src' => asset_url() . "images/icons/delete.png", 'border' => '0', 'alt' => 'Delete group')), array('class' => "confirmClick", 'title' => "Delete Group")) . "</td>";
			}*/
			echo "</tr>";
		}
		foreach ($this->children_groups as $id => $data)
		{
			$this->counter++;
			$a=Accountlist::account_st_trail($this->counter);
			$data->$a;
			$this->counter--;
		}
		if (count($this->children_ledgers) > 0)
		{	
			$temp_dr_total = 0;
			$temp_cr_total = 0;
			$this->load->model('Ledger_model');
			$this->counter++;
			foreach ($this->children_ledgers as $id => $data)
			{
				echo "<tr class=\"tr-ledger\">";
				echo "<td class=\"td-ledger\">";
				echo $this->print_space($this->counter);
				echo "&nbsp;" . anchor('report/ledgerst/' . $data['id'], $data['name'], array('title' => $data['name'] . ' Ledger Statement', 'style' => 'color:#000000'));
				echo "</td>";
				//echo "<td>Ledger Account</td>";
				/*Dr Opening*/
				echo "<td>" ;
				if($data['optype']=='D'){echo convert_cur($data['opbalance']);}else echo '-' ;
				echo "</td>";
				/*Cr Opening*/
				echo "<td>" ;
				if($data['optype']!='D'){echo convert_cur($data['opbalance']);}else echo '-' ;
				echo "</td>";
				/*Dr Total*/
				echo "<td>";
				$dr_total = $this->Ledger_model->get_dr_total($ledger_id);
				if ($dr_total)
				{
					echo convert_cur($dr_total);
					$temp_dr_total = float_ops($temp_dr_total, $dr_total, '+');
				} else {
					echo "-";
				}
				//convert_amount_dc($data['total']) . 
				"</td>";
				/*Cr Total*/
				echo "<td>";
				$cr_total = $this->Ledger_model->get_cr_total($ledger_id);
				if ($cr_total)
				{
					echo convert_cur($cr_total);
					$temp_cr_total = float_ops($temp_cr_total, $cr_total, '+');
				} else {
					echo "-";
				}
				echo "</td>";
				
				/*Closings*/
				$clbal_amount = $this->Ledger_model->get_ledger_balance($ledger_id);
				/*Dr Closing*/
				echo "<td>";
				if ($clbal_amount < 0)
					echo "-";// . convert_cur(-$amount);
				else
					echo convert_cur($clbal_amount);				
				//echo convert_amount_dc($clbal_amount);
				echo "</td>";
				/*Cr Closing*/
				echo "<td>";
				if ($clbal_amount < 0)
					echo convert_cur($clbal_amount);// . convert_cur(-$amount);
				else
					echo "-" ;				
				//echo convert_amount_dc($clbal_amount);
				echo "</td>";
				
				/*echo "<td class=\"td-actions\">" . anchor('ledger/edit/' . $data['id'], 'Edit', array('title' => "Edit Ledger", 'class' => 'red-link'));
				echo " &nbsp;" . anchor('ledger/delete/' . $data['id'], img(array('src' => asset_url() . "images/icons/delete.png", 'border' => '0', 'alt' => 'Delete Ledger')), array('class' => "confirmClick", 'title' => "Delete Ledger")) . "</td>";*/
				echo "</tr>";
			}
			$this->counter--;
		}
	}

	function print_space($count)
	{
		$html = "";
		for ($i = 1; $i <= $count; $i++)
		{
			$html .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
		}
		return $html;
	}
	
	/* Build a array of groups and ledgers */
	function build_array()
	{
		$item = array(
			'id' => $this->id,
			'name' => $this->name,
			'type' => "G",
			'total' => $this->total,
			'child_groups' => array(),
			'child_ledgers' => array(),
			'depth' => self::$temp_max,
		);
		$local_counter = 0;
		if (count($this->children_groups) > 0)
		{
			self::$temp_max++;
			if (self::$temp_max > self::$max_depth)
				self::$max_depth = self::$temp_max;
			foreach ($this->children_groups as $id => $data)
			{
				$item['child_groups'][$local_counter] = $data->build_array();
				$local_counter++;
			}
			self::$temp_max--;
		}
		$local_counter = 0;
		if (count($this->children_ledgers) > 0)
		{
			self::$temp_max++;
			foreach ($this->children_ledgers as $id => $data)
			{
				$item['child_ledgers'][$local_counter] = array(
					'id' => $data['id'],
					'name' => $data['name'],
					'type' => "L",
					'total' => $data['total'],
					'child_groups' => array(),
					'child_ledgers' => array(),
					'depth' => self::$temp_max,
				);
				$local_counter++;
			}
			self::$temp_max--;
		}
		return $item;
	}

	/* Show array of groups and ledgers as created by build_array() method */
	function show_array($data)
	{
		echo "<tr>";
		echo "<td>";
		echo $this->print_space($data['depth']);
		echo $data['depth'] . "-";
		echo $data['id'];
		echo $data['name'];
		echo $data['type'];
		echo $data['total'];
		if ($data['child_ledgers'])
		{
			foreach ($data['child_ledgers'] as $id => $ledger_data)
			{
				$this->show_array($ledger_data);
			}
		}
		if ($data['child_groups'])
		{
			foreach ($data['child_groups'] as $id => $group_data)
			{
				$this->show_array($group_data);
			}
		}
		echo "</td>";
		echo "</tr>";
	}

	function to_csv($data)
	{
		$counter = 0;
		while ($counter < $data['depth'])
		{
			self::$csv_data[self::$csv_row][$counter] = "";
			$counter++;
		}

		self::$csv_data[self::$csv_row][$counter] = $data['name'];
		$counter++;

		while ($counter < self::$max_depth + 3)
		{
			self::$csv_data[self::$csv_row][$counter] = "";
			$counter++;
		}
		self::$csv_data[self::$csv_row][$counter] = $data['type'];
		$counter++;

		if ($data['total'] == 0)
		{
			self::$csv_data[self::$csv_row][$counter] = "";
			$counter++;
			self::$csv_data[self::$csv_row][$counter] = "";
		} else if ($data['total'] < 0) {
			self::$csv_data[self::$csv_row][$counter] = "Cr";
			$counter++;
			self::$csv_data[self::$csv_row][$counter] = -$data['total'];
		} else {
			self::$csv_data[self::$csv_row][$counter] = "Dr";
			$counter++;
			self::$csv_data[self::$csv_row][$counter] = $data['total'];
		}

		if ($data['child_ledgers'])
		{
			foreach ($data['child_ledgers'] as $id => $ledger_data)
			{
				self::$csv_row++;
				$this->to_csv($ledger_data);
			}
		}
		if ($data['child_groups'])
		{
			foreach ($data['child_groups'] as $id => $group_data)
			{
				self::$csv_row++;
				$this->to_csv($group_data);
			}
		}
	}

	public static function get_csv()
	{
		return self::$csv_data;
	}
	
	public static function add_blank_csv()
	{
		self::$csv_row++;
		self::$csv_data[self::$csv_row] = array("", "");
		self::$csv_row++;
		self::$csv_data[self::$csv_row] = array("", "");
		return;
	}
	
	public static function add_row_csv($row = array(""))
	{
		self::$csv_row++;
		self::$csv_data[self::$csv_row] = $row;
		return;
	}

	public static function reset_max_depth()
	{
		self::$max_depth = 0;
		self::$temp_max = 0;
	}

	/*
	 * Return a array of sub ledgers with the object
	 * Used in CF ledgers of type Assets and Liabilities
	*/
	function get_ledger_ids()
	{
		$ledgers = array();
		if (count($this->children_ledgers) > 0)
		{
			foreach ($this->children_ledgers as $id => $data)
			{
				$ledgers[] = $data['id'];
			}
		}
		if (count($this->children_groups) > 0)
		{
			foreach ($this->children_groups as $id => $data)
			{
				foreach ($data->get_ledger_ids() as $row)
					$ledgers[] = $row;
			}
		}
		return $ledgers;
	}
}

