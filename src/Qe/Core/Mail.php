<?php
/**
 * Created by IntelliJ IDEA.
 * User: asheng
 * Date: 2016-5-17
 * Time: 12:56
 */

namespace Qe\Core;


class Mail
{
    /* Public Variables */
    public $time_out = 30; //超时
    public $host; //服务器主机地址
    public $port = 25; //smtp_port 端口号
    public $username; //服务器用户名
    public $password; //服务器密码
    public $from; //发送地址

    var $host_name = "localhost"; //服务器主机名 is used in HELO command
    var $auth = true; //验证
    /* Private Variables */
    var $sock = FALSE;

    private static $instance;

    /* Constractor 构造方法*/
    public static function sendMail($to, $subject = "", $body = "", $mailtype = "HTML", $cc = "", $bcc = "", $additional_headers = "")
    {
        if (!static::$instance) {
            $mailer = Convert::from(\Config::$mailConfig)->to(static::class);
            static::$instance = $mailer;
        }
        static::$instance->_sendmail($to, $subject, $body, $mailtype, $cc, $bcc, $additional_headers);
    }

    public function __construct()
    {

    }

    /* Main Function */
    public function _sendmail($to, $subject = "", $body = "", $mailtype = "HTML", $cc = "", $bcc = "", $additional_headers = "")
    {
        $header = "";
        $mail_from = $this->get_address($this->strip_comment($this->from));
        $body = mb_ereg_replace("(^|(\r\n))(\\.)", "\\1.\\3", $body);
        $header .= "MIME-Version:1.0\r\n";
        if ($mailtype == "HTML") { //邮件发送类型
            //$header .= "Content-Type:text/html\r\n";
            $header .= 'Content-type: text/html; charset=utf-8' . "\r\n";
        }
        $header .= "To: " . $to . "\r\n";
        if ($cc != "") {
            $header .= "Cc: " . $cc . "\r\n";
        }
        $header .= "From: " . $this->from . "\r\n";
        $header .= "Subject: " . $subject . "\r\n";
        $header .= $additional_headers;
        $header .= "Date: " . date("r") . "\r\n";
        $header .= "X-Mailer:By (PHP/" . phpversion() . ")\r\n";
        list($msec, $sec) = explode(" ", microtime());
        $header .= "Message-ID: <" . date("YmdHis", $sec) . "." . ($msec * 1000000) . "." . $mail_from . ">\r\n";
        $TO = explode(",", $this->strip_comment($to));

        if ($cc != "") {
            $TO = array_merge($TO, explode(",", $this->strip_comment($cc))); //合并一个或多个数组
        }

        if ($bcc != "") {
            $TO = array_merge($TO, explode(",", $this->strip_comment($bcc)));
        }

        $sent = TRUE;
        foreach ($TO as $rcpt_to) {
            $rcpt_to = $this->get_address($rcpt_to);
            if (!$this->smtp_sockopen($rcpt_to)) {
                $this->log_write("Error: Cannot send email to " . $rcpt_to . "\n");
                $sent = FALSE;
                continue;
            }
            if ($this->smtp_send($this->host_name, $mail_from, $rcpt_to, $header, $body)) {
                $this->log_write("E-mail has been sent to <" . $rcpt_to . ">\n");
            } else {
                $this->log_write("Error: Cannot send email to <" . $rcpt_to . ">\n");
                $sent = FALSE;
            }
            fclose($this->sock);
            $this->log_write("Disconnected from remote host\n");
        }
        return $sent;
    }

    /* Private Functions */

