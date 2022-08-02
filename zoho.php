<?php
echo "<h1>Processing........</h1>";
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

$url = 'https://bridgeportart.api-us1.com';
$params = array(
    'api_key' => 'c3febc3e65a690b66fb9a2b608592dce0aebcce86445612b44845a17a6be2eebe7a7a92f',
    'api_action' => 'contact_list',
    'api_output' => 'json',
    'filters[listid]' => 3,
    'full' => 1,
    'sort'=>'id',
    'sort_direction'=>'DESC',
    'page'=>1
);
$query = http_build_query($params);
$api = $url . '/admin/api.php?' . $query;
$ch = curl_init($api);
curl_setopt($ch, CURLOPT_HEADER, 0);
//curl_setopt($ch, CURLOPT_URL, $api);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
//curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
// curl_setopt($ch, CURLOPT_HTTPHEADER ,  array("Content-Type:application/x-www-form-urlencoded"));
$response = curl_exec($ch);
curl_close($ch);
$contacts = json_decode($response , true);
//debug($contacts , false);

if(empty($contacts[0]['email'])){
  exit("contacts not found");
}

$sql = "select * from zoho where id > 0";
$result = mysqli_query($conn, $sql);
$zoho = mysqli_fetch_array($result);
if(!empty($zoho['refresh_token'])){
    $url = 'https://accounts.zoho.com/oauth/v2/token';
    $params = array(
        'client_id' => CLIENT_ID,
        'grant_type' => 'refresh_token',
        'client_secret' => CLIENT_SECRET,
        'refresh_token' => $zoho['refresh_token'],
    );
    $post_data = http_build_query($params);
    $query = $post_data;
    $api = $url.'?'.$query;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST' );
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch,CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    //curl_setopt($ch, CURLOPT_POSTFIELDS,  json_encode($params));
    //curl_setopt($ch, CURLOPT_HTTPHEADER ,  array("Content-Type:application/json"));
    $response = curl_exec($ch);
    curl_close($ch);
    $response = json_decode($response , true);
    //debug($response , false);
    if(!empty($response['access_token'])){
      foreach ($contacts as $row) {
          if(empty($row['id'])){
            continue;
          }
          $sql = "select * from zoho_contacts where ac_contact_id = '".$row['id']."' AND is_sent = 1";
          $query = mysqli_query($conn, $sql);
          if(mysqli_num_rows($query) == 0){
              $event_date = (!empty($row['fields'][48]['val']))?$row['fields'][48]['val']:'N/A';
              $event_space = (!empty($row['fields'][55]['val']))?$row['fields'][55]['val']:'N/A';
              $event_type = (!empty($row['fields'][49]['val']))?$row['fields'][49]['val']:'N/A';
              $date_flexible = (!empty($row['fields'][50]['val']))?$row['fields'][50]['val']:'N/A';
              $guest_count = (!empty($row['fields'][51]['val']))?$row['fields'][51]['val']:'N/A';
              $overall_budget = (!empty($row['fields'][52]['val']))?$row['fields'][52]['val']:'N/A';
              $hear_about_skyline_loft = (!empty($row['fields'][53]['val']))?$row['fields'][53]['val']:'N/A';
              $additional_requests = (!empty($row['fields'][54]['val']))?$row['fields'][54]['val']:'N/A';
              $url = 'https://www.zohoapis.com/crm/v2/Contacts';
              $post = [
                'data' => [
                  [
                    'First_Name' => $row['first_name'],
                    'Last_Name' => $row['last_name'],
                    'Email' => $row['email'],
                    'Phone' => $row['phone'],
                    'Event_Date' => $event_date,
                    'Event_Space' => $event_space,
                    'Event_Type' => $event_type,
                    'Is_Your_Date_Flexible' => $date_flexible,
                    'Guest_Count' => $guest_count,
                    'Overall_Budget' => $overall_budget,
                    'Where_did_you_hear_about_Skyline_Loft' => $hear_about_skyline_loft,
                    'Additional_Requests_And_Comments' => $additional_requests,
                  ]
                ]
              ];
              //echo json_encode($post);
              $ch = curl_init();
              curl_setopt($ch, CURLOPT_URL, $url);
              curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST' );
              curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
              curl_setopt($ch,CURLOPT_SSL_VERIFYPEER, false);
              curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
              curl_setopt($ch, CURLOPT_POSTFIELDS,  json_encode($post));
              curl_setopt($ch, CURLOPT_HTTPHEADER ,  array(
                "Content-Type:application/json",
                "Authorization: Zoho-oauthtoken ".$response['access_token']
              ));
              $response2 = curl_exec($ch);
              curl_close($ch);
              $response2 = json_decode($response2 , true);
              //debug($response2 , false);
              if(!empty($response2['data']['0']['code']) && $response2['data']['0']['code']=="SUCCESS"){
                $sql = "INSERT INTO zoho_contacts (ac_contact_id,first_name,last_name,email,phone,zoho_response) VALUES ('".$row['id']."','".$row['first_name']."','".$row['last_name']."','".$row['email']."','".$row['phone']."','".json_encode($response2)."')";
                mysqli_query($conn, $sql);
              }
          }
      }
    }
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

echo "<h1>Done</h1>";
echo "<hr>";

?>