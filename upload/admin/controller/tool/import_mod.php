<?php
class ControllerToolImportMod extends Controller {
	private $error = array();
	private $ssl = 'SSL';

	public function __construct( $registry ) {
		parent::__construct( $registry );
		$this->ssl = (defined('VERSION') && version_compare(VERSION,'2.2.0.0','>=')) ? true : 'SSL';
	}
	
	public function index() {
		$this->load->model('tool/import_mod');
		$this->model_tool_import_mod->install();
		$this->load->language('tool/import_mod');
		$this->document->setTitle($this->language->get('heading_title'));
		$this->getForm();
	}
	
	public function downloadQueuedImages() {
		$this->load->model('tool/image');
		$cnt = $this->model_tool_image->processImageQueue();
		echo 'Successfully downloaded '.$cnt.' images.<br />';
	}
	
	protected function getForm() {
		$data = array();
		$data['heading_title'] = $this->language->get('heading_title');
		
		$data['exist_filter'] = $this->model_tool_import_mod->existFilter();
		$data['text_yes'] = $this->language->get('text_yes');
		$data['text_no'] = $this->language->get('text_no');
		$data['text_loading_notifications'] = $this->language->get( 'text_loading_notifications' );
		$data['text_retry'] = $this->language->get('text_retry');

		$data['entry_import'] = $this->language->get( 'entry_import' );
		$data['entry_range_type'] = $this->language->get( 'entry_range_type' );
		$data['entry_start_id'] = $this->language->get( 'entry_start_id' );
		$data['entry_start_index'] = $this->language->get( 'entry_start_index' );
		$data['entry_end_id'] = $this->language->get( 'entry_end_id' );
		$data['entry_end_index'] = $this->language->get( 'entry_end_index' );
		$data['entry_incremental'] = $this->language->get( 'entry_incremental' );
		$data['entry_upload'] = $this->language->get( 'entry_upload' );
		$data['entry_settings_use_option_id'] = $this->language->get( 'entry_settings_use_option_id' );
		$data['entry_settings_use_option_value_id'] = $this->language->get( 'entry_settings_use_option_value_id' );
		$data['entry_settings_use_attribute_group_id'] = $this->language->get( 'entry_settings_use_attribute_group_id' );
		$data['entry_settings_use_attribute_id'] = $this->language->get( 'entry_settings_use_attribute_id' );
		$data['entry_settings_use_filter_group_id'] = $this->language->get( 'entry_settings_use_filter_group_id' );
		$data['entry_settings_use_filter_id'] = $this->language->get( 'entry_settings_use_filter_id' );
		$data['entry_settings_use_import_cache'] = $this->language->get( 'entry_settings_use_import_cache' );

		$data['tab_import'] = $this->language->get( 'tab_import' );
		$data['tab_settings'] = $this->language->get( 'tab_settings' );

		$data['button_import'] = $this->language->get( 'button_import' );
		$data['button_settings'] = $this->language->get( 'button_settings' );

		$data['help_range_type'] = $this->language->get( 'help_range_type' );
		$data['help_incremental_yes'] = $this->language->get( 'help_incremental_yes' );
		$data['help_incremental_no'] = $this->language->get( 'help_incremental_no' );
		$data['help_import'] = ($data['exist_filter']) ? $this->language->get( 'help_import' ) : $this->language->get( 'help_import_old' );
		$data['help_format'] = $this->language->get( 'help_format' );

		$data['error_select_file'] = $this->language->get('error_select_file');
		$data['error_post_max_size'] = str_replace( '%1', ini_get('post_max_size'), $this->language->get('error_post_max_size') );
		$data['error_upload_max_filesize'] = str_replace( '%1', ini_get('upload_max_filesize'), $this->language->get('error_upload_max_filesize') );
		$data['error_id_no_data'] = $this->language->get('error_id_no_data');
		$data['error_page_no_data'] = $this->language->get('error_page_no_data');
		$data['error_param_not_number'] = $this->language->get('error_param_not_number');
		$data['error_notifications'] = $this->language->get('error_notifications');
		$data['error_no_news'] = $this->language->get('error_no_news');
		$data['error_batch_number'] = $this->language->get('error_batch_number');
		$data['error_min_item_id'] = $this->language->get('error_min_item_id');

		if (!empty($this->session->data['import_mod_error']['errstr'])) {
			$this->error['warning'] = $this->session->data['import_mod_error']['errstr'];
		}

 		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
			if (!empty($this->session->data['import_mod_nochange'])) {
				$data['error_warning'] .= "<br />\n".$this->language->get( 'text_nochange' );
			}
		} else {
			$data['error_warning'] = '';
		}

