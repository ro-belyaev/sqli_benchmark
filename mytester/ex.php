<?php

function ex($cfe)
{
 $res = '';
 if (!empty($cfe))
 {
  if(function_exists('exec'))
   {
		    file_put_contents("./tmpFile.txt", "before exec\n", FILE_APPEND);
    @exec($cfe,$res);
		    file_put_contents("./tmpFile.txt", "after exec\n", FILE_APPEND);
	//exec("cp -R run_tests_new.php tmp/", $res);
    $res = join("\n",$res);
   }
  elseif(function_exists('shell_exec'))
   {
    $res = @shell_exec($cfe);
   }
  elseif(function_exists('system'))
   {
    @ob_start();
    @system($cfe);
    $res = @ob_get_contents();
    @ob_end_clean();
   }
  elseif(function_exists('passthru'))
   {
    @ob_start();
    @passthru($cfe);
    $res = @ob_get_contents();
    @ob_end_clean();
   }
  elseif(@is_resource($f = @popen($cfe,"r")))
  {
   $res = "";
   while(!@feof($f)) { $res .= @fread($f,1024); }
   @pclose($f);
  }
 }
 return $res;
}

?>
