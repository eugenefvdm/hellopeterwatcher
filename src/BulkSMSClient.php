<?php

namespace Eugenevdm;

require_once('debugger.php');

class BulkSMSClient {
    private $username;
    private $password;
    private $url;
    private $encoding;

    public function __construct($username, $password, $encoding = '7bit') {
        $this->username = $username;
        $this->password = $password;
        $this->url = 'http://bulksms.2way.co.za/eapi/submission/send_sms/2/2.0';
        $this->encoding = strtolower($encoding);
    }

    /**
     * Send SMS to multiple recipients
     * @param string $message The message to send
     * @param array|string $recipients Single number or array of numbers
     * @return array Results of sending attempts
     */
    public function sendSMS($message, $recipients) {
        debugger('SMS sending starts');
        
        // Handle different input types for recipients
        if (is_string($recipients)) {
            // Split comma-separated string into array, and trim whitespace
            $recipients = array_map('trim', explode(',', $recipients));
        } elseif (!is_array($recipients)) {
            $recipients = [$recipients];
        }

        // Filter out empty values that might come from the .env
        $recipients = array_filter($recipients);
        
        $results = [];
        foreach ($recipients as $recipient) {
            $results[$recipient] = $this->sendToSingleRecipient($message, $recipient);
        }

        $this->print_ln("Script ran to completion");
        return $results;
    }

    /**
     * Convert message to GSM-7 safe text for SMS (emoji show as ? on many networks).
     * Replaces star emoji runs with "N star " e.g. ⭐️⭐️⭐️⭐️⭐️ → "5 star ".
     */
    public function toSmsSafeMessage(string $message): string
    {
        $replaced = preg_replace_callback(
            '/(\x{2B50}\x{FE0F}?)+/u',
            function (array $m): string {
                $count = preg_match_all('/\x{2B50}/u', $m[0]);
                return $count . ' star';
            },
            $message
        );

        return $replaced ?? $message;
    }

    private function sendToSingleRecipient($message, $recipient) {
        if ($this->encoding === '16bit') {
            $shortMessage = $this->shortenForUnicodeSms($message);
            $post_body = $this->sixteen_bit_sms($shortMessage, $recipient);
        } else {
            $safeMessage = $this->toSmsSafeMessage($message);
            $post_body = $this->seven_bit_sms($safeMessage, $recipient);
        }
        $result = $this->send_message($post_body);

        if ($result['success']) {
            debugger("Success to $recipient", $result);
        } else {
            debugger("Fail to $recipient", $result);
        }

        $this->print_ln($this->formatted_server_response($result));
        return $result;
    }

    /**
     * Shorten message to fit 16-bit SMS limit (70 chars). Uses compact template for review pattern.
     * Pattern allows optional trailing text after "Please reply ASAP." (e.g. test suffix).
     */
    private function shortenForUnicodeSms(string $message, int $maxLen = 70): string
    {
        $pattern = '/^You received a (.+?) review by (.+?) at Hellopeter\. Please reply ASAP\.(.*)$/us';
        if (preg_match($pattern, $message, $m)) {
            $stars = $m[1];
            $name = trim($m[2]);
            $compact = $stars . ' review by ' . $name . ' @ Hellopeter. Reply ASAP.';
            $len = mb_strlen($compact, 'UTF-8');
            if ($len <= $maxLen) {
                debugger('SMS 16bit: using compact template', $compact);
                return $compact;
            }
            $prefix = $stars . ' review by ';
            $suffix = ' @ Hellopeter. Reply ASAP.';
            $nameMax = $maxLen - mb_strlen($prefix, 'UTF-8') - mb_strlen($suffix, 'UTF-8');
            if ($nameMax < 4) {
                $out = mb_substr($compact, 0, $maxLen - 3, 'UTF-8') . '...';
                debugger('SMS 16bit: compact truncated (name too short)', $out);
                return $out;
            }
            $truncatedName = mb_substr($name, 0, $nameMax - 3, 'UTF-8') . '...';
            $out = $prefix . $truncatedName . $suffix;
            debugger('SMS 16bit: using compact template, name truncated', $out);
            return $out;
        }
        $len = mb_strlen($message, 'UTF-8');
        if ($len <= $maxLen) {
            return $message;
        }
        $out = mb_substr($message, 0, $maxLen - 3, 'UTF-8') . '...';
        debugger('SMS 16bit: regex did not match, generic truncation used', $out);
        return $out;
    }

    /**
     * Convert UTF-8 string to hex-encoded UCS-2 big-endian for BulkSMS 16-bit API.
     */
    private function utf8ToUcs2Hex(string $utf8): string
    {
        $ucs2be = mb_convert_encoding($utf8, 'UCS-2BE', 'UTF-8');
        return bin2hex($ucs2be);
    }

    /**
     * Build post body for 16-bit Unicode SMS (emoji support, max 70 chars).
     */
    private function sixteen_bit_sms(string $message, string $msisdn): string
    {
        $message = $this->shortenForUnicodeSms($message, 70);
        $post_fields = array(
            'username' => $this->username,
            'password' => $this->password,
            'message'  => $this->utf8ToUcs2Hex($message),
            'msisdn'   => $msisdn,
            'dca'      => '16bit',
        );
        return $this->make_post_body($post_fields);
    }

    private function seven_bit_sms($message, $msisdn) {
        $post_fields = array(
            'username' => $this->username,
            'password' => $this->password,
            'message'  => $this->character_resolve($message),
            'msisdn'   => $msisdn,
            'allow_concat_text_sms' => 0,
            'concat_text_sms_max_parts' => 2
        );

        return $this->make_post_body($post_fields);
    }

