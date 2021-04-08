<?php

date_default_timezone_set('America/Puerto_Rico');

$fio_chain_id      = '21dcae42c0182200e93f954a074011f9048a7624c6fe81d3c9541a614a88bd1c';
$fio_node_url      = 'https://fio.greymass.com';
$fio_explorer_url = "https://fio.bloks.io/";
$eos_chain_id      = 'aca376f206b8fc25a6ed44dbdc66547c36c6c33e3a119ffbeaef943642f0e906';
$eos_node_url      = 'https://eos.greymass.com/';
$eos_explorer_url = "https://bloks.io/";
$use_testnet  = true;
if ($use_testnet) {
    $fio_chain_id = 'b20901380af44ef59c5918439a1f9a41d83669020319a80574b804a5f95cbd7e';
    //$fio_node_url = 'https://testnet.fioprotocol.io';
    $fio_node_url      = 'https://testnet.fio.eosdetroit.io';
    $fio_explorer_url = "https://fio-test.bloks.io/";
    $eos_chain_id      = '2a02a0053e5a8cf73a56ba0fda11e4d92e0238a4a2aa74fccf46d5a910746840';
    $eos_node_url      = 'https://jungle3.cryptolions.io/';
    $eos_explorer_url = "https://jungle3.bloks.io/";
}
$fio_client = new GuzzleHttp\Client(['base_uri' => $fio_node_url]);
$eos_client = new GuzzleHttp\Client(['base_uri' => $eos_node_url]);

function br()
{return (PHP_SAPI === 'cli' ? "\n" : "<br />");}

class Util
{
    public $eos_client;
    public $eos_public_key;
    public $eos_actor;
    public $eos_balance;
    public $fio_client;
    public $fio_public_key;
    public $fio_actor;
    public $fio_balance;

    public function __construct($eos_client,$fio_client)
    {
        $this->eos_client = $eos_client;
        $this->fio_client = $fio_client;
    }

    public function chainGet($client_type, $endpoint, $params)
    {
        $client = $this->fio_client;
        if ($client_type == "eos") {
            $client = $this->eos_client;
        }
        $results = [];
        try {
            $response = $client->post('/v1/chain/' . $endpoint, [
                GuzzleHttp\RequestOptions::JSON => $params,
            ]);
            $results = json_decode($response->getBody(), true);
        } catch (Exception $e) {}
        return $results;
    }

    public function getPublicKey($chain)
    {
        $public_key = $chain . "_public_key";
        if ($this->$public_key) {
            return $this->$public_key;
        }
        $actor = $chain . "_actor";
        $params = array(
            "account_name" => $this->$actor,
        );
        try {
            $response = $this->chainGet($chain, 'get_account', $params);
            //var_dump($response);
            foreach ($response['permissions'] as $key => $permission) {
                //var_dump($permission);
                if ($permission['perm_name'] == "active") {
                    if (isset($permission['required_auth']['keys'][0])) {
                        $this->$public_key = $permission['required_auth']['keys'][0]['key'];
                    }
                }
            }
        } catch (\Exception $e) {
            //print $e->getMessage() . "\n";
        }
        return $this->$public_key;
    }

    public function getFIOPublicKey()
    {
        return $this->getPublicKey("fio");
    }

    public function getEOSPublicKey()
    {
        return $this->getPublicKey("eos");
    }

    public function getFIOBalance()
    {
        if ($this->fio_balance) {
            return $this->fio_balance;
        }
        $this->getFIOPublicKey();
        $params              = ["fio_public_key" => $this->fio_public_key];
        $fio_balance_results = $this->chainGet('fio', 'get_fio_balance', $params);
        $balance             = $fio_balance_results['balance'];
        $balance             = $balance / 1000000000;
        $this->fio_balance       = $balance;
        return $this->fio_balance;
    }

    public function getEOSBalance()
    {
        if ($this->eos_balance) {
            return $this->eos_balance;
        }
        $params              = ["code" => "eosio.token", "account" => $this->eos_actor, "symbol" => "EOS"];
        $eos_balance_results = $this->chainGet('eos', 'get_currency_balance', $params);
        $balance             = $eos_balance_results[0];
        $details = explode(" ",$balance);
        $balance             = $details[0];
        $this->eos_balance       = $balance;
        return $this->eos_balance;
    }
}

class Factory
{
    public $dataDir;

    public function __construct($dataDir = null)
    {
        $this->dataDir = __DIR__ . "/../data";
        if ($dataDir) {
            $this->dataDir = $dataDir;
        }
    }

    function new ($object_type) {
        return new $object_type($object_type, $this);
    }
}

class BaseObject
{
    public $_id;

