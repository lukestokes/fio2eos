<?php
require_once __DIR__ . '/../vendor/autoload.php';
include "header.php";
?>
<!DOCTYPE html>
<html lang="en">

<head>

  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <meta name="description" content="<?php print $description;?>">
  <meta name="author" content="Luke Stokes">
  <link rel="icon" href="favicon.ico" type="image/x-icon" />

  <!-- Facebook -->
  <!--<meta property="og:url"           content="<?php print $url;?>" />-->
  <meta property="og:type"          content="website" />
  <meta property="og:title"         content="<?php print $title;?>" />
  <meta property="og:description"   content="<?php print $description;?>" />
  <!--<meta property="og:image"         content="<?php print $image;?>" />-->

  <!-- Twitter -->
  <meta name="twitter:creator" content="@lukestokes">
  <meta name="twitter:title" content="<?php print $title;?>">
  <meta name="twitter:description" content="<?php print $description;?>">
  <!--<meta name="twitter:image" content="<?php print $image;?>">-->

  <title><?php print $title;?></title>
</head>

<body onload="restoreSession()">
<?php
if ($use_testnet) {
    print "<h3 style='color:red; margin-left: 10px; padding-top: 5px;'>TESTNET</h3>";
}
?>
<h1><?php print $description; ?></h1>
<p>
  Would you like a single human-readable address that works with for all cryptocurrency tokens and blockchains? If so, you've come to the right place!
</p>
<p>
  As an EOS token holder and supporter of EOSIO technology, the Foundation for Interwallet Operability (FIO) community would like to give you your own self-soverign FIO Address NFT.
</p>
<h3>Requirements:</h3>
<ol>
  <li>
    Have an EOS Mainnet account which has had at least 5 EOS over the past 7 days.
  </li>
  <li>
    Your account's active permission should be a key and unchanged for at least 7 days.
  </li>
  <li>
    Download and install <a href="https://greymass.com/en/anchor/" target="_blank">Anchor Wallet</a> by Greymass.
  </li>
  <li>
    Import your EOS active key into Anchor.
  </li>
</ol>

<h3>Steps:</h3>
<?php
if ($notice != "") {
  print $notice;
}
?>
<ol>
  <li>
    <?php
      if (isset($_SESSION["eos_actor"])) {
        print "<span style='color: green;'>Done:</span> " . $_SESSION["eos_actor"] . " <strike>";
      }
    ?>
    <a href="#" onclick="login('eos'); return false;">Login to EOS</a>.
    <?php
      if (isset($_SESSION["eos_actor"])) {
        print "</strike>";
      }
    ?>
  </li>
  <li>
    <?php
      if (isset($_SESSION["eos_balance"]) && $_SESSION["eos_balance"] > 5) {
        print "<span style='color: green;'>Done:</span> " . $_SESSION["eos_balance"] . " <strike>";
      }
    ?>
    Check EOS Balance > 5.
    <?php
      if (isset($_SESSION["eos_balance"]) && $_SESSION["eos_balance"] > 5) {
        print "</strike>";
      }
    ?>
  </li>
  <li>
    <?php
      if (isset($_SESSION["eos_public_key"])) {
        print "<span style='color: green;'>Done:</span> " . $_SESSION["eos_public_key"] . " <strike>";
      }
    ?>
    Get your EOS Public Key.
    <?php
      if (isset($_SESSION["eos_public_key"])) {
        print "</strike>";
      }
    ?>
  </li>
  <li>
    <?php
    $completed = false;
    $ready_to_request = true;
    $already_exists = false;
    if (isset($_SESSION["fio_actor"]) && isset($_SESSION["fio_address"])) {
      $completed = true;
      $ready_to_request = false;
      $already_exists = true;
    }
    if (!isset($_SESSION["eos_actor"])) {
      $ready_to_request = false;
    } else {
      $FIOAddressRequest = $Factory->new("FIOAddressRequest");
      $already_exists = $FIOAddressRequest->read(['eos_actor', "=", $_SESSION["eos_actor"]]);      
    }
    if ($completed) {
      print "<span style='color: green;'>Done:</span> " . $_SESSION["fio_address"] . " <strike>";
    } else {
      if ($already_exists) {
        print "<span style='color: green;'>Done:</span> Requested " . $FIOAddressRequest->fio_address_requested . " <strike>";
        $ready_to_request = false;
      }
    }
    if ($ready_to_request) {
      print '<a href="#" onclick="requestFIOAddress(); return false;">';
    }
    ?>
    Request a FIO Name
    <?php
    if ($ready_to_request) {
      print '</a>: <input type="text" id="fio_name_requested" name="fio_name_requested" value="" />@' . $giveaway_domain . '.';
    }
    if ($completed || (!$completed && $already_exists)) {
      print "</strike>";
    }
    ?>
  </li>
  <li>
    <?php
    // TODO: add email option to be notified
    // " Add an email address to be notified if your request is approved."
    if ($already_exists && $FIOAddressRequest->is_issued) {
      print "<span style='color: green;'>Done</span>: Login with " . $FIOAddressRequest->fio_actor . " <strike>";
    }
    ?>
    Wait for your FIO address to be issued to your new FIO Account.
    <?php
    if ($already_exists) {
      print "</strike>";
    }
    ?>
  </li>
  <li>
    <?php
      if (isset($_SESSION["fio_actor"])) {
        print "<span style='color: green;'>Done:</span> " . $_SESSION["fio_actor"] . " <strike>";
      } else {
        if ($already_exists && $FIOAddressRequest->is_issued) {
          print '<a href="#" onclick="login(\'fio\'); return false;">';
        }
      }
    ?>
    Login to FIO
    <?php
      if (!isset($_SESSION["fio_actor"]) && $already_exists && $FIOAddressRequest->is_issued) {
        print "</a>";
      }
    ?>
    after scanning for new accounts within Anchor or reimport your EOS Private key to access your FIO Account 
    <?php
      if (isset($_SESSION["fio_actor"])) {
        print "</strike>";
      }
    ?>
  </li>
  <li>
    Map your EOS account to your FIO Address.
  </li>
  <li>
    Map additional cryptocurrency addresses to your FIO Address.
  </li>
  <li>
    Optional: Import your FIO private key into a FIO enabled wallet like Edge Wallet for full FIO features such as FIO Send, FIO Requests, and FIO Data.
  </li>
