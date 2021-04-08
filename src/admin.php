<?php
require_once __DIR__ . '/../vendor/autoload.php';
include "header.php";

if (php_sapi_name() != "cli") {
    die("Access Denied");
}

$clio_path = "/home/fio/ubuntu_18/";
$clio_path = "/Users/lukestokes/Documents/workspace/FIO/chain_files/fio.ready-master/";
$clio = "clio --url $fio_node_url ";
$faucet_actor = "cysfakd2lzin";

print "Welcome to the FIO 2 EOS Admin\n\n";

$show = null;
if (isset($argv[1])) {
    if ($argv[1] == "issued") {
        $show = "issued";
    }
    if ($argv[1] == "all") {
        $show = "all";
    }
}

function printRequests($FIOAddressRequests) {
    $request_count = 0;
    foreach ($FIOAddressRequests as $FIOAddressRequest) {
      $request_count++;
      $FIOAddressRequest->print();
    }
    print "\nRequests: " . $request_count . "\n";  
}
function selectRequests($criteria) {
  $Factory = new Factory();
  $FIOAddressRequest = $Factory->new("FIOAddressRequest");
  $FIOAddressRequests = $FIOAddressRequest->readAll($criteria);
  return $FIOAddressRequests;
}

function showPending() {
    print "Pending Requests:" . br();
    $FIOAddressRequests = selectRequests([["is_issued","=",false],["status","=","Pending"]]);
    $oldest = -1;
    foreach ($FIOAddressRequests as $FIOAddressRequest) {
        if ($oldest == -1) {
            $oldest = $FIOAddressRequest->_id;
        }
        $oldest = min($oldest,$FIOAddressRequest->_id);
        print $FIOAddressRequest->_id . ": ";
        print $FIOAddressRequest->fio_address_requested;
        print br();
    }
    return $oldest;
}
function showRejected() {
    $FIOAddressRequests = selectRequests([["is_issued","=",false],["status","=","Rejected"]]);
    printRequests($FIOAddressRequests);
}
function showAll() {
    print "All Requests:" . br();
    $FIOAddressRequests = selectRequests(["fio_address_requested","!=",""]);
    printRequests($FIOAddressRequests);
}
function showIssued() {
    print "Issed Addresses:" . br();
    $FIOAddressRequests = selectRequests(["is_issued","=",true]);
    printRequests($FIOAddressRequests);
}

function rejectAllPending($time) {
    $time = $time - 60;
    print "Rejecting all pending requests with the following note (press enter to skip): ";
    $input = rtrim(fgets(STDIN));
    if ($input != "") {
        $FIOAddressRequests = selectRequests([["is_issued","=",false],["status","=","Pending"]]);
        foreach ($FIOAddressRequests as $FIOAddressRequest) {
            if ($FIOAddressRequest->date_requested < $time) {
                $FIOAddressRequest->status = "Rejected";
                $FIOAddressRequest->note = $input;
                $FIOAddressRequest->save();
                print $FIOAddressRequest->_id . ": ";
                $FIOAddressRequest->print();
            }
        }
    }
}

function approveAllPending($time) {
    $time = $time - 60;
    print "Are you sure you want to approve all pending requests (y/n)? ";
    $input = rtrim(fgets(STDIN));
    if ($input == "y") {
        $FIOAddressRequests = selectRequests([["is_issued","=",false],["status","=","Pending"]]);
        foreach ($FIOAddressRequests as $FIOAddressRequest) {
            if ($FIOAddressRequest->date_requested < $time) {
                approveRequest($FIOAddressRequest, false);
            }
        }
    }
}

function selectRequest($oldest) {
    print br();
    print "Which request Would you like to Process?\n";
    print " Type enter for oldest\n Type 'cancel' to cancel all\n Type 'approve' to pay all: ";
    $input = rtrim(fgets(STDIN));
    if ($input == "cancel") {
        rejectAllPending(time());
        return;
    }
    if ($input == "approve") {
        approveAllPending(time());
        return;
    }
    $fetch_id = $oldest;
    if (is_numeric($input)) {
        $fetch_id = $input;
    }
    $Factory = new Factory();
    $FIOAddressRequest = $Factory->new("FIOAddressRequest");
    $FIOAddressRequest->_id = $fetch_id;
    $found = $FIOAddressRequest->read();
    if ($found) {
        return $FIOAddressRequest;
    }
    $oldest = showPending();
    if ($oldest != -1) {
        selectRequest($oldest);
    }
}