    public $internal_fields      = array('internal_fields', 'non_printable_fields', 'sort_field', 'dataDir', 'dataStore', 'factory');
    public $non_printable_fields = array();
    public $sort_field           = "_id";
    public $dataDir;
    public $dataStore;
    public $factory;

    public function __construct($object_type, $factory)
    {
        $this->dataDir              = $factory->dataDir;
        $this->dataStore            = new \SleekDB\Store($object_type, $this->dataDir);
        $this->factory              = $factory;
        $this->non_printable_fields = array_merge($this->internal_fields, $this->non_printable_fields);
    }

    public function getData()
    {
        $object_data = get_object_vars($this);
        foreach ($this->internal_fields as $internal_field) {
            unset($object_data[$internal_field]);
        }
        return $object_data;
    }

    public function getPrintableFields()
    {
        $object_data = get_object_vars($this);
        foreach ($this->non_printable_fields as $non_printable_field) {
            unset($object_data[$non_printable_field]);
        }
        return $object_data;
    }

    public function save()
    {
        $object_data = $this->getData();
        if ($object_data["_id"]) {
            $this->dataStore->update($object_data);
        } else {
            unset($object_data["_id"]);
            $new_object_data = $this->dataStore->insert($object_data);
            $this->_id       = $new_object_data["_id"];
        }
    }

    public function delete()
    {
        if ($this->_id) {
            $this->dataStore->deleteById($this->_id);
        }
    }

    public function loadData($data)
    {
        $object_data = $this->getData();
        foreach ($object_data as $key => $value) {
            if (array_key_exists($key, $data)) {
                $this->$key = $data[$key];
            }
        }
    }

    public function read($criteria = null)
    {
        $object_data = null;
        if ($this->_id) {
            $object_data = $this->dataStore->findById($this->_id);
        } elseif ($criteria) {
            $object_data = $this->dataStore->findOneBy($criteria);
        }
        if (!is_null($object_data)) {
            $this->loadData($object_data);
        }
        return !is_null($object_data);
    }

    public function readAll($criteria)
    {
        $objects      = array();
        $queryBuilder = $this->dataStore->createQueryBuilder();
        $objects_data = $queryBuilder
            ->where($criteria)
            ->orderBy([$this->sort_field => "asc"])
            ->getQuery()
            ->fetch();
        foreach ($objects_data as $object_data) {
            $Object = $this->factory->new(get_class($this));
            $Object->loadData($object_data);
            $objects[] = $Object;
        }
        return $objects;
    }

    function print($format = "", $controls = array()) {
        $object_data           = $this->getData();
        $printable_object_data = $this->getPrintableFields();
        $display_keys          = array();
        foreach ($object_data as $key => $value) {
            $display_keys[] = '$' . $key;
        }
        $values_to_display = array();
        foreach ($printable_object_data as $key => $value) {
            $value_to_display = $value;
            if (substr($key, 0, 3) == "is_") {
                if ($value) {
                    $value_to_display = "true";
                } else {
                    $value_to_display = "false";
                }
            } elseif (strpos($key, "date") !== false) {
                if ($value) {
                    $value_to_display = date("Y-m-d H:i:s", $value);
                } else {
                    $value_to_display = "";
                }
            }
            $values_to_display[$key] = $value_to_display;
            $object_data[$key]       = $value_to_display;
        }
        $updated_values_to_display = array();
        foreach ($values_to_display as $key => $value_to_display) {
            $updated_value_to_display = $value_to_display;
            if (array_key_exists($key, $controls)) {
                $updated_value_to_display = str_replace(
                    $display_keys,
                    $object_data,
                    $controls[$key]);
            }
            $updated_values_to_display[$key] = $updated_value_to_display;
        }
        $values_to_display = $updated_values_to_display;
        if ($format == "") {
            foreach ($values_to_display as $key => $value_to_display) {
                print ucwords(str_replace("_", " ", $key)) . ": " . $value_to_display . br();
            }
        }
        if ($format == "table") {
            print "<tr>\n";
            foreach ($values_to_display as $key => $value_to_display) {
                print "<td>";
                print $value_to_display;
                print "</td>\n";
            }
            print "</tr>\n";
        }
        if ($format == "table_header") {
            print "<tr>\n";
            foreach ($printable_object_data as $key => $value) {
                print "<th>";
                print ucwords(str_replace("_", " ", $key));
                print "</th>\n";
            }
            print "</tr>\n";
        }
    }
}

class FIOAddressRequest extends BaseObject
{
    public $eos_actor;
    public $eos_public_key;
    public $eos_balance;
    public $fio_actor;
    public $date_requested;
    public $fio_address_requested;
    public $ip_address;
    public $note;
    public $status = "Pending";
    public $transaction_id;
    public $is_issued = false;
}