<?php

class smartsms{

    private function generate_refid(){

        // This is a sample refID generator. Design refID as preferred
        $ref_id_generator = substr(sha1(time()), 0, 16);
        return 'refId'. $ref_id_generator;

    }

    private function get_server_token(){

        $server_token = 'Enter Token Here';
        return $server_token;

    }

    private function get_apix_token(){
        $apix_token = 'Enter Token Here';
        return $apix_token;
    }

    public function sendsms(){
        if(isset ($_POST['send_sms'])) {
            $senderID         = $_POST['senderId'];
            $receipients      = $_POST['to'];
            $message          = $_POST['message'];
            $type             = $_POST['type'];
            $route            = $_POST['routing'];
            $sms = new smartsms();
            
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
                                            'ref_id' => $sms->generate_refid(),
                                            //'simserver_token' => 'Ls3p7ySpccZWGMR2oHCY8GzNcAYrLJxpQ2fT7BCJxzQaR2Iptd878UYrMeTAW3fNpPjtyiYthD5gIsEpEDDttdcKdP2uUEcdiQeO',
                                        ),
            ));
            
            $response = curl_exec($curl);
            
            curl_close($curl);
            echo $response;

            header("Location: ../send_sms.php");

            //header("Location: http://www.redirect.to.url.com/");

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

        echo "Your current SmartSMS balance is: " . $response;

    }

    public function submit_sender_id(){

        if(isset ($_POST['submit_sender_id'])) {
            $senderID                = $_POST['sender-id'];
            $message_content         = $_POST['message-content'];
            $organisation_name       = $_POST['organisation-name'];
            $registration_number     = $_POST['registration-number'];
            $business_address        = $_POST['business-address'];
            $sms = new smartsms();

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
            echo $response;
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
                
            }
        }
    }

    public function get_phone_info(){
        $token = $this->get_apix_token();

        if (isset($_POST['get_phone_info_submit'])) {
            $phone_numbers = $_POST['phone_numbers'];
            $type = $_POST['type'];

            //echo($phone_numbers. ' ' .  $type);


            $curl = curl_init();

            curl_setopt_array($curl, array(
            //CURLOPT_URL => 'https://app.smartsmssolutions.com/io/api/client/v1/phone/info/?token='. $token .'&phone='. $phone_numbers .'&type='. $type .'',
              CURLOPT_URL => 'https://app.smartsmssolutions.com/io/api/client/v1/phone/info/?token='. $token .'&phone='. $phone_numbers .'&type='. $type .'',
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

    }
    
}

$sms = new smartsms();
