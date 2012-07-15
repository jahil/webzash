<?php

class Ledger_model extends Model {

	function Ledger_model()
	{
		parent::Model();
	}

	function get_all_ledgers()
	{
		$options = array();
		$options[0] = "(Please Select)";
		$this->db->from('ledgers')->order_by('name', 'asc');
		$ledger_q = $this->db->get();
		foreach ($ledger_q->result() as $row)
		{
			$options[$row->id] = $row->name;
		}
		return $options;
	}

	function get_all_ledgers_bankcash()
	{
		$options = array();
		$options[0] = "(Please Select)";
		$this->db->from('ledgers')->where('type', 1)->order_by('name', 'asc');
		$ledger_q = $this->db->get();
		foreach ($ledger_q->result() as $row)
		{
			$options[$row->id] = $row->name;
		}
		return $options;
	}

	function get_all_ledgers_nobankcash()
	{
		$options = array();
		$options[0] = "(Please Select)";
		$this->db->from('ledgers')->where('type !=', 1)->order_by('name', 'asc');
		$ledger_q = $this->db->get();
		foreach ($ledger_q->result() as $row)
		{
			$options[$row->id] = $row->name;
		}
		return $options;
	}

	function get_all_ledgers_reconciliation()
	{
		$options = array();
		$options[0] = "(Please Select)";
		$this->db->from('ledgers')->where('reconciliation', 1)->order_by('name', 'asc');
		$ledger_q = $this->db->get();
		foreach ($ledger_q->result() as $row)
		{
			$options[$row->id] = $row->name;
		}
		return $options;
	}

	function get_name($ledger_id)
	{
		$this->db->from('ledgers')->where('id', $ledger_id)->limit(1);
		$ledger_q = $this->db->get();
		if ($ledger = $ledger_q->row())
			return $ledger->name;
		else
			return "(Error)";
	}

	function get_entry_name($entry_id, $entry_type_id)
	{
		/* Selecting whether to show debit side Ledger or credit side Ledger */
		$current_entry_type = entry_type_info($entry_type_id);
		$ledger_type = 'C';

		if ($current_entry_type['bank_cash_ledger_restriction'] == 3)
			$ledger_type = 'D';

		$this->db->select('ledgers.name as name');
		$this->db->from('entry_items')->join('ledgers', 'entry_items.ledger_id = ledgers.id')->where('entry_items.entry_id', $entry_id)->where('entry_items.dc', $ledger_type);
		$ledger_q = $this->db->get();
		if ( ! $ledger = $ledger_q->row())
		{
			return "(Invalid)";
		} else {
			$ledger_multiple = ($ledger_q->num_rows() > 1) ? TRUE : FALSE;
			$html = '';
			if ($ledger_multiple)
				$html .= anchor('entry/view/' . $current_entry_type['label'] . "/" . $entry_id, "(" . $ledger->name . ")", array('title' => 'View ' . $current_entry_type['name'] . ' Entry', 'class' => 'anchor-link-a'));
			else
				$html .= anchor('entry/view/' . $current_entry_type['label'] . "/" . $entry_id, $ledger->name, array('title' => 'View ' . $current_entry_type['name'] . ' Entry', 'class' => 'anchor-link-a'));
			return $html;
		}
		return;
	}

	function get_opp_ledger_name($entry_id, $entry_type_label, $ledger_type, $output_type)
	{
		$output = '';
		if ($ledger_type == 'D')
			$opp_ledger_type = 'C';
		else
			$opp_ledger_type = 'D';
		$this->db->from('entry_items')->where('entry_id', $entry_id)->where('dc', $opp_ledger_type);
		$opp_entry_name_q = $this->db->get();
		if ($opp_entry_name_d = $opp_entry_name_q->row())
		{
			$opp_ledger_name = $this->get_name($opp_entry_name_d->ledger_id);
			if ($opp_entry_name_q->num_rows() > 1)
			{
				if ($output_type == 'html')
					$output = anchor('entry/view/' . $entry_type_label . '/' . $entry_id, "(" . $opp_ledger_name . ")", array('title' => 'View ' . ' Entry', 'class' => 'anchor-link-a'));
				else
					$output = "(" . $opp_ledger_name . ")";
			} else {
				if ($output_type == 'html')
					$output = anchor('entry/view/' . $entry_type_label . '/' . $entry_id, $opp_ledger_name, array('title' => 'View ' . ' Entry', 'class' => 'anchor-link-a'));
				else
					$output = $opp_ledger_name;
			}
		}
		return $output;
	}

	function get_ledger_balance($ledger_id)
	{
		list ($op_bal, $op_bal_type) = $this->get_op_balance($ledger_id);

		$dr_total = $this->get_dr_total($ledger_id);
		$cr_total = $this->get_cr_total($ledger_id);

		$total = float_ops($dr_total, $cr_total, '-');
		if ($op_bal_type == "D")
			$total = float_ops($total, $op_bal, '+');
		else
			$total = float_ops($total, $op_bal, '-');

		return $total;
	}

