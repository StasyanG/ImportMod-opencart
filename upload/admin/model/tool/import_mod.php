<?php

static $registry = null;

// Error Handler
function error_handler_for_import_mod($errno, $errstr, $errfile, $errline) {
	global $registry;
	
	switch ($errno) {
		case E_NOTICE:
		case E_USER_NOTICE:
			$errors = "Notice";
			break;
		case E_WARNING:
		case E_USER_WARNING:
			$errors = "Warning";
			break;
		case E_ERROR:
		case E_USER_ERROR:
			$errors = "Fatal Error";
			break;
		default:
			$errors = "Unknown";
			break;
	}
	
	$config = $registry->get('config');
	$url = $registry->get('url');
	$request = $registry->get('request');
	$session = $registry->get('session');
	$log = $registry->get('log');
	
	if ($config->get('config_error_log')) {
		$log->write('PHP ' . $errors . ':  ' . $errstr . ' in ' . $errfile . ' on line ' . $errline);
	}

	if (($errors=='Warning') || ($errors=='Unknown')) {
		return true;
	}

	if (($errors != "Fatal Error") && isset($request->get['route']) && ($request->get['route']!='tool/import_mod/download'))  {
		if ($config->get('config_error_display')) {
			echo '<b>' . $errors . '</b>: ' . $errstr . ' in <b>' . $errfile . '</b> on line <b>' . $errline . '</b>';
		}
	} else {
		$session->data['import_mod_error'] = array( 'errstr'=>$errstr, 'errno'=>$errno, 'errfile'=>$errfile, 'errline'=>$errline );
		$token = $request->get['token'];
		$link = $url->link( 'tool/import_mod', 'token='.$token, 'SSL' );
		header('Status: ' . 302);
		header('Location: ' . str_replace(array('&amp;', "\n", "\r"), array('&', '', ''), $link));
		exit();
	}

	return true;
}

function fatal_error_shutdown_handler_for_import_mod() {
	$last_error = error_get_last();
	if ($last_error['type'] === E_ERROR) {
		// fatal error
		error_handler_for_import_mod(E_ERROR, $last_error['message'], $last_error['file'], $last_error['line']);
	}
}

class ModelToolImportMod extends Model {

	private $error = array();
	protected $null_array = array();
	
	private $modFolder = 'import_mod/';
	private $dirFiles = "files/";
	
	private $Count = 0;
	private $MaxCount = 0;
	