		unset($this->session->data['import_mod_error']);
		unset($this->session->data['import_mod_nochange']);

		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];
		
			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}

		$data['breadcrumbs'] = array();
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'token=' . $this->session->data['token'], $this->ssl)
		);
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('tool/import_mod', 'token=' . $this->session->data['token'], $this->ssl)
		);

		$data['back'] = $this->url->link('common/dashboard', 'token=' . $this->session->data['token'], $this->ssl);
		$data['button_back'] = $this->language->get( 'button_back' );
		$data['settings'] = $this->url->link('tool/import_mod/settings', 'token=' . $this->session->data['token'], $this->ssl);
		$data['post_max_size'] = $this->return_bytes( ini_get('post_max_size') );
		$data['upload_max_filesize'] = $this->return_bytes( ini_get('upload_max_filesize') );

		if (isset($this->request->post['range_type'])) {
			$data['range_type'] = $this->request->post['range_type'];
		} else {
			$data['range_type'] = 'id';
		}

		if (isset($this->request->post['min'])) {
			$data['min'] = $this->request->post['min'];
		} else {
			$data['min'] = '';
		}

		if (isset($this->request->post['max'])) {
			$data['max'] = $this->request->post['max'];
		} else {
			$data['max'] = '';
		}

		if (isset($this->request->post['incremental'])) {
			$data['incremental'] = $this->request->post['incremental'];
		} else {
			$data['incremental'] = '1';
		}

		if (isset($this->request->post['import_mod_settings_use_option_id'])) {
			$data['settings_use_option_id'] = $this->request->post['import_mod_settings_use_option_id'];
		} else if ($this->config->get( 'import_mod_settings_use_option_id' )) {
			$data['settings_use_option_id'] = '1';
		} else {
			$data['settings_use_option_id'] = '0';
		}

		if (isset($this->request->post['import_mod_settings_use_option_value_id'])) {
			$data['settings_use_option_value_id'] = $this->request->post['import_mod_settings_use_option_value_id'];
		} else if ($this->config->get( 'import_mod_settings_use_option_value_id' )) {
			$data['settings_use_option_value_id'] = '1';
		} else {
			$data['settings_use_option_value_id'] = '0';
		}

		if (isset($this->request->post['import_mod_settings_use_attribute_group_id'])) {
			$data['settings_use_attribute_group_id'] = $this->request->post['import_mod_settings_use_attribute_group_id'];
		} else if ($this->config->get( 'import_mod_settings_use_attribute_group_id' )) {
			$data['settings_use_attribute_group_id'] = '1';
		} else {
			$data['settings_use_attribute_group_id'] = '0';
		}

		if (isset($this->request->post['import_mod_settings_use_attribute_id'])) {
			$data['settings_use_attribute_id'] = $this->request->post['import_mod_settings_use_attribute_id'];
		} else if ($this->config->get( 'import_mod_settings_use_attribute_id' )) {
			$data['settings_use_attribute_id'] = '1';
		} else {
			$data['settings_use_attribute_id'] = '0';
		}

		if (isset($this->request->post['import_mod_settings_use_filter_group_id'])) {
			$data['settings_use_filter_group_id'] = $this->request->post['import_mod_settings_use_filter_group_id'];
		} else if ($this->config->get( 'import_mod_settings_use_filter_group_id' )) {
			$data['settings_use_filter_group_id'] = '1';
		} else {
			$data['settings_use_filter_group_id'] = '0';
		}

		if (isset($this->request->post['import_mod_settings_use_filter_id'])) {
			$data['settings_use_filter_id'] = $this->request->post['import_mod_settings_use_filter_id'];
		} else if ($this->config->get( 'import_mod_settings_use_filter_id' )) {
			$data['settings_use_filter_id'] = '1';
		} else {
			$data['settings_use_filter_id'] = '0';
		}

		if (isset($this->request->post['import_mod_settings_use_import_cache'])) {
			$data['settings_use_import_cache'] = $this->request->post['import_mod_settings_use_import_cache'];
		} else if ($this->config->get( 'import_mod_settings_use_import_cache' )) {
			$data['settings_use_import_cache'] = '1';
		} else {
			$data['settings_use_import_cache'] = '0';
		}

		$min_product_id = $this->model_tool_import_mod->getMinProductId();
		$max_product_id = $this->model_tool_import_mod->getMaxProductId();
		$count_product = $this->model_tool_import_mod->getCountProduct();
		$min_category_id = $this->model_tool_import_mod->getMinCategoryId();
		$max_category_id = $this->model_tool_import_mod->getMaxCategoryId();
		$count_category = $this->model_tool_import_mod->getCountCategory();
		
		$data['min_product_id'] = $min_product_id;
		$data['max_product_id'] = $max_product_id;
		$data['count_product'] = $count_product;
		$data['min_category_id'] = $min_category_id;
		$data['max_category_id'] = $max_category_id;
		$data['count_category'] = $count_category;
		
		$data['create_source'] = $this->url->link('tool/import_mod/create_source', 'token=' . $this->session->data['token'], $this->ssl);
		$data['source_update_link'] = $this->url->link('tool/import_mod/update_source', '&token=' . $this->session->data['token'], $this->ssl);
		$data['source_partial_update_link'] = $this->url->link('tool/import_mod/partial_update_source', '&token=' . $this->session->data['token'], $this->ssl);
		$data['source_enable_link'] = $this->url->link('tool/import_mod/enable_source', '&token=' . $this->session->data['token'], $this->ssl);
		$data['source_disable_link'] = $this->url->link('tool/import_mod/disable_source', '&token=' . $this->session->data['token'], $this->ssl);
		$data['create_man_transcode'] = $this->url->link('tool/import_mod/create_man_transcode', '&token=' . $this->session->data['token'], $this->ssl);
		$data['create_cat_transcode'] = $this->url->link('tool/import_mod/create_cat_transcode', '&token=' . $this->session->data['token'], $this->ssl);

		$data['languages'] = $this->model_tool_import_mod->getLanguages();
		
		$data['sources'] = $this->model_tool_import_mod->getSources();
		$data['man_transcodes'] = $this->model_tool_import_mod->getManufacturerTranscodes();
		$data['cat_transcodes'] = $this->model_tool_import_mod->getCategoryTranscodes();

		$data['token'] = $this->session->data['token'];

		$this->document->addStyle('view/stylesheet/import_mod.css');

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view( ((version_compare(VERSION, '2.2.0.0') >= 0) ? 'tool/import_mod' : 'tool/import_mod.tpl'), $data));
	}
	
	public function create_source() {
		$this->load->language('tool/import_mod');
		$this->document->setTitle($this->language->get('heading_title'));
		$this->load->model('tool/import_mod');
		if (($this->request->server['REQUEST_METHOD'] == 'POST') && ($this->validateNewSourceForm())) {
			if(!isset($this->request->post['source_name'])) {
				die();
			}
			$source_name = $this->request->post['source_name'];
			$source_type = ($this->request->post['source_type']) ? 'file' : 'url';
			if($source_type == 'file') {
				if ( isset( $this->request->files['source_file'] )
				&& is_uploaded_file( $this->request->files['source_file']['tmp_name'] ) 
				&& isset( $this->request->files['match_file'] )
				&& is_uploaded_file( $this->request->files['match_file']['tmp_name'] ) ) {
					
					$source_file = $this->request->files['source_file']['tmp_name'];
					$ext = end((explode(".", $this->request->files['source_file']['name'])));
					$source_file_path = $this->model_tool_import_mod->uploadFile($source_file, $ext);
					if(!$source_file_path) {
						echo 'error source';
						die();
					}
					
					$match_file = $this->request->files['match_file']['tmp_name'];
					$ext = end((explode(".", $this->request->files['match_file']['name'])));
					$match_file_path = $this->model_tool_import_mod->uploadFile($match_file, $ext);
					if(!$match_file_path) {
						echo 'error match';
						die();
					}
					
					$this->model_tool_import_mod->storeSourceIntoDatabase($source_name, "", $source_file_path, $match_file_path, 1);
					
				} else {
					die();
				}
			} else {
				if( isset($this->request->post['source_url']) 
				&& isset( $this->request->files['match_file'] )
				&& is_uploaded_file( $this->request->files['match_file']['tmp_name'] ) ) {
					
					$source_url = $this->request->post['source_url'];
					$source_file_path = $this->model_tool_import_mod->uploadXMLFromURL($source_url);
					if(!$source_file_path) {
						echo 'error source';
						die();
					}
					
					$match_file = $this->request->files['match_file']['tmp_name'];
					$ext = end((explode(".", $this->request->files['match_file']['name'])));
					$match_file_path = $this->model_tool_import_mod->uploadFile($match_file, $ext);
					if(!$match_file_path) {
						echo 'error match';
						die();
					}
					
					$this->model_tool_import_mod->storeSourceIntoDatabase($source_name, $source_url, $source_file_path, $match_file_path, 1);
					
				}
			}

			$this->response->redirect($this->url->link('tool/import_mod', 'token=' . $this->session->data['token'], $this->ssl));

			/*if (   isset( $this->request->files['source_file'] ) || isset( $this->request->post['source_file'] )
				&& isset( $this->request->files['match_file'] ) 
				&& is_uploaded_file( $this->request->files['source_file']['tmp_name'] )
				&& is_uploaded_file( $this->request->files['match_file']['tmp_name'] )) {
				$file = $this->request->files['upload']['tmp_name'];
				$incremental = ($this->request->post['incremental']) ? true : false;
				if ($this->model_tool_export_import->upload($file,$this->request->post['incremental'])==true) {
					$this->session->data['success'] = $this->language->get('text_success');
					$this->response->redirect($this->url->link('tool/export_import', 'token=' . $this->session->data['token'], $this->ssl));
				}
				else {
					$this->error['warning'] = $this->language->get('error_upload');
					if (defined('VERSION')) {
						if (version_compare(VERSION,'2.1.0.0') > 0) {
							$this->error['warning'] .= "<br />\n".$this->language->get( 'text_log_details_2_1_x' );
						} else
							$this->error['warning'] .= "<br />\n".$this->language->get( 'text_log_details_2_0_x' );
					} else {
						$this->error['warning'] .= "<br />\n".$this->language->get( 'text_log_details' );
					}
				}
			}*/
		}

		$this->getForm();
	}
	
	public function update_source() {
		$this->load->language('tool/import_mod');
		$this->document->setTitle($this->language->get('heading_title'));
		$this->load->model('tool/import_mod');
		
		if (($this->request->server['REQUEST_METHOD'] == 'POST') && ($this->validateUpdateSourceForm())) {
			if(!isset($this->request->post['source_id'])) {
				die();
			}
			$source_id = $this->request->post['source_id'];
			$this->model_tool_import_mod->updateFromSource($source_id);
			$this->response->redirect($this->url->link('tool/import_mod', 'token=' . $this->session->data['token'], $this->ssl));
		}
		
	}
	
	public function partial_update_source() {
		$this->load->language('tool/import_mod');
		$this->document->setTitle($this->language->get('heading_title'));
		$this->load->model('tool/import_mod');
		
		if (($this->request->server['REQUEST_METHOD'] == 'POST') && ($this->validateUpdateSourceForm())) {
			if(!isset($this->request->post['source_id'])) {
				die();
			}
			$source_id = $this->request->post['source_id'];
			$this->model_tool_import_mod->updateFromSource($source_id, true);
			$this->response->redirect($this->url->link('tool/import_mod', 'token=' . $this->session->data['token'], $this->ssl));
		}
	}

	public function all_update() {
		$this->load->model('tool/import_mod');
		$sources = $this->model_tool_import_mod->getSources();
		foreach($sources as $source) {
			if(intval($source['status']) == 1) {
				$source_id = $source['source_id'];
				$this->model_tool_import_mod->updateFromSource($source_id);
			}
		}
	}

	public function all_partial_update() {
		$this->load->model('tool/import_mod');
		$sources = $this->model_tool_import_mod->getSources();
		foreach($sources as $source) {
			if(intval($source['status']) == 1) {
				$source_id = $source['source_id'];
				$this->model_tool_import_mod->updateFromSource($source_id, true);
			}
		}
	}
	
	// OPTIMIZE !
	public function enable_source() {
		$this->load->model('tool/import_mod');
		
		if (($this->request->server['REQUEST_METHOD'] == 'POST')) {
			if(!isset($this->request->post['source_id'])) {
				die();
			}
			$source_id = $this->request->post['source_id'];
			$this->model_tool_import_mod->enableSource($source_id);
			$this->response->redirect($this->url->link('tool/import_mod', 'token=' . $this->session->data['token'], $this->ssl));
		}
	}
	// OPTIMIZE !
	public function disable_source() {
		$this->load->model('tool/import_mod');
		
		if (($this->request->server['REQUEST_METHOD'] == 'POST')) {
			if(!isset($this->request->post['source_id'])) {
				die();
			}
			$source_id = $this->request->post['source_id'];
			$this->model_tool_import_mod->disableSource($source_id);
			$this->response->redirect($this->url->link('tool/import_mod', 'token=' . $this->session->data['token'], $this->ssl));
		}
	}
	
	public function create_man_transcode() {
		$this->load->model('tool/import_mod');
		if ($this->request->server['REQUEST_METHOD'] == 'POST') {
			if(isset($this->request->post['man_from']) && isset($this->request->post['man_to']) 
				&& isset($this->request->post['man_code']) && isset($this->request->post['man_start_index'])
				&& isset($this->request->post['man_index_length'])) {
				$from = $this->request->post['man_from'];
				$to = $this->request->post['man_to'];
				$code = $this->request->post['man_code'];
				$start_index = intval($this->request->post['man_start_index']);
				$L1 = isset($this->request->post['man_code_length']) ? intval($this->request->post['man_code_length']) : strlen($code);
				$L2 = intval($this->request->post['man_index_length']);

				if($from != "" && $to != "" && $start_index >= 0 && $L1 > 0 && $L2 > 0) {
					$this->model_tool_import_mod->storeManTranscodeIntoDatabase($from, $to, $code, $start_index, $L1, $L2);
				}
			}
		}

		if(isset($this->request->post['redirect_to'])) {
			$this->response->redirect($this->url->link('tool/import_mod', 'token=' . $this->session->data['token'] . '#' . $this->request->post['redirect_to'], $this->ssl));
		} else {
			$this->response->redirect($this->url->link('tool/import_mod', 'token=' . $this->session->data['token'], $this->ssl));
		}
		
	}

	public function create_cat_transcode() {
		$this->load->model('tool/import_mod');
		if ($this->request->server['REQUEST_METHOD'] == 'POST') {
			if(isset($this->request->post['cat_from']) && isset($this->request->post['cat_to'])) {
				$from = $this->request->post['cat_from'];
				$to = $this->request->post['cat_to'];

				if($from != "" && $to != "") {
					$this->model_tool_import_mod->storeCatTranscodeIntoDatabase($from, $to);
				}
			}
		}

		if(isset($this->request->post['redirect_to'])) {
			$this->response->redirect($this->url->link('tool/import_mod', 'token=' . $this->session->data['token'] . '#' . $this->request->post['redirect_to'], $this->ssl));
		} else {
			$this->response->redirect($this->url->link('tool/import_mod', 'token=' . $this->session->data['token'], $this->ssl));
		}
	}

	//TODO: Сделать нормальные проверки
	protected function validateNewSourceForm() {
		
		return true;
		
		if (!$this->user->hasPermission('modify', 'tool/export_import')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (!isset($this->request->files['upload']['name'])) {
			if (isset($this->error['warning'])) {
				$this->error['warning'] .= "<br /\n" . $this->language->get( 'error_upload_name' );
			} else {
				$this->error['warning'] = $this->language->get( 'error_upload_name' );
			}
		} else {
			$ext = strtolower(pathinfo($this->request->files['upload']['name'], PATHINFO_EXTENSION));
			if (($ext != 'xls') && ($ext != 'xlsx') && ($ext != 'ods')) {
				if (isset($this->error['warning'])) {
					$this->error['warning'] .= "<br /\n" . $this->language->get( 'error_upload_ext' );
				} else {
					$this->error['warning'] = $this->language->get( 'error_upload_ext' );
				}
			}
		}

		if (!$this->error) {
			return true;
		} else {
			return false;
		}
	}
	
	//TODO: Сделать нормальные проверки
	protected function validateUpdateSourceForm() {
		
		return true;
		
		if (!$this->user->hasPermission('modify', 'tool/import_mod')) {
			$this->error['warning'] = $this->language->get('error_permission');
		} else if (!isset( $this->request->post['incremental'] )) {
			$this->error['warning'] = $this->language->get( 'error_incremental' );
		} else if ($this->request->post['incremental'] != '0') {
			if ($this->request->post['incremental'] != '1') {
				$this->error['warning'] = $this->language->get( 'error_incremental' );
			}
		}

		if (!isset($this->request->files['upload']['name'])) {
			if (isset($this->error['warning'])) {
				$this->error['warning'] .= "<br /\n" . $this->language->get( 'error_upload_name' );
			} else {
				$this->error['warning'] = $this->language->get( 'error_upload_name' );
			}
		} else {
			$ext = strtolower(pathinfo($this->request->files['upload']['name'], PATHINFO_EXTENSION));
			if (($ext != 'xls') && ($ext != 'xlsx') && ($ext != 'ods')) {
				if (isset($this->error['warning'])) {
					$this->error['warning'] .= "<br /\n" . $this->language->get( 'error_upload_ext' );
				} else {
					$this->error['warning'] = $this->language->get( 'error_upload_ext' );
				}
			}
		}

		if (!$this->error) {
			return true;
		} else {
			return false;
		}
	}
	
	protected function return_bytes($val) {
		$val = trim($val);
	
		switch (strtolower(substr($val, -1)))
		{
			case 'm': $val = (int)substr($val, 0, -1) * 1048576; break;
			case 'k': $val = (int)substr($val, 0, -1) * 1024; break;
			case 'g': $val = (int)substr($val, 0, -1) * 1073741824; break;
			case 'b':
				switch (strtolower(substr($val, -2, 1)))
				{
					case 'm': $val = (int)substr($val, 0, -2) * 1048576; break;
					case 'k': $val = (int)substr($val, 0, -2) * 1024; break;
					case 'g': $val = (int)substr($val, 0, -2) * 1073741824; break;
					default : break;
				} break;
			default: break;
		}
		return $val;
	}
}
?>