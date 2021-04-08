<?php
include "objects.php";

$Util = new Util($eos_client,$fio_client);

$title            = "FIO 2 EOS";
$description      = "FIO Address Giveaway for EOS Token Holders";
$image            = "";
$url              = "";
$onboarding_pitch = 'Import your private key into <a href="https://greymass.com/anchor/" target="_blank">Anchor Wallet by Greymass</a> to login.';

$Factory  = new Factory();
$giveaway_domain   = "edenos";
$action   = "";
$notice   = "";

if (isset($_REQUEST["action"])) {
    $action = strip_tags($_REQUEST["action"]);
}

// Begin the PHP session so we have a place to store the username
session_start();

include "actions.php";

