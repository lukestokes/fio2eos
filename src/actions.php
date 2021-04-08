<?php

if ($action == "logout") {
    // Unset all of the session variables.
    $_SESSION = array();
    // If it's desired to kill the session, also delete the session cookie.
    // Note: This will destroy the session, and not just the session data!
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    // Finally, destroy the session.
    session_destroy();
    header("Location: index.php");
}

if ($action == "request_fio_address") {
    $fio_address_requested = strip_tags($_REQUEST["fio_address_requested"]);
    $FIOAddressRequest = $Factory->new("FIOAddressRequest");
    $already_exists = $FIOAddressRequest->read(['fio_address_requested', "=", $fio_address_requested]);
    if (!$already_exists) {
        $FIOAddressRequest->fio_address_requested = $fio_address_requested;
        $FIOAddressRequest->eos_actor = $_SESSION['eos_actor'];
        $FIOAddressRequest->eos_public_key = $_SESSION['eos_public_key'];
        $FIOAddressRequest->eos_balance = $_SESSION['eos_balance'];
        $FIOAddressRequest->date_requested = time();
        $FIOAddressRequest->ip_address = $_SERVER['REMOTE_ADDR'];
        $FIOAddressRequest->save();
        $notice .= '<div style="color: green;">Thank you. Your request has been saved. Please wait for approval.</div>';
    } else {
        $notice .= '<div style="color: red;">Has already been requested. Please wait for approval.</div>';
    }
}

if ($action == "finish_eos_login") {
    $proof = json_decode($_REQUEST["identity_proof"], true);
    try {
        $identity_response = $eos_client->post('https://eosio.greymass.com/prove', [
            GuzzleHttp\RequestOptions::JSON => ['proof' => $proof], // or 'json' => [...]
        ]);
        $identity_results          = json_decode($identity_response->getBody(), true);
        $_SESSION['eos_actor']      = $identity_results["account_name"];
        $Util->eos_actor            = $_SESSION['eos_actor'];
        $eos_balance                   = $Util->getEOSBalance();
        $_SESSION['eos_balance']      = $eos_balance;
        $_SESSION['eos_public_key']   = $Util->getEOSPublicKey();
        if (isset($_SESSION['fio_actor'])) {
            unset($_SESSION['fio_actor']);
        }
        if (isset($_SESSION['fio_balance'])) {
            unset($_SESSION['fio_balance']);
        }
        if (isset($_SESSION['fio_public_key'])) {
            unset($_SESSION['fio_public_key']);
        }
    } catch (Exception $e) {
        $notice .= '<div style="color: red;">' . $e->getMessage() . '<br />Pleae login again.</div>';
    }
}

if ($action == "finish_fio_login") {
    $proof = json_decode($_REQUEST["identity_proof"], true);
    try {
        $identity_response = $fio_client->post('https://eosio.greymass.com/prove', [
            GuzzleHttp\RequestOptions::JSON => ['proof' => $proof], // or 'json' => [...]
        ]);
        $identity_results          = json_decode($identity_response->getBody(), true);
        // check to ensure the logged in FIO user matches the keys for the EOS user
        $key = substr($_SESSION['eos_public_key'], 3);
        $Util->fio_actor = $identity_results["account_name"];
        $fio_public_key = $Util->getFIOPublicKey();
        if (substr($_SESSION['eos_public_key'], 3) == substr($fio_public_key, 3)) {
            $_SESSION['fio_actor']      = $Util->fio_actor;
            $fio_balance               = $Util->getFIOBalance();
            $_SESSION['fio_balance']   = $fio_balance;
            $_SESSION['fio_public_key']   = $fio_public_key;
        } else {
            $notice .= '<div style="color: red;">Please login with the FIO Account for FIO' . $key . '</div>';
        }
    } catch (Exception $e) {
        $notice .= '<div style="color: red;">' . $e->getMessage() . '<br />Pleae login again.</div>';
    }
}


