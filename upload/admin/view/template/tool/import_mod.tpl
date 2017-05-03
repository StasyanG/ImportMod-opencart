<?php echo $header; ?><?php echo $column_left; ?>
<div id="content">
	<div class="page-header">
		<div class="container-fluid">
			<div class="pull-right">
				<a href="<?php echo $back; ?>" data-toggle="tooltip" title="<?php echo $button_back; ?>" class="btn btn-default"><i class="fa fa-reply"></i></a>
			</div>
			<h1><?php echo $heading_title; ?></h1>
			<ul class="breadcrumb">
				<?php foreach ($breadcrumbs as $breadcrumb) { ?>
				<li><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a></li>
				<?php } ?>
			</ul>
		</div>
	</div>
	<div class="container-fluid">
		<?php if ($error_warning) { ?>
		<div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> <?php echo $error_warning; ?>
			<button type="button" class="close" data-dismiss="alert">&times;</button>
		</div>
		<?php } ?>
		<?php if ($success) { ?>
		<div class="alert alert-success"><i class="fa fa-check-circle"></i> <?php echo $success; ?>
			<button type="button" class="close" data-dismiss="alert">&times;</button>
		</div>
		<?php } ?>

		<ul class="nav nav-tabs">
			<li role="importmod_tabs" class="active"><a data-toggle="tab" href="#tab_sources">Manage Sources</a></li>
			<li role="importmod_tabs"><a data-toggle="tab" href="#tab_match_file_gen">Matching File Generator</a></li>
			<li role="importmod_tabs"><a data-toggle="tab" href="#tab_man_transcodes">Manufacturer Transcodes</a></li>
			<li role="importmod_tabs"><a data-toggle="tab" href="#tab_cat_transcodes">Category Transcodes</a></li>
		</ul>
		<div class="tab-content">
			<div class="tab-pane fade in active" id="tab_sources">
				<h3><b>Create Source</b></h3>
				<form action="<?php echo $create_source; ?>" method="post" enctype="multipart/form-data" id="create_source" class="form-inline">
				  	<div class="form-group">
				    	<label class="sr-only" for="inputSourceName">Source Name</label>
				    	<input type="text" name="source_name" class="form-control" id="inputSourceName" placeholder="Source Name"  autofocus>
				  	</div>
				  	<div class="form-group">
				    	<button type="button" class="btn btn-default" id="btnSelectSourceType">Source Type...</button>
				    	<input type="text" name="source_url" class="form-control" id="inputSourceURL" placeholder="Source URL">
				    	<div class="input-group" id="inputSourceFile" style="display: none;">
	                		<label class="input-group-btn">
	                    		<span class="btn btn-primary">
	                        	Source File... <input type="file" name="source_file" accept=".xml, .xls, .xlsx" style="display: none;">
	                    		</span>
	                		</label>
	                		<input type="text" class="form-control" readonly="">
	              		</div>
				  	</div>
				  	<div class="form-group">
				    	<label class="sr-only" for="inputSourceName">Source Matching File</label>
				    	<div class="input-group" id="inputSourceFile">
	                		<label class="input-group-btn">
	                    		<span class="btn btn-primary">
	                        	Source Matching File... <input type="file" name="match_file" id="match_file" accept=".json" style="display: none;">
	                    		</span>
	                		</label>
	                		<input type="text" class="form-control" readonly="">
	              		</div>
				  	</div>
				  	<button type="submit" class="btn btn-success">Create Source</button>

				  	<input type="radio" name="source_type" value="0" style="visibility: hidden;" checked="checked"/>
				</form>

				<h3><b>Sources List</b></h3>
				<p><?php echo 'Source count: '.count($sources); ?></p>
				<div class="panel source-container">
				<?php foreach ($sources as $source) { ?>
					<div class="panel panel-item">
						<div class="panel-heading">
							<b><h4><?php echo $source['name']; ?></h4></b> 
							[<b><?php echo (intval($source['status']) == 0 ? "Disabled" : "Enabled"); ?></b> | Last updated: <?php echo $source['last_updated']; ?>]
						</div>
						<div class="panel-body">
							<div class="btn-group">
								<input type="hidden" name="source_id" value="<?php echo $source['source_id']; ?>" />
								<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
									Action <span class="caret"></span>
								</button>
								<ul class="dropdown-menu">
							    	<li>
									    <a class="source_action" name="update" href="javascript:void(0);">Update</a>
							    	</li>
							    	<li>
							    		<a class="source_action" name="partial_update" href="javascript:void(0);">Partial Update</a>
							    	</li>
							    	<li role="separator" class="divider"></li>
							    	<li>
							    		<a class="source_action" name="<?php echo (intval($source['status']) == 0 ? 'enable' : 'disable'); ?>" href="javascript:void(0);"><?php echo (intval($source['status']) == 0 ? "Enable" : "Disable");?></a>
							    	</li>
								</ul>
							</div>
							<?php if($source['url']) { ?>
							<a href="<?php echo $source['url']; ?>" title="<?php echo $source['url']; ?>" target="_blank">
								<div class="btn btn-default"> View Source URL </div>
							</a>
							<?php } ?>
							<div class="btn btn-default" title="Source Local File"> <?php echo $source['path']; ?> </div>
							<div class="btn btn-default" title="Source Match File"> <?php echo $source['match_file']; ?> </div>
						</div>
					</div>
				<?php } ?>
				</div>
			</div>

			<div class="tab-pane fade" id="tab_match_file_gen">
				<h3><b>Matching File Generator</b></h3>
				<div class="row">
					<div class="col-md-6 max-height-800">
						<div class="panel panel-item">
							<div class="panel-heading">
								<h4>Result</h4>
							</div>
							<div class="panel-body">
								<div class="btn btn-default btn-block" id="updateGenResultTextarea">
									Autosize
								</div>
								<textarea class="form-contol" id="gen-result-textarea" style="width: 100%" readonly></textarea>
							</div>
						</div>
					</div>
					<div class="col-md-6 max-height-800">
						<div class="panel panel-item">
							<div class="panel-heading">
								<h4>Currency</h4>
							</div>
							<div class="panel-body">
								<div class="input-group margin-bot-5 match_gen_field">
							    	<label class="input-group-addon">
							    		<input type="checkbox" name="main_currency"> main_currency
							    	</label>
							    	<input type="text" name="main_currency" class="form-control">
							    </div><!-- /input-group -->
							    <div class="input-group margin-bot-5 match_gen_field">
							    	<label class="input-group-addon">
							    		<input type="checkbox" name="main_currency_usd_rate"> main_currency_usd_rate
							    	</label>
							    	<input type="text" name="main_currency_usd_rate" class="form-control">
							    </div><!-- /input-group -->
							</div>
						</div>
						<div class="panel panel-item">
							<div class="panel-heading">
								<h4>Categories</h4>
							</div>
							<div class="panel-body">
								<div class="input-group margin-bot-5 match_gen_field">
							    	<label class="input-group-addon">
							    		<input type="checkbox" name="categories_container"> categories_container
							    	</label>
							    	<input type="text" name="categories_container" class="form-control">
							    </div><!-- /input-group -->
							    <div class="input-group margin-bot-5 match_gen_field">
							    	<label class="input-group-addon">
							    		<input type="checkbox" name="category_item"> category_item
							    	</label>
							    	<input type="text" name="category_item" class="form-control">
							    </div><!-- /input-group -->
							    <hr />
							    <div class="input-group margin-bot-5 match_gen_field">
							    	<label class="input-group-addon">
							    		<input type="checkbox" name="category_info.category_id"> category_id
							    	</label>
							    	<input type="text" name="category_info.category_id" class="form-control">
							    </div><!-- /input-group -->
							    <?php foreach($languages as $lang) { ?>
							    <div class="input-group margin-bot-5 match_gen_field">
							    	<label class="input-group-addon">
							    		<input type="checkbox" name="category_info.name(<?php echo $lang['code']; ?>)"> name(<?php echo $lang['code']; ?>)
							    	</label>
							    	<input type="text" name="category_info.name(<?php echo $lang['code']; ?>)" class="form-control">
							    </div><!-- /input-group -->
							    <?php } ?>
							    <?php foreach($languages as $lang) { ?>
							    <div class="input-group margin-bot-5 match_gen_field">
							    	<label class="input-group-addon">
							    		<input type="checkbox" name="category_info.meta_title(<?php echo $lang['code']; ?>)"> meta_title(<?php echo $lang['code']; ?>)
							    	</label>
							    	<input type="text" name="category_info.meta_title(<?php echo $lang['code']; ?>)" class="form-control">
							    </div><!-- /input-group -->
							    <?php } ?>
							    <div class="input-group margin-bot-5 match_gen_field">
							    	<label class="input-group-addon">
							    		<input type="checkbox" name="category_info.parent_id"> parent_id
							    	</label>
							    	<input type="text" name="category_info.parent_id" class="form-control">
							    </div><!-- /input-group -->
							    <div class="input-group margin-bot-5 match_gen_field">
							    	<label class="input-group-addon">
							    		<input type="checkbox" name="category_info.top"> top
							    	</label>
							    	<input type="text" name="category_info.top" class="form-control">
							    </div><!-- /input-group -->
							    <div class="input-group margin-bot-5 match_gen_field">
							    	<label class="input-group-addon">
							    		<input type="checkbox" name="category_info.columns"> columns
							    	</label>
							    	<input type="text" name="category_info.columns" class="form-control">
							    </div><!-- /input-group -->
							    <div class="input-group margin-bot-5 match_gen_field">
							    	<label class="input-group-addon">
							    		<input type="checkbox" name="category_info.sort_order"> sort_order
							    	</label>
							    	<input type="text" name="category_info.sort_order" class="form-control">
							    </div><!-- /input-group -->
							    <div class="input-group margin-bot-5 match_gen_field">
							    	<label class="input-group-addon">
							    		<input type="checkbox" name="category_info.date_added"> date_added
							    	</label>
							    	<input type="text" name="category_info.date_added" class="form-control">
							    </div><!-- /input-group -->
							    <div class="input-group margin-bot-5 match_gen_field">
							    	<label class="input-group-addon">
							    		<input type="checkbox" name="category_info.date_modified"> date_modified
							    	</label>
							    	<input type="text" name="category_info.date_modified" class="form-control">
							    </div><!-- /input-group -->
							    <div class="input-group margin-bot-5 match_gen_field">
							    	<label class="input-group-addon">
							    		<input type="checkbox" name="category_info.seo_keyword"> seo_keyword
							    	</label>
							    	<input type="text" name="category_info.seo_keyword" class="form-control">
							    </div><!-- /input-group -->
							    <?php foreach($languages as $lang) { ?>
							    <div class="input-group margin-bot-5 match_gen_field">
							    	<label class="input-group-addon">
							    		<input type="checkbox" name="category_info.description(<?php echo $lang['code']; ?>)"> description(<?php echo $lang['code']; ?>)
							    	</label>
							    	<input type="text" name="category_info.description(<?php echo $lang['code']; ?>)" class="form-control">
							    </div><!-- /input-group -->
							    <?php } ?>
							    <?php foreach($languages as $lang) { ?>
							    <div class="input-group margin-bot-5 match_gen_field">
							    	<label class="input-group-addon">
							    		<input type="checkbox" name="category_info.meta_description(<?php echo $lang['code']; ?>)"> meta_description(<?php echo $lang['code']; ?>)
							    	</label>
							    	<input type="text" name="category_info.meta_description(<?php echo $lang['code']; ?>)" class="form-control">
							    </div><!-- /input-group -->
							    <?php } ?>
							    <?php foreach($languages as $lang) { ?>
							    <div class="input-group margin-bot-5 match_gen_field">
							    	<label class="input-group-addon">
							    		<input type="checkbox" name="category_info.meta_keywords(<?php echo $lang['code']; ?>)"> meta_keywords(<?php echo $lang['code']; ?>)
							    	</label>
							    	<input type="text" name="category_info.meta_keywords(<?php echo $lang['code']; ?>)" class="form-control">
							    </div><!-- /input-group -->
							    <?php } ?>
							    <div class="input-group margin-bot-5 match_gen_field">
							    	<label class="input-group-addon">
							    		<input type="checkbox" name="category_info.store_ids"> store_ids
							    	</label>
							    	<input type="text" name="category_info.store_ids" class="form-control">
							    </div><!-- /input-group -->
							    <div class="input-group margin-bot-5 match_gen_field">
							    	<label class="input-group-addon">
							    		<input type="checkbox" name="category_info.layout"> layout
							    	</label>
							    	<input type="text" name="category_info.layout" class="form-control">
							    </div><!-- /input-group -->
							    <div class="input-group margin-bot-5 match_gen_field">
							    	<label class="input-group-addon">
							    		<input type="checkbox" name="category_info.status"> status
							    	</label>
							    	<input type="text" name="category_info.status" class="form-control">
							    </div><!-- /input-group -->
							</div>
						</div><!-- /panel -->
						<div class="panel panel-item">
							<div class="panel-heading">
								<h4>Products</h4>
							</div>
							<div class="panel-body">
								<div class="input-group margin-bot-5 match_gen_field">
							    	<label class="input-group-addon">
							    		<input type="checkbox" name="products_container"> products_container
							    	</label>
							    	<input type="text" name="products_container" class="form-control">
							    </div><!-- /input-group -->
							    <div class="input-group margin-bot-5 match_gen_field">
							    	<label class="input-group-addon">
							    		<input type="checkbox" name="product_item"> product_item
							    	</label>
							    	<input type="text" name="product_item" class="form-control">
							    </div><!-- /input-group -->
							    <hr />
							    <div class="input-group margin-bot-5 match_gen_field">
							    	<label class="input-group-addon">
							    		<input type="checkbox" name="product_info.product_id"> product_id
							    	</label>
							    	<input type="text" name="product_info.product_id" class="form-control">
							    </div><!-- /input-group -->
							    <?php foreach($languages as $lang) { ?>
							    <div class="input-group margin-bot-5 match_gen_field">
							    	<label class="input-group-addon">
							    		<input type="checkbox" name="product_info.name(<?php echo $lang['code']; ?>)"> name(<?php echo $lang['code']; ?>)
							    	</label>
							    	<input type="text" name="product_info.name(<?php echo $lang['code']; ?>)" class="form-control">
							    </div><!-- /input-group -->
							    <?php } ?>
							    <?php foreach($languages as $lang) { ?>
							    <div class="input-group margin-bot-5 match_gen_field">
							    	<label class="input-group-addon">
							    		<input type="checkbox" name="product_info.meta_title(<?php echo $lang['code']; ?>)"> meta_title(<?php echo $lang['code']; ?>)
							    	</label>
							    	<input type="text" name="product_info.meta_title(<?php echo $lang['code']; ?>)" class="form-control">
							    </div><!-- /input-group -->
							    <?php } ?>
							    <div class="input-group margin-bot-5 match_gen_field">
							    	<label class="input-group-addon">
							    		<input type="checkbox" name="product_info.model"> model
							    	</label>
							    	<input type="text" name="product_info.model" class="form-control">
							    </div><!-- /input-group -->
							    <div class="input-group margin-bot-5 match_gen_field">
							    	<label class="input-group-addon">
							    		<input type="checkbox" name="product_info.categories"> categories
							    	</label>
							    	<input type="text" name="product_info.categories" class="form-control">
							    </div><!-- /input-group -->
							    <div class="input-group margin-bot-5 match_gen_field">
							    	<label class="input-group-addon">
							    		<input type="checkbox" name="product_info.sku"> sku
							    	</label>
							    	<input type="text" name="product_info.sku" class="form-control">
							    </div><!-- /input-group -->
							    <div class="input-group margin-bot-5 match_gen_field">
							    	<label class="input-group-addon">
							    		<input type="checkbox" name="product_info.upc"> upc
							    	</label>
							    	<input type="text" name="product_info.upc" class="form-control">
							    </div><!-- /input-group -->
							    <div class="input-group margin-bot-5 match_gen_field">
							    	<label class="input-group-addon">
							    		<input type="checkbox" name="product_info.ean"> ean
							    	</label>
							    	<input type="text" name="product_info.ean" class="form-control">
							    </div><!-- /input-group -->
							    <div class="input-group margin-bot-5 match_gen_field">
							    	<label class="input-group-addon">
							    		<input type="checkbox" name="product_info.jan"> jan
							    	</label>
							    	<input type="text" name="product_info.jan" class="form-control">
							    </div><!-- /input-group -->
							    <div class="input-group margin-bot-5 match_gen_field">
							    	<label class="input-group-addon">
							    		<input type="checkbox" name="product_info.isbn"> isbn
							    	</label>
							    	<input type="text" name="product_info.isbn" class="form-control">
							    </div><!-- /input-group -->
							    <div class="input-group margin-bot-5 match_gen_field">
							    	<label class="input-group-addon">
							    		<input type="checkbox" name="product_info.mpn"> mpn
							    	</label>
							    	<input type="text" name="product_info.mpn" class="form-control">
							    </div><!-- /input-group -->
							    <div class="input-group margin-bot-5 match_gen_field">
							    	<label class="input-group-addon">
							    		<input type="checkbox" name="product_info.location"> location
							    	</label>
							    	<input type="text" name="product_info.location" class="form-control">
							    </div><!-- /input-group -->
							    <div class="input-group margin-bot-5 match_gen_field">
							    	<label class="input-group-addon">
							    		<input type="checkbox" name="product_info.quantity"> quantity
							    	</label>
							    	<input type="text" name="product_info.quantity" class="form-control">
							    </div><!-- /input-group -->
							    <div class="input-group margin-bot-5 match_gen_field">
							    	<label class="input-group-addon">
							    		<input type="checkbox" name="product_info.manufacturer"> manufacturer
							    	</label>
							    	<input type="text" name="product_info.manufacturer" class="form-control">
							    </div><!-- /input-group -->
							    <hr />
							    <div class="input-group margin-bot-5 match_gen_field">
							    	<label class="input-group-addon">
							    		<input type="checkbox" name="product_info.images_container"> images_container
							    	</label>
							    	<input type="text" name="product_info.images_container" class="form-control">
							    </div><!-- /input-group -->
							    <div class="input-group margin-bot-5 match_gen_field">
							    	<label class="input-group-addon">
							    		<input type="checkbox" name="product_info.image_item"> image_item
							    	</label>
							    	<input type="text" name="product_info.image_item" class="form-control">
							    </div><!-- /input-group -->
							    <hr />
							    <div class="input-group margin-bot-5 match_gen_field">
							    	<label class="input-group-addon">
							    		<input type="checkbox" name="product_info.shipping"> shipping
							    	</label>
							    	<input type="text" name="product_info.shipping" class="form-control">
							    </div><!-- /input-group -->
							    <div class="input-group margin-bot-5 match_gen_field">
							    	<label class="input-group-addon">
							    		<input type="checkbox" name="product_info.price"> price
							    	</label>
							    	<input type="text" name="product_info.price" class="form-control">
							    </div><!-- /input-group -->
							    <div class="input-group margin-bot-5 match_gen_field">
							    	<label class="input-group-addon">
							    		<input type="checkbox" name="product_info.points"> points
							    	</label>
							    	<input type="text" name="product_info.points" class="form-control">
							    </div><!-- /input-group -->
							    <div class="input-group margin-bot-5 match_gen_field">
							    	<label class="input-group-addon">
							    		<input type="checkbox" name="product_info.date_added"> date_added
							    	</label>
							    	<input type="text" name="product_info.date_added" class="form-control">
							    </div><!-- /input-group -->
							    <div class="input-group margin-bot-5 match_gen_field">
							    	<label class="input-group-addon">
							    		<input type="checkbox" name="product_info.date_modified"> date_modified
							    	</label>
							    	<input type="text" name="product_info.date_modified" class="form-control">
							    </div><!-- /input-group -->
							    <div class="input-group margin-bot-5 match_gen_field">
							    	<label class="input-group-addon">
							    		<input type="checkbox" name="product_info.date_available"> date_available
							    	</label>
							    	<input type="text" name="product_info.date_available" class="form-control">
							    </div><!-- /input-group -->
							    <div class="input-group margin-bot-5 match_gen_field">
							    	<label class="input-group-addon">
							    		<input type="checkbox" name="product_info.weight"> weight
							    	</label>
							    	<input type="text" name="product_info.weight" class="form-control">
							    </div><!-- /input-group -->
							    <div class="input-group margin-bot-5 match_gen_field">
							    	<label class="input-group-addon">
							    		<input type="checkbox" name="product_info.weight_unit"> weight_unit
							    	</label>
							    	<input type="text" name="product_info.weight_unit" class="form-control">
							    </div><!-- /input-group -->
							    <div class="input-group margin-bot-5 match_gen_field">
							    	<label class="input-group-addon">
							    		<input type="checkbox" name="product_info.length"> length
							    	</label>
							    	<input type="text" name="product_info.length" class="form-control">
							    </div><!-- /input-group -->
							    <div class="input-group margin-bot-5 match_gen_field">
							    	<label class="input-group-addon">
							    		<input type="checkbox" name="product_info.width"> width
							    	</label>
							    	<input type="text" name="product_info.width" class="form-control">
							    </div><!-- /input-group -->
							    <div class="input-group margin-bot-5 match_gen_field">
							    	<label class="input-group-addon">
							    		<input type="checkbox" name="product_info.height"> height
							    	</label>
							    	<input type="text" name="product_info.height" class="form-control">
							    </div><!-- /input-group -->
							    <div class="input-group margin-bot-5 match_gen_field">
							    	<label class="input-group-addon">
							    		<input type="checkbox" name="product_info.length_unit"> length_unit
							    	</label>
							    	<input type="text" name="product_info.length_unit" class="form-control">
							    </div><!-- /input-group -->
							    <div class="input-group margin-bot-5 match_gen_field">
							    	<label class="input-group-addon">
							    		<input type="checkbox" name="product_info.status"> status
							    	</label>
							    	<input type="text" name="product_info.status" class="form-control">
							    </div><!-- /input-group -->
							    <div class="input-group margin-bot-5 match_gen_field">
							    	<label class="input-group-addon">
							    		<input type="checkbox" name="product_info.tax_class_id"> tax_class_id
							    	</label>
							    	<input type="text" name="product_info.tax_class_id" class="form-control">
							    </div><!-- /input-group -->
							    <div class="input-group margin-bot-5 match_gen_field">
							    	<label class="input-group-addon">
							    		<input type="checkbox" name="product_info.seo_keyword"> seo_keyword
							    	</label>
							    	<input type="text" name="product_info.seo_keyword" class="form-control">
							    </div><!-- /input-group -->
							    <?php foreach($languages as $lang) { ?>
							    <div class="input-group margin-bot-5 match_gen_field">
							    	<label class="input-group-addon">
							    		<input type="checkbox" name="product_info.description(<?php echo $lang['code']; ?>)"> description(<?php echo $lang['code']; ?>)
							    	</label>
							    	<input type="text" name="product_info.description(<?php echo $lang['code']; ?>)" class="form-control">
							    </div><!-- /input-group -->
							    <?php } ?>
							    <?php foreach($languages as $lang) { ?>
							    <div class="input-group margin-bot-5 match_gen_field">
							    	<label class="input-group-addon">
							    		<input type="checkbox" name="product_info.meta_description(<?php echo $lang['code']; ?>)"> meta_description(<?php echo $lang['code']; ?>)
							    	</label>
							    	<input type="text" name="product_info.meta_description(<?php echo $lang['code']; ?>)" class="form-control">
							    </div><!-- /input-group -->
							    <?php } ?>
							    <?php foreach($languages as $lang) { ?>
							    <div class="input-group margin-bot-5 match_gen_field">
							    	<label class="input-group-addon">
							    		<input type="checkbox" name="product_info.meta_keywords(<?php echo $lang['code']; ?>)"> meta_keywords(<?php echo $lang['code']; ?>)
							    	</label>
							    	<input type="text" name="product_info.meta_keywords(<?php echo $lang['code']; ?>)" class="form-control">
							    </div><!-- /input-group -->
							    <?php } ?>
							    <div class="input-group margin-bot-5 match_gen_field">
							    	<label class="input-group-addon">
							    		<input type="checkbox" name="product_info.stock_status_id"> stock_status_id
							    	</label>
							    	<input type="text" name="product_info.stock_status_id" class="form-control">
							    </div><!-- /input-group -->
							    <div class="input-group margin-bot-5 match_gen_field">
							    	<label class="input-group-addon">
							    		<input type="checkbox" name="product_info.store_ids"> store_ids
							    	</label>
							    	<input type="text" name="product_info.store_ids" class="form-control">
							    </div><!-- /input-group -->
							    <div class="input-group margin-bot-5 match_gen_field">
							    	<label class="input-group-addon">
							    		<input type="checkbox" name="product_info.layout"> layout
							    	</label>
							    	<input type="text" name="product_info.layout" class="form-control">
							    </div><!-- /input-group -->
							    <div class="input-group margin-bot-5 match_gen_field">
							    	<label class="input-group-addon">
							    		<input type="checkbox" name="product_info.related_ids"> related_ids
							    	</label>
							    	<input type="text" name="product_info.related_ids" class="form-control">
							    </div><!-- /input-group -->
							    <?php foreach($languages as $lang) { ?>
							    <div class="input-group margin-bot-5 match_gen_field">
							    	<label class="input-group-addon">
							    		<input type="checkbox" name="product_info.tags(<?php echo $lang['code']; ?>)"> tags(<?php echo $lang['code']; ?>)
							    	</label>
							    	<input type="text" name="product_info.tags(<?php echo $lang['code']; ?>)" class="form-control">
							    </div><!-- /input-group -->
							    <?php } ?>
							    <div class="input-group margin-bot-5 match_gen_field">
							    	<label class="input-group-addon">
							    		<input type="checkbox" name="product_info.sort_order"> sort_order
							    	</label>
							    	<input type="text" name="product_info.sort_order" class="form-control">
							    </div><!-- /input-group -->
							    <div class="input-group margin-bot-5 match_gen_field">
							    	<label class="input-group-addon">
							    		<input type="checkbox" name="product_info.subtract"> subtract
							    	</label>
							    	<input type="text" name="product_info.subtract" class="form-control">
							    </div><!-- /input-group -->
							    <div class="input-group margin-bot-5 match_gen_field">
							    	<label class="input-group-addon">
							    		<input type="checkbox" name="product_info.minimum"> minimum
							    	</label>
							    	<input type="text" name="product_info.minimum" class="form-control">
							    </div><!-- /input-group -->
							</div><!-- /panel-body -->
						</div><!-- /panel -->
					</div>
				</div>
			</div>

			<div class="tab-pane fade" id="tab_man_transcodes">
				<h3><b>Create Man. Transcode</b></h3>
				<form action="<?php echo $create_man_transcode; ?>" method="post" enctype="multipart/form-data" id="create_man_transcode" class="form-inline">
				  	<div class="form-group">
				    	<label class="sr-only" for="inputManFrom">From (name)</label>
				    	<input type="text" name="man_from" class="form-control" id="inputManFrom" placeholder="From (name)"  autofocus>
				  	</div>
				  	<div class="form-group">
				    	<label class="sr-only" for="inputManTo">To (name)</label>
				    	<input type="text" name="man_to" class="form-control" id="inputManTo" placeholder="To (name)">
				  	</div>
				  	<div class="form-group">
				    	<label class="sr-only" for="inputManCode">Code</label>
				    	<input type="text" name="man_code" class="form-control" id="inputManCode" placeholder="Code">
				  	</div>
				  	<div class="form-group">
				    	<label class="sr-only" for="inputManStartIndex">Start Index</label>
				    	<input type="number" min="0" name="man_start_index" class="form-control" id="inputManStartIndex" placeholder="Start Index">
				  	</div>
				  	<div class="form-group">
				    	<label class="sr-only" for="inputManIndexLength">Index Length</label>
				    	<input type="number" min="1" name="man_index_length" class="form-control" id="inputManIndexLength" placeholder="Index Length">
				  	</div>
				  	<button type="submit" class="btn btn-success">Create Transcode</button>
				  	<input type="hidden" name="redirect_to" value="tab_man_transcodes"/>
				</form>

				<h3><b>Man. Transcodes</b></h3>
				<div class="panel panel-default">
					<table class="table table-responsive">
						<thead>
							<th>From (name)</th>
							<th>To (name)</th>
							<th>Code</th>
							<th>Start Index</th>
							<th>Code Length</th>
							<th>Index Length</th>
						</thead>
						<tbody>
							<?php foreach ($man_transcodes as $from => $man) { ?>
							<tr>
								<td><?php echo $from; ?></td>
								<td><?php echo $man['to']; ?></td>
								<td><?php echo $man['code']; ?></td>
								<td><?php echo $man['start_index']; ?></td>
								<td><?php echo $man['L1']; ?></td>
								<td><?php echo $man['L2']; ?></td>
							</tr>
							<?php } ?>
						</tbody>
					</table>
				</div>
			</div>

			<div class="tab-pane fade" id="tab_cat_transcodes">
				<h3><b>Create Cat. Transcode</b></h3>
				<form action="<?php echo $create_cat_transcode; ?>" method="post" enctype="multipart/form-data" id="create_man_transcode" class="form-inline">
				  	<div class="form-group">
				    	<label class="sr-only" for="inputCatFrom">From (name)</label>
				    	<input type="text" name="cat_from" class="form-control" id="inputCatFrom" placeholder="From (name)"  autofocus>
				  	</div>
				  	<div class="form-group">
				    	<label class="sr-only" for="inputCatTo">To (name)</label>
				    	<input type="text" name="cat_to" class="form-control" id="inputCatTo" placeholder="To (name)">
				  	</div>
				  	<button type="submit" class="btn btn-success">Create Transcode</button>
				  	<input type="hidden" name="redirect_to" value="tab_cat_transcodes"/>
				</form>

				<h3><b>Cat. Transcodes</b></h3>
				<div class="panel panel-default">
					<table class="table table-responsive">
						<thead>
							<th>From (name)</th>
							<th>To (name)</th>
						</thead>
						<tbody>
							<?php foreach ($cat_transcodes as $to => $from) { ?>
							<tr>
								<td><?php echo $from; ?></td>
								<td><?php echo $to; ?></td>
							</tr>
							<?php } ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
		
	</div>

