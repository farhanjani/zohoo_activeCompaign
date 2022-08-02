<?php
// echo "<h1>Processing........</h1>";
ini_set('max_execution_time', 0);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

define("CLIENT_ID", "1000.0L869TUBMZHLD3M79NR7LTNXV9SRXR");
define("CLIENT_SECRET", "6e540b3a8034fe4ddf69ebb68a30c409e1155a0fed");
define("AUTHORIZED_REDIRECT_URI", "https://uflow.co.uk/home/zoho_webhook");
define("LIST_ID", 3);

// $servername = "localhost";
// $username = "root";
// $password = "";
// $dbname = "uflow_own";

$servername = "localhost";
$username = "uflow_orders";
$password = "xZeH@!dY^}^%";
$dbname = "uflow_orders";

############# Create connection #############
$conn = new mysqli($servername, $username, $password, $dbname);

############# Check connection #############
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

createLog(time());

if(!empty($_POST)){
  //file_put_contents('./log_'.date("j.n.Y").'.log', json_encode($_POST), FILE_APPEND);
  createLog(json_encode($_POST));
}


function createLog($data = '')
{
    if(empty($data)){
        return false;
    }
    $log_filename = "zoho_logsss";
    if (!file_exists($log_filename)){
        mkdir($log_filename, 0777, true);
    }
    $log_file_data = $log_filename.'/log_zoho_' . date('d-M-Y') . '.log';
    file_put_contents($log_file_data, $data."\n\n", FILE_APPEND);
    return false;
}

function debug($arr, $exit = true)
{
    print "<pre>";
        print_r($arr);
    print "</pre>";
    if($exit)
        exit;
}

// echo "<h1>Done</h1>";
// echo "<hr>";

?>