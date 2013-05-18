<?

require_once './constants.php';
require_once './connection.php';

if(!isset($_GET['id'])) {
    die('id GET parameter is required!\n');
}

$id = mysql_real_escape_string($_GET['id']);
$query = "SELECT state FROM $table WHERE id='$id'";
$result = mysql_query($query);

if(!$result) {
    die('no generation with such id');
} else {
    $status = mysql_fetch_row($result)[0];
    if($status == STATE_CANCEL) {
	die('generation with such id is already canceled');
    } else if($status == STATE_COMPLETE) {
	require './clear.php';
	echo "ok";
    } else if($status == STATE_PROGRESS) {
	echo "progress";
    }
}
