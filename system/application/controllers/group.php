<?php
class Group extends Controller {

	function Group()
	{
		parent::Controller();
		$this->load->model('Group_model');
	}

	function index()
	{
		redirect('group/add');
	}

	function add()
	{
		$this->load->library('validation');
		$this->template->set('page_title', 'New Group');

		/* Form fields */
		$data['group_name'] = array(
			'name' => 'group_name',
			'id' => 'group_name',
			'maxlength' => '100',
			'size' => '40',
			'value' => '',
		);
		$data['group_parent'] = $this->Group_model->get_all_groups();
		$data['group_parent_active'] = 0;

		/* Form validations */
		$this->form_validation->set_rules('group_name', 'Group name', 'trim|required|min_length[2]|max_length[100]|unique[groups.name]');
		$this->form_validation->set_rules('group_parent', 'Parent group', 'trim|required|is_natural_no_zero');

		/* Re-populating form */
		if ($_POST)
		{
			$data['group_name']['value'] = $this->input->post('group_name', TRUE);
			$data['group_parent_active'] = $this->input->post('group_parent', TRUE);
		}

		if ($this->form_validation->run() == FALSE)
		{
			$this->messages->add(validation_errors(), 'error');
			$this->template->load('template', 'group/add', $data);
		}
		else
		{
			$data_name = $this->input->post('group_name', TRUE);
			$data_parent_id = $this->input->post('group_parent', TRUE);

			$this->db->trans_start();
			if ( ! $this->db->query("INSERT INTO groups (name, parent_id) VALUES (?, ?)", array($data_name, $data_parent_id)))
			{
				$this->db->trans_rollback();
				$this->messages->add('Error addding Group A/C', 'error');
				$this->template->load('template', 'group/add', $data);
			} else {
				$this->db->trans_complete();
				$this->messages->add('Group A/C added successfully', 'success');
				redirect('account');
			}
		}
		return;
	}

	function edit($id)
	{
		$this->template->set('page_title', 'Edit Group');

		/* Checking for valid data */
		$id = $this->input->xss_clean($id);
		$id = (int)$id;
		if ($id < 1) {
			$this->messages->add('Invalid Group A/C', 'error');
			redirect('account');
			return;
		}
		if ($id < 5) {
			$this->messages->add('Cannot edit system created Group A/C', 'error');
			redirect('account');
			return;
		}

		/* Loading current group */
		$group_data_q = $this->db->query("SELECT * FROM groups WHERE id = ?", array($id));
		if ($group_data_q->num_rows() < 1)
		{
			$this->messages->add('Invalid Group A/C', 'error');
			redirect('account');
			return;
		}
		$group_data = $group_data_q->row();

		/* Form fields */
		$data['group_name'] = array(
			'name' => 'group_name',
			'id' => 'group_name',
			'maxlength' => '100',
			'size' => '40',
			'value' => $group_data->name,
		);
		$data['group_parent'] = $this->Group_model->get_all_groups($id);
		$data['group_parent_active'] = $group_data->parent_id;
		$data['group_id'] = $id;

		/* Form validations */
		$this->form_validation->set_rules('group_name', 'Group name', 'trim|required|min_length[2]|max_length[100]|uniquewithid[groups.name.' . $id . ']');
		$this->form_validation->set_rules('group_parent', 'Parent group', 'trim|required|is_natural_no_zero');


		/* Re-populating form */
		if ($_POST)
		{
			$data['group_name']['value'] = $this->input->post('group_name', TRUE);
			$data['group_parent_active'] = $this->input->post('group_parent', TRUE);
		}

		if ($this->form_validation->run() == FALSE)
		{
			$this->messages->add(validation_errors(), 'error');
			$this->template->load('template', 'group/edit', $data);
		}
		else
		{
			$data_name = $this->input->post('group_name', TRUE);
			$data_parent_id = $this->input->post('group_parent', TRUE);
			$data_id = $id;

			$this->db->trans_start();
			if ( ! $this->db->query("UPDATE groups SET name = ?, parent_id = ? WHERE id = ?", array($data_name, $data_parent_id, $data_id)))
			{
				$this->db->trans_rollback();
				$this->messages->add('Error updating Group A/C', 'error');
				$this->template->load('template', 'group/edit', $data);
			} else {
				$this->db->trans_complete();
				$this->messages->add('Group A/C updated successfully', 'success');
				redirect('account');
			}
		}
		return;
	}

	function delete($id)
	{
		/* Checking for valid data */
		$id = $this->input->xss_clean($id);
		$id = (int)$id;
		if ($id < 1) {
			$this->messages->add('Invalid Group A/C', 'error');
			redirect('account');
			return;
		}
		if ($id < 5) {
			$this->messages->add('Cannot delete system created Group A/C', 'error');
			redirect('account');
			return;
		}
		$data_present_q = $this->db->query("SELECT * FROM groups WHERE parent_id = ?", array($id));
		if ($data_present_q->num_rows() > 0)
		{
			$this->messages->add('Cannot delete non-empty Group A/C', 'error');
			redirect('account');
			return;
		}
		$data_present_q = $this->db->query("SELECT * FROM ledgers WHERE group_id = ?", array($id));
		if ($data_present_q->num_rows() > 0)
		{
			$this->messages->add('Cannot delete non-empty Group A/C', 'error');
			redirect('account');
			return;
		}

		/* Deleting group */
		$this->db->trans_start();
		if ( ! $this->db->query("DELETE FROM groups WHERE id = ?", array($id)))
		{
			$this->db->trans_rollback();
			$this->messages->add('Error deleting Group A/C', 'success');
			redirect('account');
		} else {
			$this->db->trans_complete();
			$this->messages->add('Group A/C deleted successfully', 'success');
			redirect('account');
		}
		return;
	}
}