function my_exec($cmd, $input='') {
    $proc=proc_open($cmd, array(0=>array('pipe', 'r'), 1=>array('pipe', 'w'), 2=>array('pipe', 'w')), $pipes);
    fwrite($pipes[0], $input);fclose($pipes[0]);
    $stdout=stream_get_contents($pipes[1]);fclose($pipes[1]);
    $stderr=stream_get_contents($pipes[2]);fclose($pipes[2]);
    $rtn=proc_close($proc);
    return array('stdout'=>$stdout,
               'stderr'=>$stderr,
               'return'=>$rtn
              );
}

function approveRequest($FIOAddressRequest, $prompt = true) {
    global $clio_path;
    global $clio;
    global $faucet_actor;
    global $fio_explorer_url;
    print br();
    if (is_null($FIOAddressRequest)) {
        return;
    }
    $FIOAddressRequest->print();
    $new_fio_public_key = "FIO" . substr($FIOAddressRequest->eos_public_key,3);
    $data = '{
      "fio_address": "' . $FIOAddressRequest->fio_address_requested . '",
      "owner_fio_public_key": "' . $new_fio_public_key . '",
      "max_fee": 800000000000,
      "tpid": "luke@stokes",
      "actor": "' . $faucet_actor . '"
    }';
    $cmd = "push action fio.address regaddress '" . $data . "' -p " . $faucet_actor . "@active";
    print $clio_path . $clio . $cmd . br();
    if ($prompt) {
        print "Process Request (y/n)? ";
        $input = rtrim(fgets(STDIN));
    } else {
        $input = "y";
    }
    if ($input == "y") {
        $results = my_exec($clio_path . $clio . $cmd);
        if ($results["return"] == 1) {
            $str = strtok($results["stderr"], "\n");
            $parts = preg_split('/\s+/', $str);
            if (isset($parts[2]) && $parts[2] == "Locked") {
                print "Please unlock your wallet:" . br();
                print "./wallet.sh" . br();
                die();
            } else {
                print "=================== ERROR ===================" . br();
                print $results["stderr"] . br();
            }
        } else {
            $str = strtok($results["stderr"], "\n");
            $parts = preg_split('/\s+/', $str);
            if ($parts[0] == "executed") {
                $FIOAddressRequest->transaction_id = $parts[2];
                $FIOAddressRequest->status = "Issued";
                $FIOAddressRequest->is_issued = true;
                $FIOAddressRequest->save();
                print "Success! " . $fio_explorer_url . "transaction/" . $parts[2] . br();
                $results = my_exec($clio_path . $clio . "convert fiokey_to_account " . $new_fio_public_key);
                if ($results["return"] == 1) {
                  print "Had trouble getting fio actor:" . br();
                  print $results["stderr"] . br();
                } else {
                  $FIOAddressRequest->fio_actor = trim($results["stdout"]);
                  $FIOAddressRequest->save();
                }
            } else {
                var_dump($parts);
            }
        }
    }
    if ($input == "n") {
        print "To reject this request, enter a note (press enter to skip): ";
        $input = rtrim(fgets(STDIN));
        if ($input != "") {
            $FIOAddressRequest->status = "Rejected";
            $FIOAddressRequest->note = $input;
            $FIOAddressRequest->save();
        }
    }
}


if (is_null($show)) {
    $selected = showPending();
    while($selected != -1) {
        $FIOAddressRequest = selectRequest($selected);
        approveRequest($FIOAddressRequest);
        $selected = showPending();
    }
} else {
    if ($show == "issued") {
        showIssued();
    }
    if ($show == "all") {
        showAll();
    }
    if ($show == "rejected") {
        showRejected();
    }
}