    private function smtp_send($helo, $from, $to, $header, $body = "")
    {
        if (!$this->smtp_putcmd("HELO", $helo)) {
            return $this->smtp_error("sending HELO command");
        }
        #auth
        if ($this->auth) {
            if (!$this->smtp_putcmd("AUTH LOGIN", base64_encode($this->username))) {
                return $this->smtp_error("sending HELO command");
            }

            if (!$this->smtp_putcmd("", base64_encode($this->password))) {
                return $this->smtp_error("sending HELO command");
            }
        }
        #
        if (!$this->smtp_putcmd("MAIL", "FROM:<" . $from . ">")) {
            return $this->smtp_error("sending MAIL FROM command");
        }

        if (!$this->smtp_putcmd("RCPT", "TO:<" . $to . ">")) {
            return $this->smtp_error("sending RCPT TO command");
        }

        if (!$this->smtp_putcmd("DATA")) {
            return $this->smtp_error("sending DATA command");
        }

        if (!$this->smtp_message($header, $body)) {
            return $this->smtp_error("sending message");
        }

        if (!$this->smtp_eom()) {
            return $this->smtp_error("sending <CR><LF>.<CR><LF> [EOM]");
        }

        if (!$this->smtp_putcmd("QUIT")) {
            return $this->smtp_error("sending QUIT command");
        }

        return TRUE;
    }

    private function smtp_sockopen($address)
    {
        if ($this->host == "") {
            return $this->smtp_sockopen_mx($address);
        } else {
            return $this->smtp_sockopen_relay();
        }
    }

    private function smtp_sockopen_relay()
    {
        $this->log_write("Trying to " . $this->host . ":" . $this->port . "\n");
        $this->sock = @fsockopen($this->host, $this->port, $errno, $errstr, $this->time_out);
        if (!($this->sock && $this->smtp_ok())) {
            $this->log_write("Error: Cannot connenct to relay host " . $this->host . "\n");
            $this->log_write("Error: " . $errstr . " (" . $errno . ")\n");
            return FALSE;
        }
        $this->log_write("Connected to relay host " . $this->host . "\n");
        return TRUE;;
    }

    private function smtp_sockopen_mx($address)
    {
        $domain = ereg_replace("^.+@([^@]+)$", "\\1", $address);
        if (!@getmxrr($domain, $MXHOSTS)) {
            $this->log_write("Error: Cannot resolve MX \"" . $domain . "\"\n");
            return FALSE;
        }
        foreach ($MXHOSTS as $host) {
            $this->log_write("Trying to " . $host . ":" . $this->port . "\n");
            $this->sock = @fsockopen($host, $this->port, $errno, $errstr, $this->time_out);
            if (!($this->sock && $this->smtp_ok())) {
                $this->log_write("Warning: Cannot connect to mx host " . $host . "\n");
                $this->log_write("Error: " . $errstr . " (" . $errno . ")\n");
                continue;
            }
            $this->log_write("Connected to mx host " . $host . "\n");
            return TRUE;
        }
        $this->log_write("Error: Cannot connect to any mx hosts (" . implode(", ", $MXHOSTS) . ")\n");
        return FALSE;
    }

    private function smtp_message($header, $body)
    {
        fputs($this->sock, $header . "\r\n" . $body);

        return TRUE;
    }

    private function smtp_eom()
    {
        fputs($this->sock, "\r\n.\r\n");

        return $this->smtp_ok();
    }

    private function smtp_ok()
    {
        $response = str_replace("\r\n", "", fgets($this->sock, 512));

        if (!mb_ereg("^[23]", $response)) {
            fputs($this->sock, "QUIT\r\n");
            fgets($this->sock, 512);
            $this->log_write("Error: Remote host returned \"" . $response . "\"\n");
            return FALSE;
        }
        return TRUE;
    }

    private function smtp_putcmd($cmd, $arg = "")
    {
        if ($arg != "") {
            if ($cmd == "")
                $cmd = $arg;
            else
                $cmd = $cmd . " " . $arg;
        }

        fputs($this->sock, $cmd . "\r\n");

        return $this->smtp_ok();
    }

    private function smtp_error($string)
    {
        $this->log_write("Error: Error occurred while " . $string . ".\n");
        return FALSE;
    }

    private function log_write($message)
    {
        Logger::getLogger()->info($message);
        return TRUE;
    }

    private function strip_comment($address)
    {
        $comment = "\\([^()]*\\)";
        while (mb_ereg($comment, $address)) {
            $address = mb_ereg_replace($comment, "", $address);
        }

        return $address;
    }

    private function get_address($address)
    {
        $address = mb_ereg_replace("([ \t\r\n])+", "", $address);
        $address = mb_ereg_replace("^.*<(.+)>.*$", "\\1", $address);

        return $address;
    }

}