<script type="text/javascript"><!--

function getNotifications() {
	$('#export_import_notification').html('<i class="fa fa-info-circle"></i><button type="button" class="close" data-dismiss="alert">&times;</button> <div id="export_import_loading"><img src="view/image/export-import/loading.gif" /><?php echo $text_loading_notifications; ?></div>');
	setTimeout(
		function(){
			$.ajax({
				type: 'GET',
				url: 'index.php?route=tool/export_import/getNotifications&token=<?php echo $token; ?>',
				dataType: 'json',
				success: function(json) {
					if (json['error']) {
						$('#export_import_notification').html('<i class="fa fa-info-circle"></i><button type="button" class="close" data-dismiss="alert">&times;</button> '+json['error']+' <span style="cursor:pointer;font-weight:bold;text-decoration:underline;float:right;" onclick="getNotifications();"><?php echo $text_retry; ?></span>');
					} else if (json['message']) {
						$('#export_import_notification').html('<i class="fa fa-info-circle"></i><button type="button" class="close" data-dismiss="alert">&times;</button> '+json['message']);
					} else {
						$('#export_import_notification').html('<i class="fa fa-info-circle"></i><button type="button" class="close" data-dismiss="alert">&times;</button> '+'<?php echo $error_no_news; ?>');
					}
				},
				failure: function(){
					$('#export_import_notification').html('<i class="fa fa-info-circle"></i><button type="button" class="close" data-dismiss="alert">&times;</button> '+'<?php echo $error_notifications; ?> <span style="cursor:pointer;font-weight:bold;text-decoration:underline;float:right;" onclick="getNotifications();"><?php echo $text_retry; ?></span>');
				},
				error: function() {
					$('#export_import_notification').html('<i class="fa fa-info-circle"></i><button type="button" class="close" data-dismiss="alert">&times;</button> '+'<?php echo $error_notifications; ?> <span style="cursor:pointer;font-weight:bold;text-decoration:underline;float:right;" onclick="getNotifications();"><?php echo $text_retry; ?></span>');
				}
			});
		},
		500
	);
}

