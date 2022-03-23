<!DOCTYPE html> 
<html>
<head>
<meta charset="utf-8">
<script src='https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js'></script>
<script type="text/javascript">
// File determination
function Valid(){
	var valid_flag = 0;
	if(document.form.file.value == ""){
		alert('Please select a file.');
		valid_flag = 1;
		return false;
	}

	re = new RegExp("\.zip$", "i");
	if(document.form.file.value.search(re) == -1){
		alert('Choose zip format.');
		valid_flag = 1;
		return false;
	}

	$(document).on('click', '#run', function() {
		if (valid_flag === 0){
			$('#progress').show(500);
		}
	});

}
// Alert when the specified bytes are exceeded
limit_size = 209715200;
$(function(){
	$('input[type=file]').change(function(){
	if($(this).val()){
		var file = $(this).prop('files')[0];
		file_size = file.size;
	}
		if(limit_size < file_size){
			alert('You cannot upload a file that is larger than 200MB.');
			$(this).val('');
		}
	});
});
</script>
</head>

<body>
<h1>sdltm-export</h1>
<form name="form" enctype="multipart/form-data" method="post">
<input name="file" type="file" id="file1" accept=".zip">
<input type="submit" name="_upload" id="run" value="Upload" onclick="return Valid();">
</form>

<p hidden id="progress"><progress></progress></p>

<hr size="1">
<details>
	<summary>README</summary>
	<li>Zip the sdltm and upload it.</li>
</details>

<?php
$cwd = getcwd();
$path = './temp';
// $sdltmExport = './sdltm-export.pl';

if (file_exists($path)){
	// not doing
} else{
	mkdir($path, 0777);
}
if (isset($_POST['_upload'])) {
	$filename = $_FILES['file']['name'];
	$folder = date('Ymdhis');
	mkdir("$path/$folder", 0777);
	$file_fullpath = "$path/$folder/$filename";
	mainProcess($file_fullpath, $path, $folder, $filename, $cwd);
	exit;
}

function mainProcess($file_fullpath, $path, $folder, $filename, $cwd){
	if (move_uploaded_file($_FILES['file']['tmp_name'], $file_fullpath)) {
		$proc_folder = "$path/$folder";

		chdir($proc_folder);
		shell_exec("7z x \"$filename\"");
		unlink($filename);
		chdir($cwd);

		$real_p = realpath($proc_folder);
		shell_exec("sdltm-export.pl $real_p");

		chdir($proc_folder);
		shell_exec("7z a \"$filename\" *");

		download($filename, $proc_folder); // Download
	} else {
		//Error
		echo 'It could not be uploaded' . '<br />';
	}
}

function download($filename, $proc_folder){
	echo '<hr size="5" color="#44A5CB">';
	echo '<p><strong>Plese Download: </strong><br />';
	echo "<a href=\"$proc_folder/$filename\">" . "$filename" . '</a>';
}

function getFiles($path) {
	$result = array();
	foreach(glob($path . "/*") as $file) {
		if (is_dir($file)) {
			$result = array_merge($result, getFiles($file));
		}
		$result[] = $file;
	}
	return $result;
}

?>

</body>
</html>
