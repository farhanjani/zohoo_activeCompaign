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
define("BASE_URL_ACTIVE_COMPAIGN", "https://bridgeportart.api-us1.com");

// $servername = "localhost";
// $username = "root";
// $password = "";
// $dbname = "uflow_own";

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "powrserver_zoho";

############# Create connection #############
$conn = new mysqli($servername, $username, $password, $dbname);

############# Check connection #############
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}



$contact_id = "1953";
$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => BASE_URL_ACTIVE_COMPAIGN."/api/3/contacts/$contact_id",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "GET",
    CURLOPT_HTTPHEADER => [
        "Accept: application/json",
        "Api-Token: c3febc3e65a690b66fb9a2b608592dce0aebcce86445612b44845a17a6be2eebe7a7a92f"
    ],
]);

$response = curl_exec($curl);
$err = curl_error($curl);
$response = json_decode($response);
curl_close($curl);
$data = array();
if ($err) {
    echo "cURL Error #:" . $err;
} else {
    $contact_info = $response->contact;
    $data['First_Name'] = $contact_info->firstName;
    $data['Last_Name'] = $contact_info->lastName;
    $data['Email'] = $contact_info->email;
    $data['Phone'] = $contact_info->phone;
    $data['Lead_Source'] = 'Automation for form Rockwell Event Inquiry';
    $fieldValues = $response->fieldValues;
    foreach ($fieldValues as $field) {
        if($field->field == 48) {
            $data['Event_Date'] = $field->value;
        }
        if($field->field == 73) {
            $data['Event_Type'] = $field->value;
        }
        if($field->field == 72) {
            $data['Is_Your_Date_Flexible'] = $field->value;
        }
        if($field->field == 51) {
            $data['Guest_Count'] = $field->value;
        }
        if($field->field == 74) {
            $data['Overall_Budget'] = $field->value;
        }
        if($field->field == 53) {
            $data['Where_did_you_hear_about_Skyline_Loft'] = $field->value;
        }
        if($field->field == 54) {
            $data['Additional_Requests_And_Comments'] = $field->value;
        }
        if($field->field == 70) {
            $data['Company'] = $field->value;
        }
    }
    //send data to zooho api
    $sql = "select * from zoho where id > 0";
    $result = mysqli_query($conn, $sql);
    $zoho = mysqli_fetch_array($result);

    $sql_check = "select * from zoho_leads where ac_contact_id = $contact_id";
    $check_contact = mysqli_query($conn, $sql_check);
    $contact_exist = mysqli_fetch_array($check_contact);

    if(!empty($zoho['refresh_token']) && empty($contact_exist)){
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
        if(!empty($response['access_token'])){
            $url = 'https://www.zohoapis.com/crm/v2/Leads';
            $post = [
                'data' => array($data)
            ];
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
            if(!empty($response2['data']['0']['code']) && $response2['data']['0']['code']=="SUCCESS"){
                $sql = "INSERT INTO zoho_leads (ac_contact_id,first_name,last_name,email,phone,zoho_response) VALUES ('".$contact_id."','".$data['First_Name']."','".$data['Last_Name']."','".$data['Email']."','".$data['Phone']."','".json_encode($response2)."')";
                mysqli_query($conn, $sql);
            }
        }
    }
}

?>