function check_range_type(export_type) {
	if ((export_type=='p') || (export_type=='c') || (export_type=='u')) {
		$('#range_type').show();
		$('#range_type_id').prop('checked',true);
		$('#range_type_page').prop('checked',false);
		$('.id').show();
		$('.page').hide();
	} else {
		$('#range_type').hide();
	}
}

jQuery(function($) { $.extend({
    form: function(url, data, method) {
        if (method == null) method = 'POST';
        if (data == null) data = {};

        var form = $('<form>').attr({
            method: method,
            action: url
         }).css({
            display: 'none'
         });

        var addData = function(name, data) {
            if ($.isArray(data)) {
                for (var i = 0; i < data.length; i++) {
                    var value = data[i];
                    addData(name + '[]', value);
                }
            } else if (typeof data === 'object') {
                for (var key in data) {
                    if (data.hasOwnProperty(key)) {
                        addData(name + '[' + key + ']', data[key]);
                    }
                }
            } else if (data != null) {
                form.append($('<input>').attr({
                  type: 'hidden',
                  name: String(name),
                  value: String(data)
                }));
            }
        };

        for (var key in data) {
            if (data.hasOwnProperty(key)) {
                addData(key, data[key]);
            }
        }

        return form.appendTo('body');
    }
}); });

$(document).on('change', ':file', function() {
	var input = $(this),
		numFiles = input.get(0).files ? input.get(0).files.length : 1,
		label = input.val().replace(/\\/g, '/').replace(/.*\//, '');
	input.trigger('fileselect', [numFiles, label]);
});

$(document).ready(function() {

	check_range_type($('input[name=export_type]:checked').val());

	$("#range_type_id").click(function() {
		$(".page").hide();
		$(".id").show();
	});

	$("#range_type_page").click(function() {
		$(".id").hide();
		$(".page").show();
	});

	$('span.close').click(function() {
		$(this).parent().remove();
	});

	$('a[data-toggle="tab"]').click(function() {
		$('#import_mod_notification').remove();
	});

	getNotifications();

	$(':file').on('fileselect', function(event, numFiles, label) {
	   	var input = $(this).parents('.input-group').find(':text'),
        	log = numFiles > 1 ? numFiles + ' files selected' : label;
      	if( input.length ) {
          	input.val(log);
      	} else {
          	if( log ) alert(log);
      	}
  	});
	
	$('#btnSelectSourceType').bind('click', function() {
		if($('#inputSourceURL').is(":visible")) {
			$('#inputSourceURL').hide();
			$('#inputSourceFile').show();
			$('input:radio[name="source_type"]').val('1');
		} else {
			$('#inputSourceFile').hide();
			$('#inputSourceURL').show();
			$('input:radio[name="source_type"]').val('0');
		}
	});

	$('.source_action').bind('click', function() {
		var action = $(this).attr('name');
		var link = '';
		if(action == 'update') {
			link = '<?php echo $source_update_link; ?>';
		}
		else if (action == 'partial_update') {
			link = '<?php echo $source_partial_update_link; ?>';
		}
		else if (action == 'enable') {
			link = '<?php echo $source_enable_link; ?>';
		}
		else if (action == 'disable') {
			link = '<?php echo $source_disable_link; ?>';
		}
		link = link.replace('&amp;', '&');
		var sid = $(this).closest('.btn-group').find('input[name="source_id"]').val();
		$.form(link, { 'source_id': sid }, 'POST').submit();
	});

	// Javascript to enable link to tab
	var url = document.location.toString();
	if (url.match('#')) {
	    $('.nav-tabs a[href="#' + url.split('#')[1] + '"]').tab('show');
	} //add a suffix

	// Change hash for page-reload
	$('.nav-tabs a').on('shown.bs.tab', function (e) {
	    window.location.hash = e.target.hash;
	})

	// For matching file generator
	autosize($('#gen-result-textarea'));

	$('.match_gen_field label input[type=checkbox]').on('change', function(e) {
		updateMatchFileTextarea();
	});
	$('.match_gen_field input[type=text]').on('change', function(e) {
		updateMatchFileTextarea();
	});
	// Check and disable checkboxes for necessary fields
	$('.match_gen_field').each(function() {
		var field_name = $(this).find('input[type=text]').attr('name');

		if(		field_name == 'main_currency'
			||	field_name == 'main_currency_usd_rate'
			||	field_name == 'categories_container'
			||	field_name == 'category_item'
			||	field_name == 'category_info.category_id'
			||	field_name == 'products_container'
			||	field_name == 'product_item'
			||	field_name == 'product_info.product_id'
			||	field_name == 'product_info.model'
			||	field_name.indexOf('meta_title') != -1
			||	field_name.indexOf('name') != -1) {
			$(this).find('input[type=checkbox]').prop('checked', true);
			$(this).find('input[type=checkbox]').attr('disabled', true);
		}
	});
	updateMatchFileTextarea();	

	$('#updateGenResultTextarea').on('click', function() {
		autosize.update($('#gen-result-textarea'));
	});
});

// For matching file generator
function updateMatchFileTextarea() {
	var json = new Object();
	var ta = $('#gen-result-textarea');
	$('.match_gen_field').each(function() {
		var checked = $(this).find('input[type=checkbox]').is(':checked');
		if(!checked) {
			return true;
		}
		var field_name = $(this).find('input[type=text]').attr('name');
		var field_value = $(this).find('input[type=text]').val();

		var parent = null;
		if (field_name.indexOf(".") != -1) {
			parent = field_name.substring(0, field_name.indexOf("."));
			field_name = field_name.substring(field_name.indexOf(".") + 1);
		}

		if(parent != null) {
			if(!(parent in json)) {
				json[parent] = new Object();
			}
			json[parent][field_name] = field_value;
		} else {
			json[field_name] = field_value;
		}
	});
	$(ta).val(JSON.stringify(json, undefined, 4));
	autosize.update(ta);
}

//--></script>

</div>
<?php
	echo $footer;
?>