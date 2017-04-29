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
					<div class="panel source-item">
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
});

function checkFileSize(id) {
	// See also http://stackoverflow.com/questions/3717793/javascript-file-upload-size-validation for details
	var input, file, file_size;

	if (!window.FileReader) {
		// The file API isn't yet supported on user's browser
		return true;
	}

	input = document.getElementById(id);
	if (!input) {
		// couldn't find the file input element
		return true;
	}
	else if (!input.files) {
		// browser doesn't seem to support the `files` property of file inputs
		return true;
	}
	else if (!input.files[0]) {
		// no file has been selected for the upload
		alert( "<?php echo $error_select_file; ?>" );
		return false;
	}
	else {
		file = input.files[0];
		file_size = file.size;
		<?php if (!empty($post_max_size)) { ?>
		// check against PHP's post_max_size
		post_max_size = <?php echo $post_max_size; ?>;
		if (file_size > post_max_size) {
			alert( "<?php echo $error_post_max_size; ?>" );
			return false;
		}
		<?php } ?>
		<?php if (!empty($upload_max_filesize)) { ?>
		// check against PHP's upload_max_filesize
		upload_max_filesize = <?php echo $upload_max_filesize; ?>;
		if (file_size > upload_max_filesize) {
			alert( "<?php echo $error_upload_max_filesize; ?>" );
			return false;
		}
		<?php } ?>
		return true;
	}
}

function isNumber(txt){ 
	var regExp=/^[\d]{1,}$/;
	return regExp.test(txt); 
}

function updateSettings() {
	$('#settings').submit();
}
//--></script>

</div>
<?php
	echo $footer;
?>