<?php

require 'dbwrapper_class.php';

class smartsms{

    private static $db;
    private $error = [];

    public function __construct()  {
        $db = new dbwrapper();

        // Get apix token
        $token_name = 'apix';
        $where = array('st_token_name' => $token_name);
        $apix_tokens = $db->select_where('tokens',$where);
        foreach($apix_tokens as $apix_token) {
            $this->apix_token = $apix_token['st_token'];
        }

        // Get server token
        $server_name = 'server';
        $where = array('st_token_name' => $server_name);
        $server_tokens = $db->select_where('tokens',$where);
        foreach($server_tokens as $server_token) {
            $this->server_token = $server_token['st_token'];
        }

        // Get senderID
        $product_name = 'smartsms';
        $where = array('ssi_product_name' => $product_name);
        $sender_ids = $db->select_where('sender_id',$where);
        foreach($sender_ids as $s) {
            $this->sender_id = $s['ssi_sender_id'];
        }

        // Get app name
        $product_name = 'smartsms';
        $where = array('ss_product_name' => $product_name);
        $app_name_codes = $db->select_where('sms_otp_app_name_code', $where);
        foreach ($app_name_codes as $a) {
            $this->app_name_code = $a['ss_app_name_code'];
        }

        // Get template code
        $product_name = 'smartsms';
        $where = array('stc_product_name' => $product_name);
        $template_codes = $db->select_where('template_code',$where);
        foreach($template_codes as $t) {
            $this->template_code = $t['stc_code'];
        }
    }

    private function get_apix_token() {
        return $this->apix_token;
    }

    private function get_server_token() {
        return $this->server_token;
    }

    private function get_app_name_code() {
        return $this->app_name_code;
    }

    private function get_template_code(){
        return $this->template_code;
    }

    private function get_senderID(){
        return $this->sender_id;
    }


    public function generate_refid(){

        $ref_id_generator = substr(sha1(time()), 0, 16);
        return 'refId'. $ref_id_generator;

    }

    public function generate_otp(){

        $digits = 6;
            $otp = '';
            for ($i = 0; $i < $digits; $i++) {
                $otp .= rand(0, 9);
            }
        return $otp;

    }

    public function sendsms($senderID, $receipients, $message, $type, $route,$schedule, $ref_id){

        $db = new dbwrapper();
        $apix_token;
        $curl = curl_init();
        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://app.smartsmssolutions.com/io/api/client/v1/sms/',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => array(
                                        'token' => $this::get_apix_token(),
                                        'sender' => $senderID,
                                        'to' => $receipients,
                                        'message' => $message,
                                        'type' => $type,
                                        'routing' => $route,
                                        'ref_id' => $ref_id,
                                        'simserver_token' => $this::get_server_token(),
                                        'schedule' => $schedule,

                                    ),
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        echo '<pre>';
        echo $response;

        $data = json_decode($response, true);

        // Insert data into database
        if ($data !== null && is_array($data)) {
            $store = $db->insert('response_callback', $data);

        }

    }   

    public function get_balance(){
        
        $curl = curl_init();
        $token = $this->get_apix_token();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'http://app.smartsmssolutions.com/io/api/client/v1/balance/?token='.$token.'',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        $data = file_get_contents("php://input");
        $events = json_decode($response, true);

        // echo "Your current SmartSMS balance is: " . $response;

        return $response;

    }

    public function submit_sender_id($senderID, $message_content, $organisation_name, $registration_number, $business_address){
        $db = new dbwrapper();
        $token = $this->get_apix_token();

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://app.smartsmssolutions.com/io/api/client/v1/senderid/create/',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => array(
                                    'token'         => $this::get_apix_token(),
                                    'senderid'      => $senderID,
                                    'message'       => $message_content,
                                    'organisation'  => $organisation_name,
                                    'regno'         => $registration_number,
                                    'address'       => $business_address
                                ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return $response;

        $data = json_decode($response, true);

        if (isset($data['success'])) {
            $success = $data['success'] ? 'true' : 'false';
            $comment = $data['comment'];
            $store = $db->insert('response_submit_senderid', array('success' => $success, 'comment' => $comment));
        }



    }

    public function push_dlr(){
        $data = file_get_contents("php://input");
        $events = json_decode($data, true);

        if (is_array($events)) {

            foreach ($events as $event) {

                $sender_id = $event['sender'];
                $phone_number = $event['mobile'];
                $message_body = $event['message'];
                $messagetype = $event['messagetype'];
                $message_status = $event['status'];
                $delivery_description = $event['desc'];
                $smscount = $event['smscount'];
                $message_cost = $event['cost'];
                $time_sent = $event['senttime'];
                $time_processed = $event['done'];
                $ref_id = $event['ref_id'];
                $xmxid = $event['xmxid'];

                    //Do Something here
                $data = array (
                    'sender' => $sender_id,
                    'mobile' => $phone_number,
                    'message' => $message_body,
                    'messagetype' => $messagetype,
                    'status' => $message_status,
                    'description' => $delivery_description,
                    'smscount' => $smscount,
                    'cost' => $message_cost,
                    'senttime' => $time_sent,
                    'done' => $time_processed,
                    'ref_id' => $ref_id,
                    'xmxid' => $xmxid,
                );
                
            }
            $store = $db->update("callback", $data);
        }
        return $events;
    }
    

    public function get_phone_info($receipients, $type){
        $token = $this->get_apix_token();

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://app.smartsmssolutions.com/io/api/client/v1/phone/info/?token='. $token .'&phone='. @$receipients .'&type='. @$type .'',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        echo $response;

    }


    public function send_sms_otp($receipients){

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://app.smartsmssolutions.com/io/api/client/v1/smsotp/send/',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => array(
                                    'token' => $this::get_apix_token(),
                                    'phone' => $receipients,
                                    'otp' => $this::generate_otp(),
                                    'sender' => $this::get_senderID(),
                                    'app_name_code' => $this::get_app_name_code(),
                                    'template_code' => $this::get_template_code(),
                                    'ref_id' => $this::generate_refid()
                                ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return $response;

    }




























    
    

    public function inbox_forward(){
        $data = file_get_contents("php://input");
        $events = json_decode($data, true);
        
        if (is_array($events)) {
            foreach ($events as $event) {

                print_r($event);

                $from = $event['from'];
                $message = $event['message'];
                $server_name = $event['server_name'];
                $server_number = $event['server_number'];
                $server_token = $event['server_token'];
                $received_time = $event['received_time'];

                    //Do Something here
                
            }
        }
    }
    
}

$sms = new smartsms();
