<?php
// faucet.php

require_once 'keccak256.php'; // your pure PHP Keccak implementation
require_once 'ecdsa.php';     // the ECDSA signer we built

// CONFIG
$rpc = "https://rpc.sepolia.org"; // or your local Ganache node
$private_key = "YOUR_PRIVATE_KEY"; // 64 hex chars
$faucet_address = "0xYOUR_FAUCET_ADDRESS";

// Handle faucet POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['to'])) {
    $to = strtolower(trim($_POST['to']));
    if (!preg_match('/^0x[a-f0-9]{40}$/', $to)) die("Invalid address");

    $nonce = eth_rpc("eth_getTransactionCount", [$faucet_address, "pending"]);
    $nonce = hexdec($nonce);

    $gas_price = eth_rpc("eth_gasPrice", []);
    $gas_price = hexdec($gas_price);

    $gas_limit = 21000;
    $value_wei = bcmul("0.01", bcpow("10", "18")); // 0.01 ETH

$tokenContract = "0xYourTokenContractAddress"; // 🔁 Your token contract
//$tokenContract = $to;
$recipient = $to;
$amount = bcmul("100", bcpow("10", "18")); // 100 tokens (depends on decimals)

$method_id = substr(keccak256("transfer(address,uint256)"), 0, 8); // 4-byte method ID
$recipient_padded = str_pad(substr($recipient, 2), 64, '0', STR_PAD_LEFT);
$amount_padded = str_pad(dechex($amount), 64, '0', STR_PAD_LEFT);
$data = "0x" . $method_id . $recipient_padded . $amount_padded;
if (0){ // set to 1 after setting tokenContract with your token
$tx = [
    'nonce'    => dec2hex($nonce),
    'gasPrice' => dec2hex($gas_price),
    'gasLimit' => dec2hex(60000), // token transfer is more expensive than ETH
    'to'       => $tokenContract,
    'value'    => '0', // ERC-20 doesn't need ETH value
    'data'     => substr($data, 2), // no 0x prefix inside RLP
    'chainId'  => 11155111
];
}else{
    // RLP-encode tx fields
    $tx = [
        'nonce'    => dec2hex($nonce),
        'gasPrice' => dec2hex($gas_price),
        'gasLimit' => dec2hex($gas_limit),
        'to'       => $to,
        'value'    => dec2hex($value_wei),
        'data'     => '',
        'chainId'  => 11155111 // Sepolia
    ];
}

    $raw_tx = rlp_encode_tx($tx, $private_key);
    $tx_hash = eth_rpc("eth_sendRawTransaction", [$raw_tx]);

    echo json_encode(['txHash' => $tx_hash]);
    exit;
}

// Helpers
function dec2hex($num) {
    $hex = dechex($num);
    return (strlen($hex) % 2 ? '0' : '') . $hex;
}

function hex_strip_0x($x) {
    return (strpos($x, '0x') === 0) ? substr($x, 2) : $x;
}

function rlp_encode_tx($tx, $priv_key) {
    $items = [
        hex_strip_0x($tx['nonce']),
        hex_strip_0x($tx['gasPrice']),
        hex_strip_0x($tx['gasLimit']),
        hex_strip_0x($tx['to']),
        hex_strip_0x($tx['value']),
        '',
        dec2hex($tx['chainId']),
        '',
        ''
    ];

    $rlp_unsigned = rlp_encode($items);
    $hash = keccak256(hex2bin($rlp_unsigned));

    $sig = sign(bin2hex($hash), $priv_key);

    $v = dechex($sig['v'] + $tx['chainId'] * 2 + 8);
    $r = ltrim($sig['r'], '0');
    $s = ltrim($sig['s'], '0');

    $signed_items = [
        hex_strip_0x($tx['nonce']),
        hex_strip_0x($tx['gasPrice']),
        hex_strip_0x($tx['gasLimit']),
        hex_strip_0x($tx['to']),
        hex_strip_0x($tx['value']),
        '',
        $v,
        $r,
        $s
    ];

    return '0x' . rlp_encode($signed_items);
}

// Minimal RLP encoder
function rlp_encode($input) {
    if (is_array($input)) {
        $output = '';
        foreach ($input as $item) {
            $output .= rlp_encode($item);
        }
        return encode_length(strlen($output), 0xc0) . $output;
    } else {
        $input = hex2bin(strlen($input) % 2 ? '0' . $input : $input);
        $len = strlen($input);
        if ($len === 1 && ord($input) < 0x80) return $input;
        return encode_length($len, 0x80) . $input;
    }
}

function encode_length($len, $offset) {
    if ($len < 56) {
        return chr($len + $offset);
    }
    $bl = ltrim(gmp_strval(gmp_init($len), 16), '0');
    $l = strlen($bl) / 2;
    return chr($offset + 55 + $l) . hex2bin($bl);
}

function eth_rpc($method, $params) {
    global $rpc;
    $payload = json_encode([
        "jsonrpc" => "2.0",
        "method" => $method,
        "params" => $params,
        "id" => 1
    ]);
    $opts = [
        'http' => [
            'method' => "POST",
            'header' => "Content-type: application/json",
            'content' => $payload
        ]
    ];
    $ctx = stream_context_create($opts);
    $res = file_get_contents($rpc, false, $ctx);
    $json = json_decode($res, true);
    return $json['result'] ?? null;
}
?>