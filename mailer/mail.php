<?php
header("Content-Type: text/html; charset=UTF-8");
//error_reporting(E_ALL);
//ini_set("display_errors", 1);

require 'class.phpmailer.php';
require 'class.smtp.php';
require 'mail-config.php';

// any messages that will be added along the way
$result = 'success'; // the final result of the request (success|fail)
$messages = [];

//validation//
sleep(3);

$firstname = trim($_POST['firstname']);
$surname = trim($_POST['surname']);
$phoneNumber = trim($_POST['phone-number']);
$email = trim($_POST['email']);
$yourMessage = trim($_POST['your-message']);
$honeypot = $_POST['honeypot'];
$humancheck = $_POST['humancheck'];

if(empty($_POST)){
    echo 'not a POST';
    exit();
}

function send_email($to_address, $template, $subject)
{
    global $config;

    $mail = new PHPMailer();

    $mail->isSMTP();
//    $mail->SMTPDebug =2; //check bug
    $mail->Host = $config['mail']['server'];
    $mail->SMTPAuth = true;
    $mail->Username = $config['mail']['emailaddress'];
    $mail->Password = $config['mail']['password'];
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    $mail->CharSet = 'UTF-8';
    $mail->setFrom($config['mail']['from'], $config['mail']['fromname']);//should change to admin mail address for info
    $mail->addAddress($to_address);
    $mail->Subject = $subject;
    $mail->isHTML(true);
    $mail->addEmbeddedImage('../img/logo-brown.png', 'logo-brown');

    ob_start();

    include $template;

    $html = ob_get_clean();

    $mail->Body = $html;

    if ($mail->send()) {
        return true; // all ok
    } else {
        return false; // error        
    }


}

if($honeypot == 'http://' && empty($humancheck)) {
    $errors = array();

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "<p>Neplatný formát e-mailu.</p>";
    }

    if(empty($firstname)){
        $errors[] = "<p>Vyplňte prosím své jméno.</p>";
    }

    if(empty($surname)){
        $errors[] = "<p>Vyplňte prosím své příjmení.</p>";
    }

    if(empty($yourMessage)){
        $errors[] = "<p>Vaše zpráva.</p>";
    }

    if(!empty($errors)){
        $messages[] = ['type' => 'error', 'text' => '<h4>Prosíme vyplňte formulář znovu.</h4>'];
        foreach($errors as $error)
        {
            $messages[] = ['type' => 'error', 'text' => $error];
        }
        $result = 'fail';
    }else{

         // send to admin(s)
        foreach($config['sendto'] as $sendto_address)
        {
            if(!send_email($sendto_address, 'admin-email.php', '[Rezidence Žalanského] Contact from a customer'))
            {
                $result = 'fail';
                break;
            }
        }

        if($result == 'fail') // one of the sending above did not succeed
        {
            $messages[] = ['type' => 'error', 'text' => 'Zpráva nebyla odeslána.'];
        }
        else // all of them succeeded
        {
            $messages[] = ['type' => 'success', 'text' => '<h4>Děkujeme Vám za kontaktování!</h4>'];
        }
    }
} else{
    $messages[] = ['type' => 'error', 'text' => '<h4>Došlo k problému s odesláním formuláře. Prosím zopakujte.</h4>'];
    $result = 'fail';
}

header('Content-Type: application/json');
header("Cache-Control: no-cache, must-revalidate");

echo json_encode([
    'result' => $result,
    'messages' => $messages
]);
exit();
//header('Location: ../index.html');