	function get_op_balance($ledger_id)
	{
		$this->db->from('ledgers')->where('id', $ledger_id)->limit(1);
		$op_bal_q = $this->db->get();
		if ($op_bal = $op_bal_q->row())
			return array($op_bal->op_balance, $op_bal->op_balance_dc);
		else
			return array(0, "D");
	}

	function get_diff_op_balance()
	{
		/* Calculating difference in Opening Balance */
		$total_op = 0;
		$this->db->from('ledgers')->order_by('id', 'asc');
		$ledgers_q = $this->db->get();
		foreach ($ledgers_q->result() as $row)
		{
			list ($opbalance, $optype) = $this->get_op_balance($row->id);
			if ($optype == "D")
			{
				$total_op = float_ops($total_op, $opbalance, '+');
			} else {
				$total_op = float_ops($total_op, $opbalance, '-');
			}
		}
		return $total_op;
	}

	/* Return debit total as positive value */
	function get_dr_total($ledger_id)
	{
		$this->db->select_sum('amount', 'drtotal')->from('entry_items')->join('entries', 'entries.id = entry_items.entry_id')->where('entry_items.ledger_id', $ledger_id)->where('entry_items.dc', 'D');
		$dr_total_q = $this->db->get();
		if ($dr_total = $dr_total_q->row())
			return $dr_total->drtotal;
		else
			return 0;
	}

	/* Return credit total as positive value */
	function get_cr_total($ledger_id)
	{
		$this->db->select_sum('amount', 'crtotal')->from('entry_items')->join('entries', 'entries.id = entry_items.entry_id')->where('entry_items.ledger_id', $ledger_id)->where('entry_items.dc', 'C');
		$cr_total_q = $this->db->get();
		if ($cr_total = $cr_total_q->row())
			return $cr_total->crtotal;
		else
			return 0;
	}

	/* Delete reconciliation entries for a Ledger account */
	function delete_reconciliation($ledger_id)
	{
		$update_data = array(
			'reconciliation_date' => NULL,
		);
		$this->db->where('ledger_id', $ledger_id)->update('entry_items', $update_data);
		return;
	}
	/* convert Date formate dd/mm/yyyy to (yyyy-mm-dd or mm-dd-yyyy) */
	function convertDate($mydate,$formate){
		list($d, $m, $y) = preg_split('/\//', $mydate);
		if($formate=='y-m-d'){
			$mydate = sprintf('%4d%02d%02d', $y, $m, $d);
			return $mydate;
		}elseif($formate=='m-d-y'){
			$mydate = sprintf('%02d%02d%4d', $m, $d, $y);
			return $mydate;
		}
	}
	function get_trailTotal($col){
		$temp_total=0;
		switch($col){
			case 'drop':
				for($i=1;$i<=4;$i++){
					$dr_op_total=$this->get_opBalance_byGroup($i);
					$temp_total = float_ops($temp_total, $dr_op_total['dr_opbalance'], '+');
				}
			break;
			case 'crop':
				for($i=1;$i<=4;$i++){
					$cr_op_total=$this->get_opBalance_byGroup($i);
					$temp_total = float_ops($temp_total, $cr_op_total['cr_opbalance'], '+');
				}
			break;
			case 'drcu':
				for($i=1;$i<=4;$i++){
					$dr_cu_total=$this->get_drTotal_byGroup($i);
					$temp_total = float_ops($temp_total, $dr_cu_total, '+');
				}
			break;
			case 'crcu':
				for($i=1;$i<=4;$i++){
					$cr_cu_total=$this->get_crTotal_byGroup($i);
					$temp_total = float_ops($temp_total, $cr_cu_total, '+');
				}
			break;
			case 'drcl':
				for($i=1;$i<=4;$i++){
					$dr_cl_total=$this->get_clBalance_byGroup($i);
					$temp_total = float_ops($temp_total, $dr_cl_total['dr_clbalance'], '+');
				}
			break;
			case 'crcl':
				for($i=1;$i<=4;$i++){
					$cr_cl_total=$this->get_clBalance_byGroup($i);
					$temp_total = float_ops($temp_total, $cr_cl_total['cr_clbalance'], '+');
				}
			break;
		}
		return $temp_total;
	}
	function drtotal(){
		$temp_dr_total = 0;
		$temp_cr_total = 0;
		$all_ledgers = $this->get_all_ledgers();
		foreach ($all_ledgers as $ledger_id => $ledger_name){
			$dr_total = $this->get_dr_total($ledger_id);
			if ($dr_total){
				$temp_dr_total = float_ops($temp_dr_total, $dr_total, '+');
			}
			$cr_total = $this->get_cr_total($ledger_id);
			if ($cr_total){
				$temp_cr_total = float_ops($temp_cr_total, $cr_total, '+');
			}
		}
		$data['temp_dr_total']=$temp_dr_total;
		$data['temp_cr_total']=$temp_cr_total;
		return $data;
	}
	function get_sub_groups($gid){
		$this->db->from('groups')->where('parent_id', $gid);
		$child_group_q = $this->db->get();
		if($child_group_q->num_rows>0){
			foreach ($child_group_q->result() as $row){
				if($row->id){
					$subgroups[]=$row->id;
					$this->get_sub_groups($row->id);
				}
			}
		}else{$subgroups = array();}
		return $subgroups;
	}
	function get_sub_ledgers($gid){	$children_ledgers=array();	
		$this->db->from('ledgers')->where('group_id', $gid);
		$child_ledger_q = $this->db->get();
		$counter = 0;$total=0;
		foreach ($child_ledger_q->result() as $row)
		{
			$children_ledgers[$counter]['id'] = $row->id;
			$children_ledgers[$counter]['name'] = $row->name;
			$children_ledgers[$counter]['total'] = $this->get_ledger_balance($row->id);
			list ($children_ledgers[$counter]['opbalance'], $children_ledgers[$counter]['optype']) = $this->get_op_balance($row->id);
			$counter++;
		}
		return $children_ledgers;
	}
	