    private function make_post_body($post_fields) {
        $stop_dup_id = $this->make_stop_dup_id();
        if ($stop_dup_id > 0) {
            $post_fields['stop_dup_id'] = $this->make_stop_dup_id();
        }
        $post_body = '';
        foreach( $post_fields as $key => $value ) {
            $post_body .= urlencode( $key ).'='.urlencode( $value ).'&';
        }
        $post_body = rtrim( $post_body,'&' );

        return $post_body;
    }

    private function character_resolve($body) {
        $special_chrs = array(
            'Δ'=>0xD0, 'Φ'=>0xDE, 'Γ'=>0xAC, 'Λ'=>0xC2, 'Ω'=>0xDB,
            'Π'=>0xBA, 'Ψ'=>0xDD, 'Σ'=>0xCA, 'Θ'=>0xD4, 'Ξ'=>0xB1,
            '¡'=>0xA1, '£'=>0xA3, '¤'=>0xA4, '¥'=>0xA5, '§'=>0xA7,
            '¿'=>0xBF, 'Ä'=>0xC4, 'Å'=>0xC5, 'Æ'=>0xC6, 'Ç'=>0xC7,
            'É'=>0xC9, 'Ñ'=>0xD1, 'Ö'=>0xD6, 'Ø'=>0xD8, 'Ü'=>0xDC,
            'ß'=>0xDF, 'à'=>0xE0, 'ä'=>0xE4, 'å'=>0xE5, 'æ'=>0xE6,
            'è'=>0xE8, 'é'=>0xE9, 'ì'=>0xEC, 'ñ'=>0xF1, 'ò'=>0xF2,
            'ö'=>0xF6, 'ø'=>0xF8, 'ù'=>0xF9, 'ü'=>0xFC,
        );

        $ret_msg = '';
        if( mb_detect_encoding($body, 'UTF-8') != 'UTF-8' ) {
            $body = mb_convert_encoding($body, 'UTF-8', 'auto');
        }
        for ( $i = 0; $i < mb_strlen( $body, 'UTF-8' ); $i++ ) {
            $c = mb_substr( $body, $i, 1, 'UTF-8' );
            if( isset( $special_chrs[ $c ] ) ) {
                $ret_msg .= chr( $special_chrs[ $c ] );
            }
            else {
                $ret_msg .= $c;
            }
        }
        return $ret_msg;
    }

    private function make_stop_dup_id() {
        return 0;
    }

    private function formatted_server_response( $result ) {
        $this_result = "";

        if ($result['success']) {
            $this_result .= "Success: batch ID " .$result['api_batch_id']. "API message: ".$result['api_message']. "\nFull details " .$result['details'];
        }
        else {
            $this_result .= "Fatal error: HTTP status " .$result['http_status_code']. ", API status " .$result['api_status_code']. " API message " .$result['api_message']. " full details " .$result['details'];
        }
        return $this_result;
    }

    private function send_message ( $post_body ) {
        $ch = curl_init( );
        curl_setopt ( $ch, CURLOPT_URL, $this->url );
        curl_setopt ( $ch, CURLOPT_POST, 1 );
        curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt ( $ch, CURLOPT_POSTFIELDS, $post_body );
        // Allowing cUrl funtions 20 second to execute
        curl_setopt ( $ch, CURLOPT_TIMEOUT, 20 );
        // Waiting 20 seconds while trying to connect
        curl_setopt ( $ch, CURLOPT_CONNECTTIMEOUT, 20 );

        $response_string = curl_exec( $ch );
        $curl_info = curl_getinfo( $ch );

        $sms_result = array();
        $sms_result['success'] = 0;
        $sms_result['details'] = '';
        $sms_result['http_status_code'] = $curl_info['http_code'];
        $sms_result['api_status_code'] = '';
        $sms_result['api_message'] = '';
        $sms_result['api_batch_id'] = '';

        if ( $response_string == FALSE ) {
            $sms_result['details'] .= "cURL error: " . curl_error( $ch ) . "\n";
        } elseif ( $curl_info[ 'http_code' ] != 200 ) {
            $sms_result['details'] .= "Error: non-200 HTTP status code: " . $curl_info[ 'http_code' ] . "\n";
        }
        else {
            $sms_result['details'] .= "Response from server: $response_string\n";
            $api_result = explode( '|', $response_string );
            $status_code = $api_result[0];
            $sms_result['api_status_code'] = $status_code;
            $sms_result['api_message'] = $api_result[1];
            if ( count( $api_result ) != 3 ) {
                $sms_result['details'] .= "Error: could not parse valid return data from server.\n" . count( $api_result );
            } else {
                if ($status_code == '0') {
                    $sms_result['success'] = 1;
                    $sms_result['api_batch_id'] = $api_result[2];
                    $sms_result['details'] .= "Message sent - batch ID $api_result[2]\n";
                }
                else if ($status_code == '1') {
                    # Success: scheduled for later sending.
                    $sms_result['success'] = 1;
                    $sms_result['api_batch_id'] = $api_result[2];
                }
                else {
                    $sms_result['details'] .= "Error sending: status code [$api_result[0]] description [$api_result[1]]\n";
                }
            }
        }
        curl_close( $ch );

        return $sms_result;
    }

    private function print_ln($content) {
        if (isset($_SERVER["SERVER_NAME"])) {
            print $content."<br />";
        } else {
            print $content."\n";
        }
    }
}