</ol>

<form id="eos_login" method="POST">
  <input id="eos_identity_proof" name="identity_proof" value="" type="hidden">
  <input id="eos_actor" name="actor" value="" type="hidden">
  <input id="action" name="action" value="finish_eos_login" type="hidden">
</form>

<form id="fio_login" method="POST">
  <input id="fio_identity_proof" name="identity_proof" value="" type="hidden">
  <input id="fio_actor" name="actor" value="" type="hidden">
  <input id="action" name="action" value="finish_fio_login" type="hidden">
</form>

<form id="fio_address_request" method="POST">
  <input id="fio_address_requested" name="fio_address_requested" value="" type="hidden">
  <input id="action" name="action" value="request_fio_address" type="hidden">
</form>

<p>
<?php
      if (isset($_SESSION["eos_actor"])) {
          print $_SESSION["eos_actor"] . "<br />";
      }
      if (isset($_SESSION["eos_balance"])) {
          print "Balance: " . $_SESSION["eos_balance"] . " EOS<br />";
      }
      if (isset($_SESSION["fio_actor"])) {
          print $_SESSION["fio_actor"] . "<br />";
      }
      if (isset($_SESSION["fio_balance"])) {
          print "Balance: " . $_SESSION["fio_balance"] . " FIO<br />";
      }
?>
</p>

<p>
  <a href="/?action=logout">Logout</a>
</p>

  <script>
  var giveaway_domain = '<?php print $giveaway_domain;?>';
  var fio_node_url = '<?php print $fio_node_url;?>';
  var fio_explorer_url = '<?php print $fio_explorer_url;?>';
  var eos_node_url = '<?php print $eos_node_url;?>';
  var eos_explorer_url = '<?php print $eos_explorer_url;?>';
  </script>
  <script src="https://unpkg.com/anchor-link@3"></script>
  <script src="https://unpkg.com/anchor-link-browser-transport@3"></script>
  <script src="js/script.js"></script>
  <script>
  // app identifier, should be set to the eosio contract account if applicable
  const identifier = 'eosio2fio'
  // initialize the browser transport
  const transport = new AnchorLinkBrowserTransport()
  // initialize the link
  const eos_link = new AnchorLink(
      {
        transport,
        chains: [
            {
                chainId: '<?php print $eos_chain_id;?>',
                nodeUrl: '<?php print $eos_node_url;?>',
            }
        ],
      }
    );
  const fio_link = new AnchorLink(
      {
        transport,
        chains: [
            {
                chainId: '<?php print $fio_chain_id;?>',
                nodeUrl: '<?php print $fio_node_url;?>',
            }
        ],
      }
    );
  // the session instance, either restored using link.restoreSession() or created with link.login()
  let eos_session
  let fio_session
  </script>
</body>
</html>