	function get_clBalance_byGroup($group_id){
		$dr_clbalance = 0;
		$cr_clbalance = 0;
		$subgroup_ids=$this->get_sub_groups($group_id);	
		if($subgroup_ids){
			foreach($subgroup_ids as $subgroup_id){
				$subsubgroup_ids=$this->get_sub_groups($subgroup_id);
				$suball_ledgers = $this->get_sub_ledgers($subgroup_id);

				foreach ($suball_ledgers as $subledger){
					$dr_total = $this->get_dr_total($subledger['id']);
					$cr_total = $this->get_cr_total($subledger['id']);			
					$total = float_ops($dr_total, $cr_total, '-');
					if($subledger['optype']=='D'){
						$dr_clTotal= float_ops($total,$subledger['opbalance'], '+'); 
						echo '&nbsp;&nbsp;';
						if($dr_clTotal>0){
							$dr_clbalance = float_ops($dr_clbalance,$dr_clTotal, '+');
						}else{$cr_clbalance = float_ops($cr_clbalance,$dr_clTotal, '+');}
					}else{
						$cr_clTotal= float_ops($total,$subledger['opbalance'], '-');
						if($cr_clTotal>0){
							$dr_clbalance = float_ops($dr_clbalance,$cr_clTotal, '+');
						}else{$cr_clbalance = float_ops($cr_clbalance,$cr_clTotal, '+');}
					}	
							
				}
				
				if($subsubgroup_ids){
					foreach($subsubgroup_ids as $subsubgroup_id){
						$all_ledgers = $this->get_sub_ledgers($subsubgroup_id);
						foreach ($all_ledgers as $ledger){
							$dr_total = $this->get_dr_total($ledger['id']);
							$cr_total = $this->get_cr_total($ledger['id']);			
							$total = float_ops($dr_total, $cr_total, '-');
							if($ledger['optype']=='D'){
								$dr_clTotal= float_ops($total,$ledger['opbalance'], '+');
								if($dr_clTotal>0){
									$dr_clbalance = float_ops($dr_clbalance,$dr_clTotal, '+');
								}else{$cr_clbalance = float_ops($cr_clbalance,$dr_clTotal, '+');}
							}else{
								$cr_clTotal= float_ops($total,$ledger['opbalance'], '-');
								if($cr_clTotal>0){
									$dr_clbalance = float_ops($dr_clbalance,$cr_clTotal, '+');
								}else{$cr_clbalance = float_ops($cr_clbalance,$cr_clTotal, '+');}
							}				
						}
					}
				}
			}
		}
		
		$all_ledgers = $this->get_sub_ledgers($group_id);
		foreach ($all_ledgers as $ledger){
			$dr_total = $this->get_dr_total($ledger['id']);
			$cr_total = $this->get_cr_total($ledger['id']);			
			$total = float_ops($dr_total, $cr_total, '-');
			if($ledger['optype']=='D'){
				$dr_clTotal= float_ops($total,$ledger['opbalance'], '+');
				if($dr_clTotal>0){
				$dr_clbalance = float_ops($dr_clbalance,$dr_clTotal, '+');
				}else{$cr_clbalance = float_ops($cr_clbalance,$dr_clTotal, '+');}
			}else{
				$cr_clTotal= float_ops($total,$ledger['opbalance'], '-');
				if($cr_clTotal>0){
				$dr_clbalance = float_ops($dr_clbalance,$cr_clTotal, '+');
				}else{$cr_clbalance = float_ops($cr_clbalance,$cr_clTotal, '+');}
			}				
		}
		
		$data['dr_clbalance']=$dr_clbalance;
		$data['cr_clbalance']=$cr_clbalance;
		return $data;
	}
	function get_opBalance_byGroup($group_id){
		$dr_opbalance = 0;
		$cr_opbalance = 0;
		$subgroup_ids=$this->get_sub_groups($group_id);		
		if($subgroup_ids){
			foreach($subgroup_ids as $subgroup_id){
				$subsubgroup_ids=$this->get_sub_groups($subgroup_id);
				$suball_ledgers = $this->get_sub_ledgers($subgroup_id);
				foreach ($suball_ledgers as $subledger){
					if($subledger['optype']=='D'){
						$dr_opbalance = float_ops($dr_opbalance, $subledger['opbalance'], '+');
					}else{
						$cr_opbalance = float_ops($cr_opbalance, $subledger['opbalance'], '+');
					}			
				}
				if($subsubgroup_ids){
					foreach($subsubgroup_ids as $subsubgroup_id){
						$all_ledgers = $this->get_sub_ledgers($subsubgroup_id);
						foreach ($all_ledgers as $ledger){
							if($ledger['optype']=='D'){
								$dr_opbalance = float_ops($dr_opbalance, $ledger['opbalance'], '+');
							}else{
								$cr_opbalance = float_ops($cr_opbalance, $ledger['opbalance'], '+');
							}			
						}
					}
				}
			}
		}
		$all_ledgers = $this->get_sub_ledgers($group_id);
		foreach ($all_ledgers as $ledger){
			if($ledger['optype']=='D'){
				$dr_opbalance = float_ops($dr_opbalance, $ledger['opbalance'], '+');
			}else{
				$cr_opbalance = float_ops($cr_opbalance, $ledger['opbalance'], '+');
			}			
		}
		
		$data['dr_opbalance']=$dr_opbalance;
		$data['cr_opbalance']=$cr_opbalance;
		return $data;
	}
	function get_drTotal_byGroup($group_id){
		$group_dr_total = 0;
		$subgroup_ids=$this->get_sub_groups($group_id);		
		if($subgroup_ids){
			foreach($subgroup_ids as $subgroup_id){
				$subsubgroup_ids=$this->get_sub_groups($subgroup_id);
				$suball_ledgers = $this->get_sub_ledgers($subgroup_id);
				foreach ($suball_ledgers as $ledger){
					$dr_total = $this->get_dr_total($ledger['id']);
					if ($dr_total){
						$group_dr_total = float_ops($group_dr_total, $dr_total, '+');
					}			
				}
				if($subsubgroup_ids){
					foreach($subsubgroup_ids as $subsubgroup_id){
						$all_ledgers = $this->get_sub_ledgers($subsubgroup_id);
						foreach ($all_ledgers as $ledger){
							$dr_total = $this->get_dr_total($ledger['id']);
							if ($dr_total){
								$group_dr_total = float_ops($group_dr_total, $dr_total, '+');
							}			
						}
					}
				}
			}
		}
		$all_ledgers = $this->get_sub_ledgers($group_id);
		foreach ($all_ledgers as $ledger){
			$dr_total = $this->get_dr_total($ledger['id']);
			if ($dr_total){
				$group_dr_total = float_ops($group_dr_total, $dr_total, '+');
			}			
		}
		return $group_dr_total;
	}
	function get_crTotal_byGroup($group_id){
		$group_cr_total = 0;
		$subgroup_ids=$this->get_sub_groups($group_id);		
		if($subgroup_ids){
			foreach($subgroup_ids as $subgroup_id){
				$subsubgroup_ids=$this->get_sub_groups($subgroup_id);
				$suball_ledgers = $this->get_sub_ledgers($subgroup_id);
				foreach ($suball_ledgers as $ledger){
					$cr_total = $this->get_cr_total($ledger['id']);
					if ($cr_total){
						$group_cr_total = float_ops($group_cr_total, $cr_total, '+');
					}			
				}
				if($subsubgroup_ids){
					foreach($subsubgroup_ids as $subsubgroup_id){
						$all_ledgers = $this->get_sub_ledgers($subsubgroup_id);
						foreach ($all_ledgers as $ledger){
							$cr_total = $this->get_cr_total($ledger['id']);
							if ($cr_total){
								$group_cr_total = float_ops($group_cr_total, $cr_total, '+');
							}			
						}
					}
				}
			}
		}
		$all_ledgers = $this->get_sub_ledgers($group_id);
		foreach ($all_ledgers as $ledger){
			$cr_total = $this->get_cr_total($ledger['id']);
			if ($cr_total){
				$group_cr_total = float_ops($group_cr_total, $cr_total, '+');
			}			
		}
		return $group_cr_total;
	}
}