	public function install() {
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "import_mod_sources` (
		  `source_id` int(11) NOT NULL AUTO_INCREMENT,
		  `url` text,
		  `name` text NOT NULL,
		  `path` text,
		  `match_file` text,
		  `last_updated` datetime,
		  `status` tinyint(1) NOT NULL,
		  PRIMARY KEY (`source_id`)
		) DEFAULT CHARSET=utf8;");

		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "import_mod_matches` (
		  `match_id` int(11) NOT NULL AUTO_INCREMENT,
		  `source_id` int(11) NOT NULL,
		  `type` int(10),
		  `remote_id` int(11) NOT NULL,
		  `local_id` int(11) NOT NULL,
		  `last_updated` datetime,
		  PRIMARY KEY (`match_id`),
		  FOREIGN KEY (`source_id`) REFERENCES `" . DB_PREFIX . "import_mod_sources`(`source_id`)
		) DEFAULT CHARSET=utf8;");
		
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "images_queue` (
		  `id` int(11) NOT NULL AUTO_INCREMENT,
		  `uri` text NOT NULL,
		  `local_path` text NOT NULL,
		  PRIMARY KEY (`id`)
		) DEFAULT CHARSET=utf8;");
		
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "import_mod_man_transcodes` (
		  `from` varchar(50) NOT NULL,
		  `to` varchar(50) NOT NULL,
		  `code` varchar(16),
		  `start_index` int(11),
		  `L1` int(11),
		  `L2` int(11),
		  PRIMARY KEY (`from`)
		) DEFAULT CHARSET=utf8;");
		
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "import_mod_cat_transcodes` (
		  `name_from` varchar(100) NOT NULL,
		  `name_to` varchar(100) NOT NULL,
		  PRIMARY KEY (`name_from`)
		) DEFAULT CHARSET=utf8;");
		
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "import_mod_model_transcodes` (
		  `man_name` varchar(64) NOT NULL,
		  `old_model` varchar(64) NOT NULL,
		  `new_model` varchar(64) NOT NULL,
		  PRIMARY KEY (`man_name`, `old_model`)
		) DEFAULT CHARSET=utf8;");

		if (!file_exists(DIR_DOWNLOAD . $this->modFolder . $this->dirFiles)) {
		    mkdir(DIR_DOWNLOAD . $this->modFolder . $this->dirFiles, 0777, true);
		}
	}
	
	public function getSources() {
		$query = $this->db->query( "SELECT * FROM `".DB_PREFIX."import_mod_sources`" );
		$sources = array();
		foreach($query->rows as $result){
		    $sources[] = $result;
		}
		return $sources;
	}

	public function getManufacturerTranscodes() {
		$manufacturer_ids = array();
		$sql  = "SELECT * FROM `".DB_PREFIX."import_mod_man_transcodes`";
		$result = $this->db->query( $sql );
		$manufacturers = array();
		foreach ($result->rows as $row) {
			$manufacturers[$row['from']] = array(
				'to' => $row['to'],
				'code' => $row['code'],
				'start_index' => $row['start_index'],
				'L1' => $row['L1'],
				'L2' => $row['L2']
			);
		}
		return $manufacturers;
	}

	public function getCategoryTranscodes() {
		$category_ids = array();
		$sql  = "SELECT * FROM `".DB_PREFIX."import_mod_cat_transcodes`";
		$result = $this->db->query( $sql );
		$categories = array();
		foreach ($result->rows as $row) {
			$categories[$row['name_to']] = $row['name_from'];
		}
		return $categories;
	}
	
	public function enableSource( $source_id ) {
		$sql = "SELECT `local_id` FROM `".DB_PREFIX."import_mod_matches` WHERE `type`=1 AND `source_id`=$source_id;";
		$q = $this->db->query( $sql );
		$ids = array();
		foreach($q->rows as $row){
		    $this->enableProduct($row['local_id']);
		}
		$sql = "UPDATE `".DB_PREFIX."import_mod_sources` SET `status`=1 WHERE `source_id`=$source_id;";
		$q = $this->db->query( $sql );
	}
	
	public function disableSource( $source_id ) {
		$sql = "SELECT `local_id` FROM `".DB_PREFIX."import_mod_matches` WHERE `type`=1 AND `source_id`=$source_id;";
		$q = $this->db->query( $sql );
		foreach($q->rows as $row){
		    $this->disableProduct($row['local_id']);
		}
		$sql = "UPDATE `".DB_PREFIX."import_mod_sources` SET `status`=0 WHERE `source_id`=$source_id;";
		$q = $this->db->query( $sql );
	}
	
	public function updateFromSource($source_id, $partial = false) {
		$id = $this->db->escape($source_id);
		$query = $this->db->query( "SELECT * FROM `".DB_PREFIX."import_mod_sources` WHERE `source_id`=" . $id . ";");
		if($query->num_rows > 0) {
			$status = $query->row['status'];
			if(!$status) {
				exit('Source status is 0');
			}
			$source_url = $query->row['url'];
			$input_filename = $query->row['path'];
			$match_filename = $query->row['match_file'];
			
			// if URL is not empty, then download file to files directory and use it
			if ($source_url != "") {
				$new_path = $this->uploadXMLFromURL($source_url);
				// if file was not downloaded then we don't need to update
				// exit with error
				if(!$new_path) {
					exit('Could not download new file from URL ' . $source_url);
				} else {
					// update database with new path
					$sql = "UPDATE `".DB_PREFIX."import_mod_sources` SET `path`='$new_path' WHERE `source_id`=$id;";
					$q = $this->db->query( $sql );
					if(!$q) {
						unlink(DIR_DOWNLOAD . $this->modFolder . $new_path);
						exit('Could not update database');
					}
					// if another file for this source exists then delete it
					if($input_filename != "") {
						unlink(DIR_DOWNLOAD . $this->modFolder . $input_filename);
					}
					$input_filename = $new_path;
				}
			}
			
			if (!file_exists(DIR_DOWNLOAD . $this->modFolder . $input_filename)) {
				exit('Could not open file ' . DIR_DOWNLOAD . $this->modFolder . $input_filename);
			}
			
			$inputXml = simplexml_load_file(DIR_DOWNLOAD . $this->modFolder . $input_filename);
			
			if (!file_exists(DIR_DOWNLOAD . $this->modFolder . $match_filename)) {
				exit('Could not open file ' . DIR_DOWNLOAD . $this->modFolder . $match_filename);
			}
			
			$json = file_get_contents(DIR_DOWNLOAD . $this->modFolder . $match_filename);
			$matchArray = json_decode($json, true);
			
			
			if(!$partial) {
				$this->updateCategories($id, $inputXml, $matchArray);
				$this->updateProducts($id, $inputXml, $matchArray);
				
				$sql = "UPDATE `".DB_PREFIX."import_mod_sources` SET `last_updated`=NOW() WHERE `source_id`=$id;";
				$q = $this->db->query( $sql );
			} else {
				$this->partialUpdateProducts($id, $inputXml, $matchArray);
				
				$sql = "UPDATE `".DB_PREFIX."import_mod_sources` SET `last_updated`=NOW() WHERE `source_id`=$id;";
				$q = $this->db->query( $sql );
			}
				
			$this->load->model('tool/image');
			$cnt = $this->model_tool_image->processImageQueue();
			echo 'Successfully downloaded '.$cnt.' images.<br />'."\n";
			
			
		} else {
			exit('SQL error SOURCES');
		}
	}
	
	public function getMaxProductId() {
		$query = $this->db->query( "SELECT MAX(product_id) as max_product_id FROM `".DB_PREFIX."product`" );
		if (isset($query->row['max_product_id'])) {
			$max_id = $query->row['max_product_id'];
		} else {
			$max_id = 0;
		}
		return $max_id;
	}

	public function getMinProductId() {
		$query = $this->db->query( "SELECT MIN(product_id) as min_product_id FROM `".DB_PREFIX."product`" );
		if (isset($query->row['min_product_id'])) {
			$min_id = $query->row['min_product_id'];
		} else {
			$min_id = 0;
		}
		return $min_id;
	}
	
	public function getCountProduct() {
		$query = $this->db->query( "SELECT COUNT(product_id) as count_product FROM `".DB_PREFIX."product`" );
		if (isset($query->row['count_product'])) {
			$count = $query->row['count_product'];
		} else {
			$count = 0;
		}
		return $count;
	}  
 
	public function getMaxCategoryId() {
		$query = $this->db->query( "SELECT MAX(category_id) as max_category_id FROM `".DB_PREFIX."category`" );
		if (isset($query->row['max_category_id'])) {
			$max_id = $query->row['max_category_id'];
		} else {
			$max_id = 0;
		}
		return $max_id;
	}

	public function getMinCategoryId() {
		$query = $this->db->query( "SELECT MIN(category_id) as min_category_id FROM `".DB_PREFIX."category`" );
		if (isset($query->row['min_category_id'])) {
			$min_id = $query->row['min_category_id'];
		} else {
			$min_id = 0;
		}
		return $min_id;
	}

	public function getCountCategory() {
		$query = $this->db->query( "SELECT COUNT(category_id) as count_category FROM `".DB_PREFIX."category`" );
		if (isset($query->row['count_category'])) {
			$count = $query->row['count_category'];
		} else {
			$count = 0;
		}
		return $count;
	}
	
	protected function updateCategories(&$id, &$inputXml, &$matchArray) {
		if(array_key_exists('categories_container', $matchArray)
		&& array_key_exists('category_item', $matchArray)
		&& array_key_exists('category_info', $matchArray)) {
			$this->Count = 0;
			$categories_container = $inputXml->xpath($matchArray['categories_container']);
			if($categories_container == FALSE) {
				exit('ERROR categories_container');
			}
			$xml_categories = $categories_container[0]->xpath($matchArray['category_item']);
			if($xml_categories == FALSE) {
				exit('ERROR categories');
			}
			
			// Opencart versions from 2.0 onwards also have category_description.meta_title
			$sql = "SHOW COLUMNS FROM `".DB_PREFIX."category_description` LIKE 'meta_title'";
			$query = $this->db->query( $sql );
			$exist_meta_title = ($query->num_rows > 0) ? true : false;
			
			// get old url_alias_ids
			$url_alias_ids = $this->getCategoryUrlAliasIds();
			
			$old_category_ids = $this->getAvailableCategoryIds();
			//$this->deleteCategories($url_alias_ids);
			//$this->deleteSourceCategories($id);
			
			// get pre-defined layouts
			$layout_ids = $this->getLayoutIds();
	
			// get pre-defined store_ids
			$available_store_ids = $this->getAvailableStoreIds();
	
			// find the installed languages
			$languages = $this->getLanguages();
			
			$cat_transcodes = $this->getCategoryTranscodes();
			
			foreach($xml_categories as $category) {
				$category_id = array_key_exists('category_id', $matchArray['category_info']) ? $this->get($category, $matchArray['category_info']['category_id']) : null;
				if($category_id == null || $category_id == "") {
					exit('ERROR category_id is NULL');
				}
				$category_id = trim($category_id);
				$parent_id = array_key_exists('parent_id', $matchArray['category_info']) ? $this->get($category, $matchArray['category_info']['parent_id']) : '0';
				
				$found_id_by_name = false;
				$names = array();
				$names_from_match = $this->array_filter_key($matchArray['category_info'], function($key) {
				    return strpos($key, 'name(') === 0;
				});
				foreach($names_from_match as $name_d => $name_i) {
					$lang_code = substr($name_d,strlen("name("),strlen($name_d)-strlen("name(")-1);
					$name = $this->get($category, $matchArray['category_info'][$name_d]);
					$name = htmlspecialchars( $name );
					
					$cat_transcode = false;
					foreach($cat_transcodes as $to => $from) {
						if(!strcmp($name, $from)) {
							$cat_transcode = $to;
							break;
						}
					}
					
					if($cat_transcode) {
						$name = $cat_transcode;
					}
					
					$names[$lang_code] = $name;
					if(!$found_id_by_name)
						$found_id_by_name = $this->findCategoryByName( $name );
				}
				
				if($found_id_by_name) {
					$sql = "SELECT `source_id` FROM `".DB_PREFIX."import_mod_matches` WHERE type=0 AND local_id=".$found_id_by_name.";";
					$res = $this->db->query( $sql );
					if($res->num_rows > 0) {
						$bFound = false;
						foreach($res->rows as $row) {
							$sid = $row['source_id'];
							if(strval($sid) == strval($id)) {
								$bFound = true;
								break;
							}
						}
						if($bFound == false) {
							$this->deleteMatches( $id, 0, (int)$category_id);
							$match_id = $this->storeMatchIntoDatabase( $id, 0, (int)$category_id, $found_id_by_name );
							continue;
						}
					}
				}
				
				$top = array_key_exists('top', $matchArray['category_info']) ? $this->get($category, $matchArray['category_info']['top']) : 'true';
				$columns = array_key_exists('columns', $matchArray['category_info']) ? $this->get($category, $matchArray['category_info']['columns']) : '1';
				$sort_order = array_key_exists('sort_order', $matchArray['category_info']) ? $this->get($category, $matchArray['category_info']['sort_order']) : '1';
				
				$image_name = array_key_exists('image_name', $matchArray['category_info']) ? $this->get($category, $matchArray['category_info']['image_name']) : '';
				// If image_name is URL then download it to local location
				if (filter_var($image_name, FILTER_VALIDATE_URL) !== FALSE) {
					$this->load->model('tool/image');
				    $uurrll = $this->model_tool_image->downloadImageFromURL($image_name);
				    if($uurrll != null) {
				    	$image_name = $uurrll;
				    } else {
				    	$image_name = 'placeholder.png';
				    }
				}
				
				$date_added = array_key_exists('date_added', $matchArray['category_info']) ? $this->get($category, $matchArray['category_info']['date_added']) : '';
				$date_added = trim($date_added);
				$date_added = ((is_string($date_added)) && (strlen($date_added)>0)) ? $date_added : "NOW()";
				$date_modified = array_key_exists('date_modified', $matchArray['category_info']) ? $this->get($category, $matchArray['category_info']['date_modified']) : '';
				$date_modified = trim($date_modified);
				$date_modified = ((is_string($date_modified)) && (strlen($date_modified)>0)) ? $date_modified : "NOW()";
				$seo_keyword = array_key_exists('seo_keyword', $matchArray['category_info']) ? $this->get($category, $matchArray['category_info']['seo_keyword']) : '';
				
				$descriptions = array();
				$descriptions_from_match = $this->array_filter_key($matchArray['category_info'], function($key) {
				    return strpos($key, 'description(') === 0;
				});
				foreach($descriptions_from_match as $desc_d => $desc_i) {
					$lang_code = substr($desc_d,strlen("description("),strlen($desc_d)-strlen("description(")-1);
					$desc = $this->get($category, $matchArray['category_info'][$desc_d]);
					$desc = htmlspecialchars( $desc );
					$descriptions[$lang_code] = $desc;
				}
				
				if ($exist_meta_title) {
					$meta_titles = array();
					$meta_titles_from_match = $this->array_filter_key($matchArray['category_info'], function($key) {
					    return strpos($key, 'meta_title(') === 0;
					});
					foreach($meta_titles_from_match as $meta_d => $meta_i) {
						$lang_code = substr($meta_d,strlen("meta_title("),strlen($meta_d)-strlen("meta_title(")-1);
						$meta = $this->get($category, $matchArray['category_info'][$meta_d]);
						$meta = htmlspecialchars( $meta );
						
						$cat_transcode = false;
						foreach($cat_transcodes as $to => $from) {
							if(!strcmp($meta, $from)) {
								$cat_transcode = $to;
								break;
							}
						}
						
						if($cat_transcode) {
							$meta = $cat_transcode;
						}
						
						$meta_titles[$lang_code] = $meta;
					}
					
					if($meta_titles_from_match == null) {
						foreach($names_from_match as $name_d => $name_i) {
							$lang_code = substr($name_d,strlen("name("),strlen($name_d)-strlen("name(")-1);
							$name = $this->get($category, $matchArray['category_info'][$name_d]);
							$name = htmlspecialchars( $name );
							
							$cat_transcode = false;
							foreach($cat_transcodes as $to => $from) {
								if(!strcmp($name, $from)) {
									$cat_transcode = $to;
									break;
								}
							}
							
							if($cat_transcode) {
								$name = $cat_transcode;
							}
							
							$meta_titles[$lang_code] = $name;
						}
					}
					
				}
				$meta_descriptions = array();
				$meta_descriptions_from_match = $this->array_filter_key($matchArray['category_info'], function($key) {
				    return strpos($key, 'meta_description(') === 0;
				});
				foreach($meta_descriptions_from_match as $meta_d => $meta_i) {
					$lang_code = substr($meta_d,strlen("meta_description("),strlen($meta_d)-strlen("meta_description(")-1);
					$meta = $this->get($category, $matchArray['category_info'][$meta_d]);
					$meta = htmlspecialchars( $meta );
					$meta_descriptions[$lang_code] = $meta;
				}
				$meta_keywords = array();
				$meta_keywords_from_match = $this->array_filter_key($matchArray['category_info'], function($key) {
				    return strpos($key, 'meta_keywords(') === 0;
				});
				foreach($meta_keywords_from_match as $meta_d => $meta_i) {
					$lang_code = substr($meta_d,strlen("meta_keywords("),strlen($meta_d)-strlen("meta_keywords(")-1);
					$meta = $this->get($category, $matchArray['category_info'][$meta_d]);
					$meta = htmlspecialchars( $meta );
					$meta_keywords[$lang_code] = $meta;
				}
				
				$store_ids = array_key_exists('store_ids', $matchArray['category_info']) ? $this->get($category, $matchArray['category_info']['store_ids']) : '0';
				$store_ids = trim( $this->clean($store_ids, false) );
				
				$layout = array_key_exists('layout', $matchArray['category_info']) ? $this->get($category, $matchArray['category_info']['layout']) : '';
				$status = array_key_exists('status', $matchArray['category_info']) ? $this->get($category, $matchArray['category_info']['status']) : 'true';

				$cat = array();
				$is_category_new = true;
				$found_local_id = $this->findLocalId((int)$category_id, $id, 0);
				if($found_local_id) {
					$cat["category_id"] = $found_local_id;
					$is_category_new = false;
				} else {
					$cat["category_id"] = (int)$category_id;
				}
				$found_local_id = $this->findLocalId((int)$parent_id, $id, 0);
				if($found_local_id) {
					$cat["parent_id"] = $found_local_id;
				} else {
					$cat["parent_id"] = (int)$parent_id;
				}
				$cat["names"] = $names;
				$cat["top"] = $top;
				$cat["columns"] = $columns;
				$cat["sort_order"] = $sort_order;
				$cat['image'] = $image_name;
				$cat["date_added"] = $date_added;
				$cat["date_modified"] = $date_modified;
				$cat["seo_keyword"] = $seo_keyword;
				$cat["descriptions"] = $descriptions;
				if ($exist_meta_title) {
					$cat['meta_titles'] = $meta_titles;
				}
				$cat['meta_descriptions'] = $meta_descriptions;
				$cat['meta_keywords'] = $meta_keywords;
				$cat['store_ids'] = ($store_ids=="") ? array() : explode( ",", $store_ids );
				if ($cat['store_ids']===false) {
					$cat['store_ids'] = array();
				}
				$cat['layout'] = ($layout=="") ? array() : explode( ",", $layout );
				if ($cat['layout']===false) {
					$cat['layout'] = array();
				}
				$cat['status'] = $status;
				
				if(!$is_category_new) {
					$this->deleteCategory( $cat["category_id"] );
				}

				$inserted_id = $this->storeCategoryIntoDatabase( $is_category_new, $cat, $languages, $exist_meta_title, $layout_ids, $available_store_ids, $url_alias_ids );
				
				if(!$inserted_id) {
					exit('Category was not inserted: '.json_encode($cat));
				}
				
				$match_id = $this->storeMatchIntoDatabase( $id, 0, (int)$category_id, $inserted_id );
				
				if(!$match_id) {
					exit('Category was inserted, but not matched: '.json_encode($cat));
				}
				$this->Count++;
			}
			echo 'Updated '.$this->Count.' categories.<br />'."\n";
		} else {
			exit('ERROR match file '. DIR_DOWNLOAD . $this->modFolder . $match_filename);
		}
	}
	
	protected function updateProducts(&$id, &$inputXml, &$matchArray) {
		if(array_key_exists('products_container', $matchArray)
		&& array_key_exists('product_item', $matchArray)
		&& array_key_exists('product_info', $matchArray)
		&& array_key_exists('main_currency', $matchArray)
		&& array_key_exists('main_currency_usd_rate', $matchArray)) {
			$this->Count = 0;
			$products_container = $inputXml->xpath($matchArray['products_container']);
			if($products_container == FALSE) {
				echo 'NO products_container<br />';
				return null;
			}
			$xml_products = $products_container[0]->xpath($matchArray['product_item']);
			if($xml_products == FALSE) {
				echo 'NO products<br />';
				return null;
			}
			
			$main_currrency_usd_rate = $matchArray['main_currency_usd_rate'] != null ? (float)$this->get($inputXml, $matchArray['main_currency_usd_rate']) : 1.0;
			
			// save product view counts
			$view_counts = $this->getProductViewCounts();
	
			// save old url_alias_ids
			$url_alias_ids = $this->getProductUrlAliasIds();
	
			// some older versions of OpenCart use the 'product_tag' table
			$exist_table_product_tag = false;
			$query = $this->db->query( "SHOW TABLES LIKE '".DB_PREFIX."product_tag'" );
			$exist_table_product_tag = ($query->num_rows > 0);
	
			// Opencart versions from 2.0 onwards also have product_description.meta_title
			$sql = "SHOW COLUMNS FROM `".DB_PREFIX."product_description` LIKE 'meta_title'";
			$query = $this->db->query( $sql );
			$exist_meta_title = ($query->num_rows > 0) ? true : false;
			
			$available_product_ids = array();
			$old_product_ids = $this->getAvailableProductIds();
			//$this->deleteProducts($exist_table_product_tag,$url_alias_ids);
			//$this->deleteSourceProducts($id, $exist_table_product_tag);
	
			// get pre-defined layouts
			$layout_ids = $this->getLayoutIds();
	
			// get pre-defined store_ids
			$available_store_ids = $this->getAvailableStoreIds();
	
			// find the installed languages
			$languages = $this->getLanguages();
	
			// find the default units
			$default_weight_unit = $this->getDefaultWeightUnit();
			$default_measurement_unit = $this->getDefaultMeasurementUnit();
			$default_stock_status_id = $this->config->get('config_stock_status_id');
			if(!$default_stock_status_id) {
				$default_stock_status_id = 5;
			}
	
			// find existing manufacturers, only newly specified manufacturers will be added
			$manufacturers = $this->getManufacturers();
			
			// get manufacturers transcoding list
			$man_transcodes = $this->getManufacturerTranscodes();
	
			// get weight classes
			$weight_class_ids = $this->getWeightClassIds();
	
			// get length classes
			$length_class_ids = $this->getLengthClassIds();
	
			// get list of the field names, some are only available for certain OpenCart versions
			$query = $this->db->query( "DESCRIBE `".DB_PREFIX."product`" );
			$product_fields = array();
			foreach ($query->rows as $row) {
				$product_fields[] = $row['Field'];
			}
			
			$this->MaxCount = count($xml_products);
			
			foreach($xml_products as $product) {
				$add_imgs = null;
				// get attribute groups
				$attribute_groups = $this->getAttributeGroups();
				// get attributes
				$attributes = $this->getAttributes();
				
				set_time_limit(120);
				$product_id = array_key_exists('product_id', $matchArray['product_info']) && $matchArray['product_info']['product_id'] != null ? $this->get($product, $matchArray['product_info']['product_id']) : null;
				if($product_id == null || $product_id == "") {
					exit('ERROR product_id is NULL');
				}
				$product_id = trim($product_id);
				
				$names = array();
				$names_from_match = $this->array_filter_key($matchArray['product_info'], function($key) {
				    return strpos($key, 'name(') === 0;
				});
				foreach($names_from_match as $name_d => $name_i) {
					$lang_code = substr($name_d,strlen("name("),strlen($name_d)-strlen("name(")-1);
					$name = $this->get($product, $matchArray['product_info'][$name_d]);
					if($name == null) {
						$name = '';
					}
					$name = htmlspecialchars( $name );
					$names[$lang_code] = $name;
				}
				
				$categories = array_key_exists('categories', $matchArray['product_info']) && $matchArray['product_info']['categories'] != null ? $this->get($product, $matchArray['product_info']['categories']) : '';
				$sku = array_key_exists('sku', $matchArray['product_info']) && $matchArray['product_info']['sku'] != null ? $this->get($product, $matchArray['product_info']['sku']) : '';
				$upc = array_key_exists('upc', $matchArray['product_info']) && $matchArray['product_info']['upc'] != null ? $this->get($product, $matchArray['product_info']['upc']) : '';
				if (in_array('ean', $product_fields)) {
					$ean = array_key_exists('ean', $matchArray['product_info']) && $matchArray['product_info']['ean'] != null ? $this->get($product, $matchArray['product_info']['ean']) : '';
				}
				if (in_array('jan', $product_fields)) {
					$jan = array_key_exists('jan', $matchArray['product_info']) && $matchArray['product_info']['jan'] != null ? $this->get($product, $matchArray['product_info']['jan']) : '';
				}
				if (in_array('isbn', $product_fields)) {
					$isbn = array_key_exists('isbn', $matchArray['product_info']) && $matchArray['product_info']['isbn'] != null ? $this->get($product, $matchArray['product_info']['isbn']) : '';
				}
				if (in_array('mpn', $product_fields)) {
					$mpn = array_key_exists('mpn', $matchArray['product_info']) && $matchArray['product_info']['mpn'] != null ? $this->get($product, $matchArray['product_info']['mpn']) : '';
				}
				$location = array_key_exists('location', $matchArray['product_info']) && $matchArray['product_info']['location'] != null ? $this->get($product, $matchArray['product_info']['location']) : '';
				$quantity = array_key_exists('quantity', $matchArray['product_info']) && $matchArray['product_info']['quantity'] != null ? $this->get($product, $matchArray['product_info']['quantity']) : '0';
				$quantity = ((is_string($quantity)) && (strlen($quantity)>0)) ? $quantity : '0';
				
				$model = array_key_exists('model', $matchArray['product_info']) && $matchArray['product_info']['model'] != null ? $this->get($product, $matchArray['product_info']['model']) : '   ';
				$model = ((is_string($model)) && (strlen($model)>0)) ? $model : '   ';
				$manufacturer_name = array_key_exists('manufacturer', $matchArray['product_info']) && $matchArray['product_info']['manufacturer'] != null ? $this->get($product, $matchArray['product_info']['manufacturer']) : '';
				foreach($man_transcodes as $from => $man) {
					if($from == $manufacturer_name) {
						if($man['code'] != null && $man['code'] != '') {
							$start = intval($man['start_index']);
							$new_model = $man['code'].'-'.$this->genIndex($start + $this->Count, $man['L2']);
							if($this->storeModelTranscodeIntoDatabase($manufacturer_name, $model, $new_model)) {
								$model = $new_model;
							}
						}
						$manufacturer_name = $man['to'];
						break;
					}
				}
				
				$images_container = array_key_exists('images_container', $matchArray['product_info']) && $matchArray['product_info']['images_container'] != "" ? $product->xpath($matchArray['product_info']['images_container']) : $product;
				$images = $this->getMultiple($images_container, $matchArray['product_info']['image_item']);
				$image_count = count($images);
				if($image_count > 0) {
					$image_name = $images[0];
					// If image_name is URL then download it to local location
					if (filter_var($image_name, FILTER_VALIDATE_URL) !== FALSE) {
						$this->load->model('tool/image');
					    $uurrll = $this->model_tool_image->downloadImageFromURL($image_name, 0);
					    $image_name = $uurrll;
					}
					if($image_count > 1) {
						$add_imgs = array_slice($images, 1);
					}
				}
				
				$shipping = array_key_exists('shipping', $matchArray['product_info']) && $matchArray['product_info']['shipping'] != null ? $this->get($product, $matchArray['product_info']['shipping']) : 'yes';
				$shipping = ((is_string($shipping)) && (strlen($shipping)>0)) ? $shipping : 'yes';
				$price = array_key_exists('price', $matchArray['product_info']) && $matchArray['product_info']['price'] != null ? $this->get($product, $matchArray['product_info']['price']) : '0.00';
				$price = ((is_string($price)) && (strlen($price)>0)) ? str_replace(',', '.', $price) : '0.00';
				$price = strval((float)$price / $main_currrency_usd_rate);
				$points = array_key_exists('points', $matchArray['product_info']) && $matchArray['product_info']['points'] != null ? $this->get($product, $matchArray['product_info']['points']) : '0';
				$points = ((is_string($points)) && (strlen($points)>0)) ? $points : '0';
				$date_added = array_key_exists('date_added', $matchArray['product_info']) && $matchArray['product_info']['date_added'] != null ? $this->get($product, $matchArray['product_info']['date_added']) : '';
				$date_added = ((is_string($date_added)) && (strlen($date_added)>0)) ? $date_added : "NOW()";
				$date_modified = array_key_exists('date_modified', $matchArray['product_info']) && $matchArray['product_info']['date_modified'] != null ? $this->get($product, $matchArray['product_info']['date_modified']) : '';
				$date_modified = ((is_string($date_modified)) && (strlen($date_modified)>0)) ? $date_modified : "NOW()";
				$date_available = array_key_exists('date_available', $matchArray['product_info']) && $matchArray['product_info']['date_available'] != null ? $this->get($product, $matchArray['product_info']['date_available']) : '';
				$date_available = ((is_string($date_available)) && (strlen($date_available)>0)) ? $date_available : "NOW()";
				$weight = array_key_exists('weight', $matchArray['product_info']) && $matchArray['product_info']['weight'] != null ? $this->get($product, $matchArray['product_info']['weight']) : '0';
				$weight = ((is_string($weight)) && (strlen($weight)>0)) ? $weight : '0';
				$weight_unit = array_key_exists('weight_unit', $matchArray['product_info']) && $matchArray['product_info']['weight_unit'] != null ? $this->get($product, $matchArray['product_info']['weight_unit']) : $default_weight_unit;
				$length = array_key_exists('length', $matchArray['product_info']) && $matchArray['product_info']['length'] != null ? $this->get($product, $matchArray['product_info']['length']) : '0';
				$length = ((is_string($length)) && (strlen($length)>0)) ? $length : '0';
				$width = array_key_exists('width', $matchArray['product_info']) && $matchArray['product_info']['width'] != null ? $this->get($product, $matchArray['product_info']['width']) : '0';
				$width = ((is_string($width)) && (strlen($width)>0)) ? $width : '0';
				$height = array_key_exists('height', $matchArray['product_info']) && $matchArray['product_info']['height'] != null ? $this->get($product, $matchArray['product_info']['height']) : '0';
				$height = ((is_string($height)) && (strlen($height)>0)) ? $height : '0';
				$measurement_unit = array_key_exists('length_unit', $matchArray['product_info']) && $matchArray['product_info']['length_unit'] != null ? $this->get($product, $matchArray['product_info']['length_unit']) : $default_measurement_unit;
				$status = array_key_exists('status', $matchArray['product_info']) && $matchArray['product_info']['status'] != null ? $this->get($product, $matchArray['product_info']['status']) : 'true';
				$status = ((is_string($status)) && (strlen($status)>0)) ? $status : 'true';
				$tax_class_id = array_key_exists('tax_class_id', $matchArray['product_info']) && $matchArray['product_info']['tax_class_id'] != null ? $this->get($product, $matchArray['product_info']['tax_class_id']) : '0';
				$tax_class_id = ((is_string($tax_class_id)) && (strlen($tax_class_id)>0)) ? $tax_class_id : '0';
				$keyword = array_key_exists('seo_keyword', $matchArray['product_info']) && $matchArray['product_info']['seo_keyword'] != null ? $this->get($product, $matchArray['product_info']['seo_keyword']) : '';
				$descriptions = array();
				$descriptions_from_match = $this->array_filter_key($matchArray['product_info'], function($key) {
				    return strpos($key, 'description(') === 0;
				});
				foreach($descriptions_from_match as $desc_d => $desc_i) {
					$lang_code = substr($desc_d,strlen("description("),strlen($desc_d)-strlen("description(")-1);
					$desc = $this->get($product, $matchArray['product_info'][$desc_d]);
					if($desc == null) {
						$desc = '';
					}
					$desc = htmlspecialchars( $desc );
					$descriptions[$lang_code] = $desc;
				}
				$meta_titles = array();
				if ($exist_meta_title) {
					$meta_titles_from_match = $this->array_filter_key($matchArray['product_info'], function($key) {
					    return strpos($key, 'meta_title(') === 0;
					});
					foreach($meta_titles_from_match as $meta_d => $meta_i) {
						$lang_code = substr($meta_d,strlen("meta_title("),strlen($meta_d)-strlen("meta_title(")-1);
						$meta = $this->get($product, $matchArray['product_info'][$meta_d]);
						if($meta == null) {
							$meta = '';
						}
						$meta = htmlspecialchars( $meta );
						$meta_titles[$lang_code] = $meta;
					}
					
					if($meta_titles_from_match == null) {
						foreach($names_from_match as $name_d => $name_i) {
							$lang_code = substr($name_d,strlen("name("),strlen($name_d)-strlen("name(")-1);
							$name = $this->get($product, $matchArray['product_info'][$name_d]);
							if($name == null) {
								$name = '';
							}
							$name = htmlspecialchars( $name );
							$meta_titles[$lang_code] = $name;
						}
					}
					
				} else {
					$meta_titles = null;
				}
				$meta_descriptions = array();
				$meta_descriptions_from_match = $this->array_filter_key($matchArray['product_info'], function($key) {
				    return strpos($key, 'meta_description(') === 0;
				});
				foreach($meta_descriptions_from_match as $meta_d => $meta_i) {
					$lang_code = substr($meta_d,strlen("meta_description("),strlen($meta_d)-strlen("meta_description(")-1);
					$meta = $this->get($product, $matchArray['product_info'][$meta_d]);
					if($meta == null) {
						$meta = '';
					}
					$meta = htmlspecialchars( $meta );
					$meta_descriptions[$lang_code] = $meta;
				}
				$meta_keywords = array();
				$meta_keywords_from_match = $this->array_filter_key($matchArray['product_info'], function($key) {
				    return strpos($key, 'meta_keywords(') === 0;
				});
				foreach($meta_keywords_from_match as $meta_d => $meta_i) {
					$lang_code = substr($meta_d,strlen("meta_keywords("),strlen($meta_d)-strlen("meta_keywords(")-1);
					$meta = $this->get($product, $matchArray['product_info'][$meta_d]);
					if($meta == null) {
						$meta = '';
					}
					$meta = htmlspecialchars( $meta );
					$meta_keywords[$lang_code] = $meta;
				}
				$stock_status_id = array_key_exists('stock_status_id', $matchArray['product_info']) && $matchArray['product_info']['stock_status_id'] != null ? $this->get($product, $matchArray['product_info']['stock_status_id']) : $default_stock_status_id;
				$store_ids = array_key_exists('store_ids', $matchArray['product_info']) && $matchArray['product_info']['store_ids'] != null ? $this->get($product, $matchArray['product_info']['store_ids']) : '0';
				$layout = array_key_exists('layout', $matchArray['product_info']) && $matchArray['product_info']['layout'] != null ? $this->get($product, $matchArray['product_info']['layout']) : '';
				$related = array_key_exists('related_ids', $matchArray['product_info']) && $matchArray['product_info']['related_ids'] != null ? $this->get($product, $matchArray['product_info']['related_ids']) : '';
				$tags = array();
				$tags_from_match = $this->array_filter_key($matchArray['product_info'], function($key) {
				    return strpos($key, 'tags(') === 0;
				});
				foreach($tags_from_match as $tag_d => $tag_i) {
					$lang_code = substr($tag_d,strlen("tags("),strlen($tag_d)-strlen("tags(")-1);
					$tag = $this->get($product, $matchArray['product_info'][$tag_d]);
					if($tag == null) {
						$tag = '';
					}
					$tag = htmlspecialchars( $tag );
					$tags[$lang_code] = $tag;
				}
				$sort_order = array_key_exists('sort_order', $matchArray['product_info']) && $matchArray['product_info']['sort_order'] != null ? $this->get($product, $matchArray['product_info']['sort_order']) : '0';
				$subtract = array_key_exists('subtract', $matchArray['product_info']) && $matchArray['product_info']['subtract'] != null ? $this->get($product, $matchArray['product_info']['subtract']) : 'true';
				$minimum = array_key_exists('minimum', $matchArray['product_info']) && $matchArray['product_info']['minimum'] != null ? $this->get($product, $matchArray['product_info']['minimum']) : '1';
				
				$prod = array();
				$is_product_new = true;
				$found_local_id = $this->findLocalId((int)$product_id, $id, 1);
				if($found_local_id) {
					$prod['product_id'] = $found_local_id;
					$is_product_new = false;
				} else {
					$prod['product_id'] = (int)$product_id;
				}
				$prod['names'] = $names;
				$categories = trim( $this->clean($categories, false) );
				$prod['categories'] = array();
				if($categories !== "")
					$prod['categories'] = explode( ",", $categories );
				if ($prod['categories']===false) {
					$prod['categories'] = array();
				}
				if(count($prod['categories']) > 0) {
					$new_ids = array();
					foreach($prod['categories'] as $c) {
						$found_local_id = $this->findLocalId((int)$c, $id, 0);
						if($found_local_id) {
							$new_ids[] = $found_local_id;
						} else {
							$new_ids[] = $c;
						}
					}
					$prod['categories'] = $new_ids;
				}
				$prod['quantity'] = $quantity;
				$prod['model'] = $model;
				$prod['manufacturer_name'] = $manufacturer_name;
				$prod['image'] = $image_name;
				$prod['shipping'] = $shipping;
				$prod['price'] = $price;
				$prod['points'] = $points;
				$prod['date_added'] = $date_added;
				$prod['date_modified'] = $date_modified;
				$prod['date_available'] = $date_available;
				$prod['weight'] = $weight;
				$prod['weight_unit'] = $weight_unit;
				$prod['status'] = (!$image_name || $image_name == '' || $price == '0.00') ? 'false' : $status;
				$prod['tax_class_id'] = $tax_class_id;
				$prod['viewed'] = isset($view_counts[$product_id]) ? $view_counts[$product_id] : 0;
				$prod['descriptions'] = $descriptions;
				$prod['stock_status_id'] = $stock_status_id;
				if ($exist_meta_title) {
					$prod['meta_titles'] = $meta_titles;
				}
				$prod['meta_descriptions'] = $meta_descriptions;
				
				$prod['length'] = strval(floatval(str_replace(',','.',$length)));
				$prod['width'] = strval(floatval(str_replace(',','.',$width)));
				$prod['height'] = strval(floatval(str_replace(',','.',$height)));
				
				$prod['seo_keyword'] = $keyword;
				$prod['measurement_unit'] = $measurement_unit;
				$prod['sku'] = $sku;
				$prod['upc'] = $upc;
				if (in_array('ean',$product_fields)) {
					$prod['ean'] = $ean;
				}
				if (in_array('jan',$product_fields)) {
					$prod['jan'] = $jan;
				}
				if (in_array('isbn',$product_fields)) {
					$prod['isbn'] = $isbn;
				}
				if (in_array('mpn',$product_fields)) {
					$prod['mpn'] = $mpn;
				}
				$prod['location'] = $location;
				$store_ids = trim( $this->clean($store_ids, false) );
				$prod['store_ids'] = ($store_ids=="") ? array() : explode( ",", $store_ids );
				if ($prod['store_ids']===false) {
					$prod['store_ids'] = array();
				}
				$prod['related_ids'] = ($related=="") ? array() : explode( ",", $related );
				if ($prod['related_ids']===false) {
					$prod['related_ids'] = array();
				}
				$prod['layout'] = ($layout=="") ? array() : explode( ",", $layout );
				if ($prod['layout']===false) {
					$prod['layout'] = array();
				}
				$prod['subtract'] = $subtract;
				$prod['minimum'] = $minimum;
				$prod['meta_keywords'] = $meta_keywords;
				$prod['tags'] = $tags;
				$prod['sort_order'] = $sort_order;

				if(!$is_product_new) {
					$this->deleteProduct( $prod['product_id'], $exist_table_product_tag );
				}
				$available_product_ids[$product_id] = $product_id;
				$inserted_id = $this->storeProductIntoDatabase( $is_product_new, $prod, $languages, $product_fields, $exist_table_product_tag, $exist_meta_title, $layout_ids, $available_store_ids, $manufacturers, $weight_class_ids, $length_class_ids, $url_alias_ids );
		
				if(!$inserted_id) {
					exit('Product was not inserted: '.json_encode($prod));
				}
				
				$match_id = $this->storeMatchIntoDatabase( $id, 1, (int)$product_id, $inserted_id );
				
				if(!$match_id) {
					exit('Product was inserted, but not matched: '.json_encode($prod));
				}
				
				if($add_imgs && count($add_imgs) > 0)
					$this->uploadAdditionalImages( $inserted_id, $add_imgs );
				
				// Attributes
				$attribute_array = array_key_exists('attributes', $matchArray['product_info']) && $matchArray['product_info']['attributes'] != null ? $matchArray['product_info']['attributes'] : null;
				if($attribute_array) {
					$this->deleteProductAttribute( $inserted_id );
					foreach($attribute_array as $attribute) {
						$attr_group_id = null;
						$att_group_names = array();
						$att_group_names_from_match = $this->array_filter_key($attribute, function($key) {
						    return strpos($key, 'group-name(') === 0;
						});
						foreach($att_group_names_from_match as $name_d => $name_i) {
							$lang_code = substr($name_d,strlen("group-name("),strlen($name_d)-strlen("group-name(")-1);
							$name = $attribute[$name_d];
							$name = htmlspecialchars( $name );
							$att_group_names[$lang_code] = $name;
							foreach($attribute_groups as $i => $a_group) {
								foreach($a_group as $n) {
									if($n == $name) {
										$attr_group_id = $i;
										break;
									}
								}
								if($attr_group_id) {
									break;
								}
							}
							if($attr_group_id) {
								break;
							}
						}
						if(!$attr_group_id) {
							$attr_group = array();
							$attr_group['sort_order'] = '0';
							$attr_group['names'] = $att_group_names;
							$attr_group_id = $this->storeAttributeGroupIntoDatabase(1, $attr_group, $languages);
						}
						
						$attr_id = null;
						$att_names = array();
						$att_names_from_match = $this->array_filter_key($attribute, function($key) {
						    return strpos($key, 'name(') === 0;
						});
						foreach($att_names_from_match as $name_d => $name_i) {
							$lang_code = substr($name_d,strlen("name("),strlen($name_d)-strlen("name(")-1);
							$name = $attribute[$name_d];
							$name = htmlspecialchars( $name );
							$att_names[$lang_code] = $name;
							foreach($attributes as $i => $a) {
								foreach($a as $n) {
									if($n == $name) {
										$attr_id = $i;
										break;
									}
								}
								if($attr_id) {
									break;
								}
							}
							if($attr_id) {
								break;
							}
						}
						if(!$attr_id) {
							$attr = array();
							$attr['attribute_group_id'] = $attr_group_id;
							$attr['sort_order'] = '0';
							$attr['names'] = $att_names;
							$attr_id = $this->storeAttributeIntoDatabase(1, $attr, $languages);
						}
						
						$texts = array();
						$texts_from_match = $this->array_filter_key($attribute, function($key) {
						    return strpos($key, 'text(') === 0;
						});
						$text_empty = false;
						foreach($texts_from_match as $text_d => $text_i) {
							$lang_code = substr($text_d,strlen("text("),strlen($text_d)-strlen("text(")-1);
							$text = $this->get($product, $attribute[$text_d]);
							$text = htmlspecialchars( $text );
							$texts[$lang_code] = $text;
							if(!$text || $text == "") {
								$text_empty = true;
								break;
							}
						}
						
						if($text_empty) {
							continue;
						}
						
						$prod_attr = array(
							'product_id' => $inserted_id,
							'attribute_id' => $attr_id,
							'texts' => $texts
						);
						$this->storeProductAttributeIntoDatabase( $prod_attr, $languages );
					}
				}
				// -----
				
				$this->Count++;
			}
			echo 'Updated '.$this->Count.' products.<br />'."\n";
		}
	}
	
	protected function uploadAdditionalImages( $product_id, $images ) {

		// check for the existence of product_image.sort_order field
		$sql = "SHOW COLUMNS FROM `".DB_PREFIX."product_image` LIKE 'sort_order'";
		$query = $this->db->query( $sql );
		$exist_sort_order = ($query->num_rows > 0) ? true : false;

		$old_product_image_ids = $this->deleteAdditionalImage( $product_id );

		foreach($images as $image_name) {
			// If image_name is URL then download it to local location
			if (filter_var($image_name, FILTER_VALIDATE_URL) !== FALSE) {
				$this->load->model('tool/image');
			    $uurrll = $this->model_tool_image->downloadImageFromURL($image_name, 0);
			    if($uurrll != null) {
			    	$image_name = $uurrll;
			    }
			}

			$sort_order = '0';
			
			$image = array();
			$image['product_id'] = $product_id;
			$image['image_name'] = $image_name;
			if ($exist_sort_order) {
				$image['sort_order'] = $sort_order;
			}

			$this->storeAdditionalImageIntoDatabase( $image, $old_product_image_ids, $exist_sort_order );
		}
	}
	
	// ЗАГОТОВКА ДЛЯ СИСТЕМЫ ЧАСТИЧНОГО ОБНОВЛЕНИЯ ТОВАРОВ
	protected function partialUpdateProducts(&$id, &$inputXml, &$matchArray) {
		if(array_key_exists('products_container', $matchArray)
		&& array_key_exists('product_item', $matchArray)
		&& array_key_exists('product_info', $matchArray)
		&& array_key_exists('main_currency', $matchArray)
		&& array_key_exists('main_currency_usd_rate', $matchArray)) {
			
			$products_container = $inputXml->xpath($matchArray['products_container']);
			if($products_container == FALSE) {
				exit('ERROR products_container');
			}
			$xml_products = $products_container[0]->xpath($matchArray['product_item']);
			if($xml_products == FALSE) {
				exit('ERROR products');
			}
			
			$main_currrency_usd_rate = (float)$matchArray['main_currency_usd_rate'];
			
			$sourceProducts = $this->getSourceProducts( $id );
			
			$i = 1;
			$count = count($xml_products);
			
			$productsToExclude = array();
			foreach($xml_products as $product) {
				set_time_limit(120);
				$product_id = $matchArray['product_info']['product_id'] != null ? $this->get($product, $matchArray['product_info']['product_id']) : null;
				if($product_id == null || $product_id == "") {
					exit('ERROR product_id is NULL');
				}
				$product_id = trim($product_id);
				
				$found_local_id = false;
				foreach($sourceProducts as $sProd) {
					if(strval($sProd['remote_id']) == strval($product_id)) {
						$found_local_id = $sProd['local_id'];
						break;
					}
				}
				
				if($found_local_id) {
					// Update only stock quantity
					$quantity = $matchArray['product_info']['quantity'] != null ? $this->get($product, $matchArray['product_info']['quantity']) : '0';
					$quantity = ((is_string($quantity)) && (strlen($quantity)>0)) ? $quantity : '0';
					
					$this->updateProductQuantity( $found_local_id, $quantity );
					
					// Add this node to remove node from xml
					$productsToExclude[] = $product;
				}
			}
			
			// Remove products that are already in the base 
			// (we've just updated quantities for them)
			foreach ($productsToExclude as $prod) {
			    unset($prod[0]);
			}
			
			// All new products will be added
			$this->updateProducts($id, $inputXml, $matchArray);
		}
	}
	
	public function storeSourceIntoDatabase($name, $url, $path, $matching_file, $status) {
		$sql = "INSERT INTO `".DB_PREFIX."import_mod_sources` (`name`, `url`, `path`, `match_file`, `last_updated`, `status`) VALUES ";
		$sql .= "( '$name', '$url', '$path', '$matching_file', 0, $status );";

		$idd = $this->db->getLastId();
		$ret_id = null;
		$this->db->query( $sql );
		if($idd != $this->db->getLastId()) {
			$ret_id = $this->db->getLastId();
		}
		return $ret_id;
	}

	public function storeManTranscodeIntoDatabase($from, $to, $code, $start_index, $L1, $L2) {
		$sql = "DELETE FROM `".DB_PREFIX."import_mod_man_transcodes` WHERE `from`='$from';";
		$query = $this->db->query($sql);
		if(!$query) {
			return false;
		}
		$sql = "INSERT INTO `".DB_PREFIX."import_mod_man_transcodes` (`from`, `to`, `code`, `start_index`, `L1`, `L2`) VALUES ";
		$sql .= "('$from', '$to', '$code', $start_index, $L1, $L2);";
		$query = $this->db->query($sql);
		if(!$query) {
			return false;
		}
		return true;
	}

	public function storeCatTranscodeIntoDatabase($from, $to) {
		$sql = "DELETE FROM `".DB_PREFIX."import_mod_cat_transcodes` WHERE `name_from`='$from';";
		$query = $this->db->query($sql);
		if(!$query) {
			return false;
		}
		$sql = "INSERT INTO `".DB_PREFIX."import_mod_cat_transcodes` (`name_from`, `name_to`) VALUES ";
		$sql .= "('$from', '$to');";
		$query = $this->db->query($sql);
		if(!$query) {
			return false;
		}
		return true;
	}
	
	protected function storeMatchIntoDatabase( $source_id, $type, $remote_id, $local_id ) {
		$sql = "INSERT INTO `".DB_PREFIX."import_mod_matches` (`source_id`, `type`, `remote_id`, `local_id`, `last_updated`) VALUES ";
		$sql .= "( $source_id, $type, $remote_id, $local_id, NOW() );";
		
		$idd = $this->db->getLastId();
		$ret_id = null;
		$this->db->query( $sql );
		if($idd != $this->db->getLastId()) {
			$ret_id = $this->db->getLastId();
		}
		
		return $ret_id;
	}
	
	protected function storeCategoryIntoDatabase( $is_new, &$category, &$languages, $exist_meta_title, &$layout_ids, &$available_store_ids, &$url_alias_ids ) {
		// extract the category details
		$category_id = $category['category_id'];
		$image_name = $this->db->escape($category['image']);
		$parent_id = $category['parent_id'];
		$top = $category['top'];
		$top = ((strtoupper($top)=="TRUE") || (strtoupper($top)=="YES") || (strtoupper($top)=="ENABLED")) ? 1 : 0;
		$columns = $category['columns'];
		$sort_order = $category['sort_order'];
		$date_added = $category['date_added'];
		$date_modified = $category['date_modified'];
		$names = $category['names'];
		$descriptions = $category['descriptions'];
		if ($exist_meta_title) {
			$meta_titles = $category['meta_titles'];
		}
		$meta_descriptions = $category['meta_descriptions'];
		$meta_keywords = $category['meta_keywords'];
		$seo_keyword = $category['seo_keyword'];
		$store_ids = $category['store_ids'];
		$layout = $category['layout'];
		$status = $category['status'];
		$status = ((strtoupper($status)=="TRUE") || (strtoupper($status)=="YES") || (strtoupper($status)=="ENABLED")) ? 1 : 0;

		// generate and execute SQL for inserting the category
		$sql = "INSERT INTO `".DB_PREFIX."category` (" . ($is_new ? "" : "`category_id`, ") . "`image`, `parent_id`, `top`, `column`, `sort_order`, `date_added`, `date_modified`, `status`) VALUES ";
		$sql .= "(" . ($is_new ? "" : "$category_id, ") . "'$image_name', $parent_id, $top, $columns, $sort_order, ";
		$sql .= ($date_added=='NOW()') ? "$date_added," : "'$date_added',";
		$sql .= ($date_modified=='NOW()') ? "$date_modified," : "'$date_modified',";
		$sql .= " $status);";

		$idd = $this->db->getLastId();
		$ret_id = null;
		$this->db->query( $sql );
		if($idd != $this->db->getLastId()) {
			$ret_id = $this->db->getLastId();
		}
		if ($ret_id == null) {
			return null;
		}

		foreach ($languages as $language) {
			$language_code = $language['code'];
			$language_id = $language['language_id'];
			$name = isset($names[$language_code]) ? $this->db->escape($names[$language_code]) : '';
			$description = isset($descriptions[$language_code]) ? $this->db->escape($descriptions[$language_code]) : '';
			if ($exist_meta_title) {
				$meta_title = isset($meta_titles[$language_code]) ? $this->db->escape($meta_titles[$language_code]) : '';
			}
			$meta_description = isset($meta_descriptions[$language_code]) ? $this->db->escape($meta_descriptions[$language_code]) : '';
			$meta_keyword = isset($meta_keywords[$language_code]) ? $this->db->escape($meta_keywords[$language_code]) : '';
			if ($exist_meta_title) {
				$sql  = "INSERT INTO `".DB_PREFIX."category_description` (`category_id`, `language_id`, `name`, `description`, `meta_title`, `meta_description`, `meta_keyword`) VALUES ";
				$sql .= "( $ret_id, $language_id, '$name', '$description', '$meta_title', '$meta_description', '$meta_keyword' );";
			} else {
				$sql  = "INSERT INTO `".DB_PREFIX."category_description` (`category_id`, `language_id`, `name`, `description`, `meta_description`, `meta_keyword`) VALUES ";
				$sql .= "( $ret_id, $language_id, '$name', '$description', '$meta_description', '$meta_keyword' );";
			}
			$this->db->query( $sql );
		}
		if ($seo_keyword) {
			if (isset($url_alias_ids[$category_id])) {
				$url_alias_id = $url_alias_ids[$category_id];
				$sql = "INSERT INTO `".DB_PREFIX."url_alias` (`url_alias_id`,`query`,`keyword`) VALUES ($url_alias_id,'category_id=$ret_id','$seo_keyword');";
				unset($url_alias_ids[$category_id]);
			} else {
				$sql = "INSERT INTO `".DB_PREFIX."url_alias` (`query`,`keyword`) VALUES ('category_id=$ret_id','$seo_keyword');";
			}
			$this->db->query($sql);
		}
		foreach ($store_ids as $store_id) {
			if (in_array((int)$store_id,$available_store_ids)) {
				$sql = "INSERT INTO `".DB_PREFIX."category_to_store` (`category_id`,`store_id`) VALUES ($ret_id,$store_id);";
				$this->db->query($sql);
			}
		}
		$layouts = array();
		foreach ($layout as $layout_part) {
			$next_layout = explode(':',$layout_part);
			if ($next_layout===false) {
				$next_layout = array( 0, $layout_part );
			} else if (count($next_layout)==1) {
				$next_layout = array( 0, $layout_part );
			}
			if ( (count($next_layout)==2) && (in_array((int)$next_layout[0],$available_store_ids)) && (is_string($next_layout[1])) ) {
				$store_id = (int)$next_layout[0];
				$layout_name = $next_layout[1];
				if (isset($layout_ids[$layout_name])) {
					$layout_id = (int)$layout_ids[$layout_name];
					if (!isset($layouts[$store_id])) {
						$layouts[$store_id] = $layout_id;
					}
				}
			}
		}
		foreach ($layouts as $store_id => $layout_id) {
			$sql = "INSERT INTO `".DB_PREFIX."category_to_layout` (`category_id`,`store_id`,`layout_id`) VALUES ($ret_id,$store_id,$layout_id);";
			$this->db->query($sql);
		}

		return $ret_id;
	}
	
	protected function storeProductIntoDatabase( $is_new, &$product, &$languages, &$product_fields, $exist_table_product_tag, $exist_meta_title, &$layout_ids, &$available_store_ids, &$manufacturers, &$weight_class_ids, &$length_class_ids, &$url_alias_ids ) {
		// extract the product details
		$product_id = $product['product_id'];
		$names = $product['names'];
		$categories = $product['categories'];
		$quantity = $product['quantity'];
		$model = $this->db->escape($product['model']);
		$manufacturer_name = $product['manufacturer_name'];
		$image = $this->db->escape($product['image']);
		$shipping = $product['shipping'];
		$shipping = ((strtoupper($shipping)=="YES") || (strtoupper($shipping)=="Y") || (strtoupper($shipping)=="TRUE")) ? 1 : 0;
		$price = trim($product['price']);
		$points = $product['points'];
		$date_added = $product['date_added'];
		$date_modified = $product['date_modified'];
		$date_available = $product['date_available'];
		$weight = ($product['weight']=="") ? 0 : $product['weight'];
		$weight_unit = $product['weight_unit'];
		$weight_class_id = (isset($weight_class_ids[$weight_unit])) ? $weight_class_ids[$weight_unit] : 0;
		$status = $product['status'];
		$status = ((strtoupper($status)=="TRUE") || (strtoupper($status)=="YES") || (strtoupper($status)=="ENABLED")) ? 1 : 0;
		$tax_class_id = $product['tax_class_id'];
		$viewed = $product['viewed'];
		$descriptions = $product['descriptions'];
		$stock_status_id = $product['stock_status_id'];
		if ($exist_meta_title) {
			$meta_titles = $product['meta_titles'];
		}
		$meta_descriptions = $product['meta_descriptions'];
		$length = $product['length'];
		$width = $product['width'];
		$height = $product['height'];
		$keyword = $this->db->escape($product['seo_keyword']);
		$length_unit = $product['measurement_unit'];
		$length_class_id = (isset($length_class_ids[$length_unit])) ? $length_class_ids[$length_unit] : 0;
		$sku = $this->db->escape($product['sku']);
		$upc = $this->db->escape($product['upc']);
		if (in_array('ean',$product_fields)) {
			$ean = $this->db->escape($product['ean']);
		}
		if (in_array('jan',$product_fields)) {
			$jan = $this->db->escape($product['jan']);
		}
		if (in_array('isbn',$product_fields)) {
			$isbn = $this->db->escape($product['isbn']);
		}
		if (in_array('mpn',$product_fields)) {
			$mpn = $this->db->escape($product['mpn']);
		}
		$location = $this->db->escape($product['location']);
		$store_ids = $product['store_ids'];
		$layout = $product['layout'];
		$related_ids = $product['related_ids'];
		$subtract = $product['subtract'];
		$subtract = ((strtoupper($subtract)=="TRUE") || (strtoupper($subtract)=="YES") || (strtoupper($subtract)=="ENABLED")) ? 1 : 0;
		$minimum = $product['minimum'];
		$meta_keywords = $product['meta_keywords'];
		$tags = $product['tags'];
		$sort_order = $product['sort_order'];
		if ($manufacturer_name) {
			$this->storeManufacturerIntoDatabase( $manufacturers, $manufacturer_name, $store_ids, $available_store_ids );
			$manufacturer_id = $manufacturers[$manufacturer_name]['manufacturer_id'];
		} else {
			$manufacturer_id = 0;
		}

		// generate and execute SQL for inserting the product
		$sql  = "INSERT INTO `".DB_PREFIX."product` (".(!$is_new ? "`product_id`," : "")."`quantity`,`sku`,`upc`,";
		$sql .= in_array('ean',$product_fields) ? "`ean`," : "";
		$sql .= in_array('jan',$product_fields) ? "`jan`," : "";
		$sql .= in_array('isbn',$product_fields) ? "`isbn`," : "";
		$sql .= in_array('mpn',$product_fields) ? "`mpn`," : "";
		$sql .= "`location`,`stock_status_id`,`model`,`manufacturer_id`,`image`,`shipping`,`price`,`points`,`date_added`,`date_modified`,`date_available`,`weight`,`weight_class_id`,`status`,";
		$sql .= "`tax_class_id`,`viewed`,`length`,`width`,`height`,`length_class_id`,`sort_order`,`subtract`,`minimum`) VALUES ";
		$sql .= (!$is_new ? "($product_id," : "(")."$quantity,'$sku','$upc',";
		$sql .= in_array('ean',$product_fields) ? "'$ean'," : "";
		$sql .= in_array('jan',$product_fields) ? "'$jan'," : "";
		$sql .= in_array('isbn',$product_fields) ? "'$isbn'," : "";
		$sql .= in_array('mpn',$product_fields) ? "'$mpn'," : "";
		$sql .= "'$location',$stock_status_id,'$model',$manufacturer_id,'$image',$shipping,$price,$points,";
		$sql .= ($date_added=='NOW()') ? "$date_added," : "'$date_added',";
		$sql .= ($date_modified=='NOW()') ? "$date_modified," : "'$date_modified',";
		$sql .= ($date_available=='NOW()') ? "$date_available," : "'$date_available',";
		$sql .= "$weight,$weight_class_id,$status,";
		$sql .= "$tax_class_id,$viewed,$length,$width,$height,'$length_class_id','$sort_order','$subtract','$minimum');";

		$idd = $this->db->getLastId();
		$ret_id = null;
		$this->db->query( $sql );
		if($idd != $this->db->getLastId()) {
			$ret_id = $this->db->getLastId();
		}
		if ($ret_id == null) {
			return null;
		}
		$product_id = $ret_id;
		
		foreach ($languages as $language) {
			$language_code = $language['code'];
			$language_id = $language['language_id'];
			$name = isset($names[$language_code]) ? $this->db->escape($names[$language_code]) : '';
			$description = isset($descriptions[$language_code]) ? $this->db->escape($descriptions[$language_code]) : '';
			if ($exist_meta_title) {
				$meta_title = isset($meta_titles[$language_code]) ? $this->db->escape($meta_titles[$language_code]) : '';
			}
			$meta_description = isset($meta_descriptions[$language_code]) ? $this->db->escape($meta_descriptions[$language_code]) : '';
			$meta_keyword = isset($meta_keywords[$language_code]) ? $this->db->escape($meta_keywords[$language_code]) : '';
			$tag = isset($tags[$language_code]) ? $this->db->escape($tags[$language_code]) : '';
			if ($exist_table_product_tag) {
				if ($exist_meta_title) {
					$sql  = "INSERT INTO `".DB_PREFIX."product_description` (`product_id`, `language_id`, `name`, `description`, `meta_title`, `meta_description`, `meta_keyword`) VALUES ";
					$sql .= "( $product_id, $language_id, '$name', '$description', '$meta_title', '$meta_description', '$meta_keyword' );";
				} else {
					$sql  = "INSERT INTO `".DB_PREFIX."product_description` (`product_id`, `language_id`, `name`, `description`, `meta_description`, `meta_keyword`) VALUES ";
					$sql .= "( $product_id, $language_id, '$name', '$description', '$meta_description', '$meta_keyword' );";
				}
				$this->db->query( $sql );
				$sql  = "INSERT INTO `".DB_PREFIX."product_tag` (`product_id`,`language_id`,`tag`) VALUES ";
				$sql .= "($product_id, $language_id, '$tag')";
				$this->db->query($sql);
			} else {
				if ($exist_meta_title) {
					$sql  = "INSERT INTO `".DB_PREFIX."product_description` (`product_id`, `language_id`, `name`, `description`, `meta_title`, `meta_description`, `meta_keyword`, `tag`) VALUES ";
					$sql .= "( $product_id, $language_id, '$name', '$description', '$meta_title', '$meta_description', '$meta_keyword', '$tag' );";
				} else {
					$sql  = "INSERT INTO `".DB_PREFIX."product_description` (`product_id`, `language_id`, `name`, `description`, `meta_description`, `meta_keyword`, `tag`) VALUES ";
					$sql .= "( $product_id, $language_id, '$name', '$description', '$meta_description', '$meta_keyword', '$tag' );";
				}
				$this->db->query( $sql );
			}
		}
		if (count($categories) > 0) {
			$sql = "INSERT INTO `".DB_PREFIX."product_to_category` (`product_id`,`category_id`) VALUES ";
			$first = true;
			foreach ($categories as $category_id) {
				$sql .= ($first) ? "\n" : ",\n";
				$first = false;
				$sql .= "($product_id,$category_id)";
			}
			$sql .= ";";
			$this->db->query($sql);
		}
		if ($keyword) {
			if (isset($url_alias_ids[$product_id])) {
				$url_alias_id = $url_alias_ids[$product_id];
				$sql = "INSERT INTO `".DB_PREFIX."url_alias` (`url_alias_id`,`query`,`keyword`) VALUES ($url_alias_id,'product_id=$product_id','$keyword');";
				unset($url_alias_ids[$product_id]);
			} else {
				$sql = "INSERT INTO `".DB_PREFIX."url_alias` (`query`,`keyword`) VALUES ('product_id=$product_id','$keyword');";
			}
			$this->db->query($sql);
		}
		foreach ($store_ids as $store_id) {
			if (in_array((int)$store_id,$available_store_ids)) {
				$sql = "INSERT INTO `".DB_PREFIX."product_to_store` (`product_id`,`store_id`) VALUES ($product_id,$store_id);";
				$this->db->query($sql);
			}
		}
		$layouts = array();
		foreach ($layout as $layout_part) {
			$next_layout = explode(':',$layout_part);
			if ($next_layout===false) {
				$next_layout = array( 0, $layout_part );
			} else if (count($next_layout)==1) {
				$next_layout = array( 0, $layout_part );
			}
			if ( (count($next_layout)==2) && (in_array((int)$next_layout[0],$available_store_ids)) && (is_string($next_layout[1])) ) {
				$store_id = (int)$next_layout[0];
				$layout_name = $next_layout[1];
				if (isset($layout_ids[$layout_name])) {
					$layout_id = (int)$layout_ids[$layout_name];
					if (!isset($layouts[$store_id])) {
						$layouts[$store_id] = $layout_id;
					}
				}
			}
		}
		foreach ($layouts as $store_id => $layout_id) {
			$sql = "INSERT INTO `".DB_PREFIX."product_to_layout` (`product_id`,`store_id`,`layout_id`) VALUES ($product_id,$store_id,$layout_id);";
			$this->db->query($sql);
		}
		if (count($related_ids) > 0) {
			$sql = "INSERT INTO `".DB_PREFIX."product_related` (`product_id`,`related_id`) VALUES ";
			$first = true;
			foreach ($related_ids as $related_id) {
				$sql .= ($first) ? "\n" : ",\n";
				$first = false;
				$sql .= "($product_id,$related_id)";
			}
			$sql .= ";";
			$this->db->query($sql);
		}
		
		return $ret_id;
	}
	
	protected function storeManufacturerIntoDatabase( &$manufacturers, $name, &$store_ids, &$available_store_ids ) {
		foreach ($store_ids as $store_id) {
			if (!in_array( $store_id, $available_store_ids )) {
				continue;
			}
			if (!isset($manufacturers[$name]['manufacturer_id'])) {
				$this->db->query("INSERT INTO ".DB_PREFIX."manufacturer SET name = '".$this->db->escape($name)."', image='', sort_order = '0'");
				$manufacturer_id = $this->db->getLastId();
				if (!isset($manufacturers[$name])) {
					$manufacturers[$name] = array();
				}
				$manufacturers[$name]['manufacturer_id'] = $manufacturer_id;
			}
			if (!isset($manufacturers[$name]['store_ids'])) {
				$manufacturers[$name]['store_ids'] = array();
			}
			if (!in_array($store_id,$manufacturers[$name]['store_ids'])) {
				$manufacturer_id = $manufacturers[$name]['manufacturer_id'];
				$sql = "INSERT INTO `".DB_PREFIX."manufacturer_to_store` SET manufacturer_id='".(int)$manufacturer_id."', store_id='".(int)$store_id."'";
				$this->db->query( $sql );
				$manufacturers[$name]['store_ids'][] = $store_id;
			}
		}
	}
	
	protected function storeAdditionalImageIntoDatabase( &$image, &$old_product_image_ids, $exist_sort_order=true ) {
		$product_id = $image['product_id'];
		$image_name = $image['image_name'];
		if ($exist_sort_order) {
			$sort_order = $image['sort_order'];
		}
		if (isset($old_product_image_ids[$product_id][$image_name])) {
			$product_image_id = $old_product_image_ids[$product_id][$image_name];
			if ($exist_sort_order) {
				$sql  = "INSERT INTO `".DB_PREFIX."product_image` (`product_image_id`,`product_id`,`image`,`sort_order` ) VALUES "; 
				$sql .= "($product_image_id,$product_id,'".$this->db->escape($image_name)."',$sort_order)";
			} else {
				$sql  = "INSERT INTO `".DB_PREFIX."product_image` (`product_image_id`,`product_id`,`image` ) VALUES "; 
				$sql .= "($product_image_id,$product_id,'".$this->db->escape($image_name)."')";
			}
			$this->db->query($sql);
			unset($old_product_image_ids[$product_id][$image_name]);
		} else {
			if ($exist_sort_order) {
				$sql  = "INSERT INTO `".DB_PREFIX."product_image` (`product_id`,`image`,`sort_order` ) VALUES "; 
				$sql .= "($product_id,'".$this->db->escape($image_name)."',$sort_order)";
			} else {
				$sql  = "INSERT INTO `".DB_PREFIX."product_image` (`product_id`,`image` ) VALUES "; 
				$sql .= "($product_id,'".$this->db->escape($image_name)."')";
			}
			$this->db->query($sql);
		}
	}
	
	protected function storeAttributeGroupIntoDatabase( $is_new, &$attribute_group, &$languages ) {
		if(!$is_new)
			$attribute_group_id = $attribute_group['attribute_group_id'];
		$sort_order = $attribute_group['sort_order'];
		$names = $attribute_group['names'];
		$sql  = "INSERT INTO `".DB_PREFIX."attribute_group` (".(!$is_new ? "`attribute_group_id`," : "")."`sort_order`) VALUES ";
		$sql .= "( ".(!$is_new ? "$attribute_group_id, " : "")."$sort_order );";
		
		$this->db->query( $sql );
		
		if($is_new) {
			$attribute_group_id = $this->db->getLastId();
		}
		
		foreach ($languages as $language) {
			$language_code = $language['code'];
			$language_id = $language['language_id'];
			$name = isset($names[$language_code]) ? $this->db->escape($names[$language_code]) : '';
			$sql  = "INSERT INTO `".DB_PREFIX."attribute_group_description` (`attribute_group_id`, `language_id`, `name`) VALUES ";
			$sql .= "( $attribute_group_id, $language_id, '$name' );";
			$this->db->query( $sql );
		}
		
		return $attribute_group_id;
	}
	
	protected function storeAttributeIntoDatabase( $is_new, &$attribute, &$languages ) {
		if(!$is_new)
			$attribute_id = $attribute['attribute_id'];
		$attribute_group_id = $attribute['attribute_group_id'];
		$sort_order = $attribute['sort_order'];
		$names = $attribute['names'];
		$sql  = "INSERT INTO `".DB_PREFIX."attribute` (".(!$is_new ? "`attribute_id`," : "")."`attribute_group_id`,`sort_order`) VALUES ";
		$sql .= "( ".(!$is_new ? "$attribute_id, " : "")."$attribute_group_id, $sort_order );"; 
		
		$this->db->query( $sql );
		
		if($is_new) {
			$attribute_id = $this->db->getLastId();
		}
		
		foreach ($languages as $language) {
			$language_code = $language['code'];
			$language_id = $language['language_id'];
			$name = isset($names[$language_code]) ? $this->db->escape($names[$language_code]) : '';
			$sql  = "INSERT INTO `".DB_PREFIX."attribute_description` (`attribute_id`, `language_id`, `name`) ";
			$sql .= "VALUES ( $attribute_id, $language_id, '$name' );";
			$this->db->query( $sql );
		}
		
		return $attribute_id;
	}
	
	protected function storeProductAttributeIntoDatabase( &$product_attribute, &$languages ) {
		$product_id = $product_attribute['product_id'];
		$attribute_id = $product_attribute['attribute_id'];
		$texts = $product_attribute['texts'];
		foreach ($languages as $language) {
			$language_code = $language['code'];
			$language_id = $language['language_id'];
			$text = isset($texts[$language_code]) ? $this->db->escape($texts[$language_code]) : '';
			$sql  = "INSERT INTO `".DB_PREFIX."product_attribute` (`product_id`, `attribute_id`, `language_id`, `text`) VALUES ";
			$sql .= "( $product_id, $attribute_id, $language_id, '$text' );";
			$this->db->query( $sql );
		}
	}

	// MAKE IT PRETTY
	protected function storeModelTranscodeIntoDatabase( &$manufacturer_name, &$old_model, &$new_model ) {
		$sql = "DELETE FROM `".DB_PREFIX."import_mod_model_transcodes` WHERE `man_name`='$manufacturer_name' AND `old_model`='$old_model';";
		$query = $this->db->query($sql);
		if(!$query) {
			return false;
		}
		$sql = "INSERT INTO `".DB_PREFIX."import_mod_model_transcodes` (`man_name`, `old_model`, `new_model`) VALUES ";
		$sql .= "('$manufacturer_name', '$old_model', '$new_model');";
		$query = $this->db->query($sql);
		if(!$query) {
			return false;
		}
		return true;
	}
	
	protected function deleteCategory( $category_id ) {
		
		$sql = "SELECT `image` FROM `".DB_PREFIX."category` WHERE `category_id` = '$category_id';";
		$res = $this->db->query( $sql );
		if($res->num_rows > 0) {
			$image_name = $res->row['image'];
			$this->load->model('tool/image');
			$this->model_tool_image->deleteImage( $image_name );
		}
		
		$sql  = "DELETE FROM `".DB_PREFIX."category` WHERE `category_id` = '".(int)$category_id."' ;\n";
		$sql .= "DELETE FROM `".DB_PREFIX."category_description` WHERE `category_id` = '".(int)$category_id."' ;\n";
		$sql .= "DELETE FROM `".DB_PREFIX."category_to_store` WHERE `category_id` = '".(int)$category_id."' ;\n";
		$sql .= "DELETE FROM `".DB_PREFIX."url_alias` WHERE `query` LIKE 'category_id=".(int)$category_id."';\n";
		$sql .= "DELETE FROM `".DB_PREFIX."category_to_layout` WHERE `category_id` = '".(int)$category_id."' ;\n";
		$sql .= "DELETE FROM `".DB_PREFIX."import_mod_matches` WHERE `local_id` = '".(int)$category_id."' AND `type` = 0;\n";
		$this->multiquery( $sql );
		$sql = "SHOW TABLES LIKE \"".DB_PREFIX."category_path\"";
		$query = $this->db->query( $sql );
		if ($query->num_rows) {
			$sql = "DELETE FROM `".DB_PREFIX."category_path` WHERE `category_id` = '".(int)$category_id."'";
			$this->db->query( $sql );
		}
	}
	
	protected function deleteCategories( &$url_alias_ids ) {
		$sql  = "TRUNCATE TABLE `".DB_PREFIX."category`;\n";
		$sql .= "TRUNCATE TABLE `".DB_PREFIX."category_description`;\n";
		$sql .= "TRUNCATE TABLE `".DB_PREFIX."category_to_store`;\n";
		$sql .= "DELETE FROM `".DB_PREFIX."url_alias` WHERE `query` LIKE 'category_id=%';\n";
		$sql .= "TRUNCATE TABLE `".DB_PREFIX."category_to_layout`;\n";
		$this->multiquery( $sql );
		$sql = "SHOW TABLES LIKE \"".DB_PREFIX."category_path\"";
		$query = $this->db->query( $sql );
		if ($query->num_rows) {
			$sql = "TRUNCATE TABLE `".DB_PREFIX."category_path`";
			$this->db->query( $sql );
		}
		$sql = "SELECT (MAX(url_alias_id)+1) AS next_url_alias_id FROM `".DB_PREFIX."url_alias` LIMIT 1";
		$query = $this->db->query( $sql );
		$next_url_alias_id = $query->row['next_url_alias_id'];
		$sql = "ALTER TABLE `".DB_PREFIX."url_alias` AUTO_INCREMENT = $next_url_alias_id";
		$this->db->query( $sql );
		$remove = array();
		foreach ($url_alias_ids as $category_id=>$url_alias_id) {
			if ($url_alias_id >= $next_url_alias_id) {
				$remove[$category_id] = $url_alias_id;
			}
		}
		foreach ($remove as $category_id=>$url_alias_id) {
			unset($url_alias_ids[$category_id]);
		}
	}
	
	protected function deleteSourceCategories( $source_id ) {
		$sql = "SELECT `local_id` FROM `".DB_PREFIX."import_mod_matches` WHERE `source_id` = '".(int)$source_id."' AND `type` = 0;";
		$result = $this->db->query( $sql );
		$local_ids = array();
		foreach($result->rows as $row) {
			$this->deleteCategory($row['local_id']);
		}
	}

	protected function enableProduct( $product_id ) {
		$sql = "UPDATE `".DB_PREFIX."product` SET `status`=1 WHERE `product_id`=$product_id;";
		$this->db->query($sql);
	}

	protected function disableProduct( $product_id ) {
		$sql = "UPDATE `".DB_PREFIX."product` SET `status`=0 WHERE `product_id`=$product_id;";
		$this->db->query($sql);
	}

	protected function updateProductQuantity( $product_id, $new_quantity ) {
		$new_quantity = intval(strval($new_quantity));
		$sql = "UPDATE `".DB_PREFIX."product` SET `quantity`=$new_quantity WHERE `product_id`=$product_id;";
		$this->db->query($sql);
	}

	protected function deleteProduct( $product_id, $exist_table_product_tag ) {
		
		$sql = "SELECT `image` FROM `".DB_PREFIX."product` WHERE `product_id` = '$product_id';";
		$res = $this->db->query( $sql );
		if($res->num_rows > 0) {
			$image_name = $res->row['image_name'];
			$this->load->model('tool/image');
			$this->model_tool_image->deleteImage( $image_name );
		}
		
		$this->deleteAdditionalImage( $product_id );
		
		$sql  = "DELETE FROM `".DB_PREFIX."product` WHERE `product_id` = '$product_id';\n";
		$sql .= "DELETE FROM `".DB_PREFIX."product_description` WHERE `product_id` = '$product_id';\n";
		$sql .= "DELETE FROM `".DB_PREFIX."product_to_category` WHERE `product_id` = '$product_id';\n";
		$sql .= "DELETE FROM `".DB_PREFIX."product_to_store` WHERE `product_id` = '$product_id';\n";
		$sql .= "DELETE FROM `".DB_PREFIX."url_alias` WHERE `query` LIKE 'product_id=$product_id';\n";
		$sql .= "DELETE FROM `".DB_PREFIX."product_related` WHERE `product_id` = '$product_id';\n";
		$sql .= "DELETE FROM `".DB_PREFIX."product_to_layout` WHERE `product_id` = '$product_id';\n";
		$sql .= "DELETE FROM `".DB_PREFIX."import_mod_matches` WHERE `local_id` = '".(int)$product_id."' AND `type` = 1;\n";
		if ($exist_table_product_tag) {
			$sql .= "DELETE FROM `".DB_PREFIX."product_tag` WHERE `product_id` = '$product_id';\n";
		}
		$this->multiquery( $sql );
	}
	
	protected function deleteProducts( $exist_table_product_tag, &$url_alias_ids ) {
		$sql  = "TRUNCATE TABLE `".DB_PREFIX."product`;\n";
		$sql .= "TRUNCATE TABLE `".DB_PREFIX."product_description`;\n";
		$sql .= "TRUNCATE TABLE `".DB_PREFIX."product_to_category`;\n";
		$sql .= "TRUNCATE TABLE `".DB_PREFIX."product_to_store`;\n";
		$sql .= "DELETE FROM `".DB_PREFIX."url_alias` WHERE `query` LIKE 'product_id=%';\n";
		$sql .= "TRUNCATE TABLE `".DB_PREFIX."product_related`;\n";
		$sql .= "TRUNCATE TABLE `".DB_PREFIX."product_to_layout`;\n";
		if ($exist_table_product_tag) {
			$sql .= "TRUNCATE TABLE `".DB_PREFIX."product_tag`;\n";
		}
		$this->multiquery( $sql );
		$sql = "SELECT (MAX(url_alias_id)+1) AS next_url_alias_id FROM `".DB_PREFIX."url_alias` LIMIT 1";
		$query = $this->db->query( $sql );
		$next_url_alias_id = $query->row['next_url_alias_id'];
		$sql = "ALTER TABLE `".DB_PREFIX."url_alias` AUTO_INCREMENT = $next_url_alias_id";
		$this->db->query( $sql );
		$remove = array();
		foreach ($url_alias_ids as $product_id=>$url_alias_id) {
			if ($url_alias_id >= $next_url_alias_id) {
				$remove[$product_id] = $url_alias_id;
			}
		}
		foreach ($remove as $product_id=>$url_alias_id) {
			unset($url_alias_ids[$product_id]);
		}
	}
	
	protected function deleteSourceProducts( $source_id, $exist_table_product_tag ) {
		$sql = "SELECT `local_id` FROM `".DB_PREFIX."import_mod_matches` WHERE `source_id` = '".(int)$source_id."' AND `type` = 1;";
		$result = $this->db->query( $sql );
		$local_ids = array();
		foreach($result->rows as $row) {
			$this->deleteProduct($row['local_id'], $exist_table_product_tag);
		}
	}
	
	protected function deleteAdditionalImage( $product_id ) {
		$this->load->model('tool/image');
		$sql = "SELECT product_image_id, product_id, image FROM `".DB_PREFIX."product_image` WHERE product_id='".(int)$product_id."'";
		$query = $this->db->query( $sql );
		$old_product_image_ids = array();
		foreach ($query->rows as $row) {
			$product_image_id = $row['product_image_id'];
			$product_id = $row['product_id'];
			$image_name = $row['image'];
			$old_product_image_ids[$product_id][$image_name] = $product_image_id;

			$this->model_tool_image->deleteImage( $image_name );
		}
		if ($old_product_image_ids) {
			$sql = "DELETE FROM `".DB_PREFIX."product_image` WHERE product_id='".(int)$product_id."'";
			$this->db->query( $sql );
		}
		return $old_product_image_ids;
	}
	
	protected function deleteAdditionalImages() {
		$sql = "TRUNCATE TABLE `".DB_PREFIX."product_image`";
		$this->db->query( $sql );
	}
	
	protected function deleteAttributeGroup( $attribute_group_id ) {
		$sql = "DELETE FROM `".DB_PREFIX."attribute_group` WHERE attribute_group_id='".(int)$attribute_group_id."'";
		$this->db->query( $sql );
		$sql = "DELETE FROM `".DB_PREFIX."attribute_group_description` WHERE attribute_group_id='".(int)$attribute_group_id."'";
		$this->db->query( $sql );
	}
	
	protected function deleteProductAttribute( $product_id ) {
		$sql = "DELETE FROM `".DB_PREFIX."product_attribute` WHERE product_id='".(int)$product_id."'";
		$this->db->query( $sql );
	}
	
	protected function getSourceProducts( $source_id ) {
		$sql = "SELECT * FROM `".DB_PREFIX."import_mod_matches` WHERE `source_id` = $source_id;";
		$res = $this->db->query( $sql );
		if($res->num_rows > 0) {
			return $res->rows;
		} else {
			return null;
		}
	}
	
	protected function deleteMatches($source_id, $type, $remote_id) {
		$sql = "DELETE FROM `".DB_PREFIX."import_mod_matches` WHERE `source_id` = $source_id AND `type` = '$type' AND `remote_id`='$remote_id';";
		$this->db->query( $sql );
	}
	
	protected function findCategoryByName( $name ) {
		$sql = "SELECT `category_id` FROM `".DB_PREFIX."category_description` WHERE `name` = '$name';";
		$res = $this->db->query( $sql );
		if($res->num_rows > 0) {
			$id = $res->row['category_id'];
			return $id;
		} else {
			return null;
		}
	}
	
	protected function getAttributeGroups() {
		$sql = "SELECT * FROM `".DB_PREFIX."attribute_group_description`;";
		$query = $this->db->query( $sql );
		$attributeGroups = array();
		if($query->num_rows > 0) {
			foreach ($query->rows as $row) {
				if($attributeGroups[$row['attribute_group_id']] == null) {
					$attributeGroups[$row['attribute_group_id']] = array(
						$row['language_id'] => $row['name']
					);
				} else {
					$attributeGroups[$row['attribute_group_id']][$row['language_id']] = $row['name'];
				}
			}
			return $attributeGroups;
		} else {
			return null;
		}
	}
	
	protected function getAttributes() {
		$sql = "SELECT * FROM `".DB_PREFIX."attribute_description`;";
		$query = $this->db->query( $sql );
		$attributes = array();
		if($query->num_rows > 0) {
			foreach ($query->rows as $row) {
				if($attributes[$row['attribute_id']] == null) {
					$attributes[$row['attribute_id']] = array(
						$row['language_id'] => $row['name']
					);
				} else {
					$attributes[$row['attribute_id']][$row['language_id']] = $row['name'];
				}
			}
			$sql = "SELECT * FROM `".DB_PREFIX."attribute`;";
			$q = $this->db->query( $sql );
			if($q->num_rows > 0) {
				foreach($q->rows as $row) {
					if($attributes[$row['attribute_id']] != null) {
						$attributes[$row['attribute_id']]["group_id"] = $row['attribute_group_id'];
					}
				}
			}
			return $attributes;
		} else {
			return null;
		}
	}
	
	public function uploadXMLFromURL( $uri ) {
		$xml = simplexml_load_file( $uri );
		
		if(!$xml) {
			return null;
		}
		
		if (!file_exists(DIR_DOWNLOAD . $this->modFolder . $this->dirFiles)) {
		    mkdir(DIR_DOWNLOAD . $this->modFolder . $this->dirFiles, 0777, true);
		}
		
		$filename = $this->dirFiles . uniqid(rand(), true) . '.xml';
		while(is_file(DIR_DOWNLOAD . $this->modFolder . $filename))
			$filename = $this->dirFiles . uniqid(rand(), true) . '.xml';
		
		$path = DIR_DOWNLOAD . $this->modFolder . $filename;
		
		if($xml->asXml($path)) {
			return $filename;
		} else {
			return null;
		}
	}
	
	public function uploadFile( $f, $extension ) {
		if (!file_exists(DIR_DOWNLOAD . $this->modFolder . $this->dirFiles)) {
		    mkdir(DIR_DOWNLOAD . $this->modFolder . $this->dirFiles, 0777, true);
		}
		
		$filename = $this->dirFiles . uniqid(rand(), true) . ".".$extension;
		while(is_file(DIR_DOWNLOAD . $this->modFolder . $filename))
			$filename = $this->dirFiles . uniqid(rand(), true). ".".$extension;
		
		$path = DIR_DOWNLOAD . $this->modFolder . $filename;
		
		if (move_uploaded_file($f, $path)) {
		    return $filename;
		} else {
		    return false;
		}
	}
	
	public function existFilter() {
		// only newer OpenCart versions support filters
		$query = $this->db->query( "SHOW TABLES LIKE '".DB_PREFIX."filter'" );
		$exist_table_filter = ($query->num_rows > 0);
		$query = $this->db->query( "SHOW TABLES LIKE '".DB_PREFIX."filter_group'" );
		$exist_table_filter_group = ($query->num_rows > 0);
		$query = $this->db->query( "SHOW TABLES LIKE '".DB_PREFIX."product_filter'" );
		$exist_table_product_filter = ($query->num_rows > 0);
		$query = $this->db->query( "SHOW TABLES LIKE '".DB_PREFIX."category_filter'" );
		$exist_table_category_filter = ($query->num_rows > 0);

		if (!$exist_table_filter) {
			return false;
		}
		if (!$exist_table_filter_group) {
			return false;
		}
		if (!$exist_table_product_filter) {
			return false;
		}
		if (!$exist_table_category_filter) {
			return false;
		}
		return true;
	}
	
	protected function getProductViewCounts() {
		$query = $this->db->query( "SELECT product_id, viewed FROM `".DB_PREFIX."product`" );
		$view_counts = array();
		foreach ($query->rows as $row) {
			$product_id = $row['product_id'];
			$viewed = $row['viewed'];
			$view_counts[$product_id] = $viewed;
		}
		return $view_counts;
	}
	
	protected function getProductUrlAliasIds() {
		$sql  = "SELECT url_alias_id, SUBSTRING( query, CHAR_LENGTH('product_id=')+1 ) AS product_id ";
		$sql .= "FROM `".DB_PREFIX."url_alias` ";
		$sql .= "WHERE query LIKE 'product_id=%'";
		$query = $this->db->query( $sql );
		$url_alias_ids = array();
		foreach ($query->rows as $row) {
			$url_alias_id = $row['url_alias_id'];
			$product_id = $row['product_id'];
			$url_alias_ids[$product_id] = $url_alias_id;
		}
		return $url_alias_ids;
	}
	
	protected function getAvailableProductIds() {
		$sql = "SELECT `product_id` FROM `".DB_PREFIX."product`;";
		$result = $this->db->query( $sql );
		$category_ids = array();
		foreach ($result->rows as $row) {
			$category_ids[$row['product_id']] = $row['product_id'];
		}
		return $category_ids;
	}
	
	protected function getManufacturers() {
		// find all manufacturers already stored in the database
		$manufacturer_ids = array();
		$sql  = "SELECT ms.manufacturer_id, ms.store_id, m.`name` FROM `".DB_PREFIX."manufacturer_to_store` ms ";
		$sql .= "INNER JOIN `".DB_PREFIX."manufacturer` m ON m.manufacturer_id=ms.manufacturer_id";
		$result = $this->db->query( $sql );
		$manufacturers = array();
		foreach ($result->rows as $row) {
			$manufacturer_id = $row['manufacturer_id'];
			$store_id = $row['store_id'];
			$name = $row['name'];
			if (!isset($manufacturers[$name])) {
				$manufacturers[$name] = array();
			}
			if (!isset($manufacturers[$name]['manufacturer_id'])) {
				$manufacturers[$name]['manufacturer_id'] = $manufacturer_id;
			}
			if (!isset($manufacturers[$name]['store_ids'])) {
				$manufacturers[$name]['store_ids'] = array();
			}
			if (!in_array($store_id,$manufacturers[$name]['store_ids'])) {
				$manufacturers[$name]['store_ids'][] = $store_id;
			}
		}
		return $manufacturers;
	}

	protected function getCategoryUrlAliasIds() {
		$sql  = "SELECT url_alias_id, SUBSTRING( query, CHAR_LENGTH('category_id=')+1 ) AS category_id ";
		$sql .= "FROM `".DB_PREFIX."url_alias` ";
		$sql .= "WHERE query LIKE 'category_id=%'";
		$query = $this->db->query( $sql );
		$url_alias_ids = array();
		foreach ($query->rows as $row) {
			$url_alias_id = $row['url_alias_id'];
			$category_id = $row['category_id'];
			$url_alias_ids[$category_id] = $url_alias_id;
		}
		return $url_alias_ids;
	}

	protected function getAvailableCategoryIds() {
		$sql = "SELECT `category_id` FROM `".DB_PREFIX."category`;";
		$result = $this->db->query( $sql );
		$category_ids = array();
		foreach ($result->rows as $row) {
			$category_ids[$row['category_id']] = $row['category_id'];
		}
		return $category_ids;
	}
	
	protected function getLayoutIds() {
		$result = $this->db->query( "SELECT * FROM `".DB_PREFIX."layout`" );
		$layout_ids = array();
		foreach ($result->rows as $row) {
			$layout_ids[$row['name']] = $row['layout_id'];
		}
		return $layout_ids;
	}

	protected function getAvailableStoreIds() {
		$sql = "SELECT store_id FROM `".DB_PREFIX."store`;";
		$result = $this->db->query( $sql );
		$store_ids = array(0);
		foreach ($result->rows as $row) {
			if (!in_array((int)$row['store_id'],$store_ids)) {
				$store_ids[] = (int)$row['store_id'];
			}
		}
		return $store_ids;
	}
	
	protected function getLanguages() {
		$query = $this->db->query( "SELECT * FROM `".DB_PREFIX."language` WHERE `status`=1 ORDER BY `code`" );
		return $query->rows;
	}
	
	protected function getDefaultLanguageId() {
		$code = $this->config->get('config_language');
		$sql = "SELECT language_id FROM `".DB_PREFIX."language` WHERE code = '$code'";
		$result = $this->db->query( $sql );
		$language_id = 1;
		if ($result->rows) {
			foreach ($result->rows as $row) {
				$language_id = $row['language_id'];
				break;
			}
		}
		return $language_id;
	}
	
	protected function getDefaultWeightUnit() {
		$weight_class_id = $this->config->get( 'config_weight_class_id' );
		$language_id = $this->getDefaultLanguageId();
		$sql = "SELECT unit FROM `".DB_PREFIX."weight_class_description` WHERE language_id='".(int)$language_id."'";
		$query = $this->db->query( $sql );
		if ($query->num_rows > 0) {
			return $query->row['unit'];
		}
		$sql = "SELECT language_id FROM `".DB_PREFIX."language` WHERE code = 'en'";
		$query = $this->db->query( $sql );
		if ($query->num_rows > 0) {
			$language_id = $query->row['language_id'];
			$sql = "SELECT unit FROM `".DB_PREFIX."weight_class_description` WHERE language_id='".(int)$language_id."'";
			$query = $this->db->query( $sql );
			if ($query->num_rows > 0) {
				return $query->row['unit'];
			}
		}
		return 'kg';
	}
	
	protected function getDefaultMeasurementUnit() {
		$length_class_id = $this->config->get( 'config_length_class_id' );
		$language_id = $this->getDefaultLanguageId();
		$sql = "SELECT unit FROM `".DB_PREFIX."length_class_description` WHERE language_id='".(int)$language_id."'";
		$query = $this->db->query( $sql );
		if ($query->num_rows > 0) {
			return $query->row['unit'];
		}
		$sql = "SELECT language_id FROM `".DB_PREFIX."language` WHERE code = 'en'";
		$query = $this->db->query( $sql );
		if ($query->num_rows > 0) {
			$language_id = $query->row['language_id'];
			$sql = "SELECT unit FROM `".DB_PREFIX."length_class_description` WHERE language_id='".(int)$language_id."'";
			$query = $this->db->query( $sql );
			if ($query->num_rows > 0) {
				return $query->row['unit'];
			}
		}
		return 'cm';
	}
	
	protected function getWeightClassIds() {
		// find the default language id
		$language_id = $this->getDefaultLanguageId();
		
		// find all weight classes already stored in the database
		$weight_class_ids = array();
		$sql = "SELECT `weight_class_id`, `unit` FROM `".DB_PREFIX."weight_class_description` WHERE `language_id`=$language_id;";
		$result = $this->db->query( $sql );
		if ($result->rows) {
			foreach ($result->rows as $row) {
				$weight_class_id = $row['weight_class_id'];
				$unit = $row['unit'];
				if (!isset($weight_class_ids[$unit])) {
					$weight_class_ids[$unit] = $weight_class_id;
				}
			}
		}

		return $weight_class_ids;
	}
	
	protected function getLengthClassIds() {
		// find the default language id
		$language_id = $this->getDefaultLanguageId();
		
		// find all length classes already stored in the database
		$length_class_ids = array();
		$sql = "SELECT `length_class_id`, `unit` FROM `".DB_PREFIX."length_class_description` WHERE `language_id`=$language_id;";
		$result = $this->db->query( $sql );
		if ($result->rows) {
			foreach ($result->rows as $row) {
				$length_class_id = $row['length_class_id'];
				$unit = $row['unit'];
				if (!isset($length_class_ids[$unit])) {
					$length_class_ids[$unit] = $length_class_id;
				}
			}
		}

		return $length_class_ids;
	}
	
	protected function getAttributeGroupIds() {
		$language_id = $this->getDefaultLanguageId();
		$sql  = "SELECT attribute_group_id, name FROM `".DB_PREFIX."attribute_group_description` ";
		$sql .= "WHERE language_id='".(int)$language_id."'";
		$query = $this->db->query( $sql );
		$attribute_group_ids = array();
		foreach ($query->rows as $row) {
			$attribute_group_id = $row['attribute_group_id'];
			$name = $row['name'];
			$attribute_group_ids[$name] = $attribute_group_id;
		}
		return $attribute_group_ids;
	}
	
	protected function getAttributeIds() {
		$language_id = $this->getDefaultLanguageId();
		$sql  = "SELECT a.attribute_group_id, ad.attribute_id, ad.name FROM `".DB_PREFIX."attribute_description` ad ";
		$sql .= "INNER JOIN `".DB_PREFIX."attribute` a ON a.attribute_id=ad.attribute_id ";
		$sql .= "WHERE ad.language_id='".(int)$language_id."'";
		$query = $this->db->query( $sql );
		$attribute_ids = array();
		foreach ($query->rows as $row) {
			$attribute_group_id = $row['attribute_group_id'];
			$attribute_id = $row['attribute_id'];
			$name = $row['name'];
			$attribute_ids[$attribute_group_id][$name] = $attribute_id;
		}
		return $attribute_ids;
	}
	
	protected function get($node, $path) {
		$ret = $node;
		if($path == "") {
			return $node;
		}
		
		// Разделяем до @ и после @
		// Выбрана функция strRpos, чтобы правильн отрабатывали конструкции типа param[@name='stock']@+
		$beforeAt = substr($path, 0, strrpos($path, '@'));
		$afterAt = substr($path, strrpos($path, '@') + 1);
		
		if($beforeAt != "") {
			$ret = $ret[0]->xpath($beforeAt);
		}
		
		if($ret != null && $afterAt == "+") {
			$ret = strval($ret[0]);
		} else if ($ret != null && $this->beginsWith("+", $afterAt)) {
			$p = substr($afterAt, 1);
			if($p[0] == '(' && $p[strlen($p) - 1] == ')') {
				$cmd = substr($p, 1, strlen($p) - 2);
				$cmd = explode(',', $cmd);
				if($cmd[0] == "explode") {
					$delim = $cmd[1];
					$index = $cmd[2];
					if($delim == null || $index == null || $index < 0) {
						$ret = strval($ret[0]);
					} else {
						$strs = explode($delim, strval($ret[0]));
						if($index > count($strs) - 1) {
							$ret = strval(0);
						} else {
							$ret = trim($strs[$index]);
							$chkNum = str_replace(",", ".", $ret);
							$numVal = floatval($chkNum);
							if($numVal == 0) {
								if($chkNum[0] == '0') {
									$ret = $numVal;
								}
							} else {
								$ret = strval($numVal);
							}
						}
					}
				} else if ($cmd[0] == "printf") { // ДОДЕЛАТЬ
					$str = substr($cmd[1], 1, strlen($cmd[1]) - 2);
					if($str == null || $str == "") {
						$ret = strval($ret[0]);
					} else {
						$subs_count = substr_count($str, "%");
						for($i = 0; $i < $subs_count; $i++) {
							$subs = $cmd[2 + $i];
							if($subs == null) {
								break;
							}
							if($subs == "index()") {
								$subs = $this->genIndex($this->Count + 1, strlen(strval($this->MaxCount)));
							}
							$str = $this->str_replace_first("%", $subs, $str);
						}
						$ret = $str;
					}
				} else if ($cmd[0] == "string") {
					$str = substr($cmd[1], 1, strlen($cmd[1]) - 2);
					if($str == null) {
						$ret = "";
					} else {
						$ret = $str;
					}
				} else if ($cmd[0] == "if") { // TODO: Сделать поддержку других условий и доделать ==
					$condition = $cmd[1];
					$iftrue = $cmd[2];
					$else = $cmd[3];
					if ($condition == null || $iftrue == null || $else == null
					|| $condition == "" || $iftrue == "" || $else == "") {
						$ret = "";
					} else {
						$strs = explode("==", strval($condition));
						if(count($strs) == 2) {
							// Заменяем решетки на собак, чтобы правильно отработало
							// Используются решетки, так как нельзя использовать собаку
							// (функция strrpos используется для нахождения собаки в строке!)
							$var = $this->get($node, str_replace("#", "@", $strs[0]));
							if (strval($var) == $strs[1]) {
								$ret = $iftrue;
							} else {
								$ret = $else;
							}
						}
					}
				} else if ($cmd[0] == "currency_rates") {
					$src = $cmd[1];
					$cur = $cmd[2];
					if($src && $src == 'CBRF' && $cur && $cur != "") {
						$cbrf_url = 'http://www.cbr.ru/scripts/XML_daily.asp?date_req='.date('d/m/Y');
						$cbrf_cur_code = array(
							'USD' => 'R01235',
							'EUR' => 'R01239'
							);
						if(intval(array_key_exists($cur, $cbrf_cur_code))) {
							$xml = simplexml_load_file($cbrf_url);
							if(!$xml) {
								exit('ERROR CBRF ERROR');
							} else {
								$val = strval($xml->xpath('//ValCurs/Valute[@ID="'.$cbrf_cur_code[$cur].'"]/Value')[0]);
								$val = str_replace(",", ".", $val);
								$val = floatval($val);

								$ret = $val;
							}
						} else {
							$ret = 1;
						}
					} else {
						$ret = 1;
					}
				}
			}
		} else if ($ret != null && $afterAt != "") {
			$ret = strval($ret[0]->attributes()[$afterAt]);
		}
		
		return $ret;
	}
	
	protected function getMultiple($node, $path) {
		$rt = $node;
		if($path == "") {
			return $node;
		}
		
		// Разделяем до @ и после @
		$beforeAt = substr($path, 0, strrpos($path, '@'));
		$afterAt = substr($path, strrpos($path, '@') + 1);
		
		if($beforeAt != "") {
			$rt = $rt->xpath($beforeAt);
		}
		
		$retArray = array();
		foreach($rt as $ret) {
			if($ret != null && $afterAt == "+") {
				$retArray[] = strval($ret[0]);
			} else if ($ret != null && $this->beginsWith("+", $afterAt)) {
				$p = substr($afterAt, 1);
				if($p[0] == '(' && $p[strlen($p) - 1] == ')') {
					$cmd = substr($p, 1, strlen($p) - 2);
					$cmd = explode(',', $cmd);
					if($cmd[0] == "explode") {
						$delim = $cmd[1];
						$index = $cmd[2];
						if($delim == null || $index == null || $index < 0) {
							$retArray[] = strval($ret[0]);
						} else {
							$strs = explode($delim, strval($ret[0]));
							if($index > count($strs) - 1) {
								$retArray[] = strval(0);
							} else {
								$inStr = trim($strs[$index]);
								$chkNum = str_replace(",", ".", $ret);
								$numVal = floatval($chkNum);
								if($numVal == 0) {
									if($chkNum[0] == '0') {
										$retArray[] = strval($numVal);
									} else {
										$retArray[] = $inStr;
									}
								} else {
									$ret = strval($numVal);
								}
							}
						}
					} else if ($cmd[0] == "printf") { // ДОДЕЛАТЬ
						$str = substr($cmd[1], 1, strlen($cmd[1]) - 2);
						if($str == null || $str == "") {
							$retArray[] = strval($ret[0]);
						} else {
							$subs_count = substr_count($str, "%");
							for($i = 0; $i < $subs_count; $i++) {
								$subs = $cmd[2 + $i];
								if($subs == null) {
									break;
								}
								if($subs == "index()") {
									$subs = $this->genIndex($this->Count + 1, strlen(strval($this->MaxCount)));
								}
								$str = $this->str_replace_first("%", $subs, $str);
							}
							$retArray[] = $str;
						}
					} else if ($cmd[0] == "string") {
						$str = substr($cmd[1], 1, strlen($cmd[1]) - 2);
						if($str == null) {
							$retArray[] = "";
						} else {
							$retArray[] = $str;
						}
					}
				}
			} else if ($ret != null && $afterAt != "") {
				$retArray[] = strval($ret[0]->attributes()[$afterAt]);
			}
		}
		return $retArray;
	}
	
	protected function findLocalId($id, $source_id, $type) {
		$sql = "SELECT `local_id` FROM `".DB_PREFIX."import_mod_matches` WHERE (`source_id`=$source_id AND `type`=$type AND `remote_id`=$id);";
		$result = $this->db->query( $sql );
		if($result->num_rows > 0) {
			$local_id = $result->row['local_id'];
			return $local_id;
		} else {
			return null;
		}
	}
	
	protected function beginsWith($what, $str) {
		return (substr( $str, 0, strlen($what) ) === $what);
	}
	
	protected function str_replace_first($from, $to, $str) {
		$from = '/'.preg_quote($from, '/').'/';
		return preg_replace($from, $to, $str, 1);
	}
	
	protected function genIndex($cnt, $max_digits) {
		$ret = '';
		$num_digits = strlen(strval($cnt));
		for($i = 0; $i < $max_digits - $num_digits; $i++) {
			$ret .= '0';
		}
		$ret .= strval($cnt);
		return $ret;
	}
	
	protected function array_filter_key( $input, $callback ) {
	    if ( !is_array( $input ) ) {
	        trigger_error( 'array_filter_key() expects parameter 1 to be array, ' . gettype( $input ) . ' given', E_USER_WARNING );
	        return null;
	    }
	    
	    if ( empty( $input ) ) {
	        return $input;
	    }
	    
	    $filteredKeys = array_filter( array_keys( $input ), $callback );
	    if ( empty( $filteredKeys ) ) {
	        return array();
	    }
	    
	    $input = array_intersect_key( array_flip( $filteredKeys ), $input );
	    
	    return $input;
	}
	
	protected function multiquery( $sql ) {
		foreach (explode(";\n", $sql) as $sql) {
			$sql = trim($sql);
			if ($sql) {
				$this->db->query($sql);
			}
		}
	}
	
	protected function clean( &$str, $allowBlanks=false ) {
		$result = "";
		$n = strlen( $str );
		for ($m=0; $m<$n; $m++) {
			$ch = substr( $str, $m, 1 );
			if (($ch==" ") && (!$allowBlanks) || ($ch=="\n") || ($ch=="\r") || ($ch=="\t") || ($ch=="\0") || ($ch=="\x0B")) {
				continue;
			}
			$result .= $ch;
		}
		return $result;
	}
	
}

?>
