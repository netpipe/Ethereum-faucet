<?php
// Show HTML GUI on GET
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>ETH Sender</title>
  <style>
    body { font-family: sans-serif; max-width: 500px; margin: 2em auto; }
    input, button { width: 100%; padding: 12px; margin: 8px 0; font-size: 16px; }
    #status { margin-top: 1em; }
  </style>
</head>
<body>
  <h2>Ethereum Sender (Private Key)</h2>
  <form method="post">
    <input type="text" name="to" placeholder="Recipient address (0x...)" required>
    <input type="number" step="0.00001" name="amount" placeholder="Amount (ETH)" required>
    <button type="submit">🚀 Send ETH</button>
  </form>
  <?php if (isset($_GET['tx'])): ?>
    <div id="status">
      ✅ Sent! <a href="https://sepolia.etherscan.io/tx/<?php echo $_GET['tx']; ?>" target="_blank">View on Etherscan</a>
    </div>
  <?php endif; ?>
</body>
</html>
<?php
exit;
}

// 👇 Handle POST below
require_once 'ecdsa.php';
require_once 'keccak256.php';

$rpcUrl = "https://rpc.sepolia.org"; // Or localhost
$privateKey = "YOUR_PRIVATE_KEY";    // No 0x
$faucetAddress = "0xYOUR_ADDRESS";   // Matches key

$to = strtolower(trim($_POST['to']));
$amount = $_POST['amount'];

if (!preg_match('/^0x[a-fA-F0-9]{40}$/', $to)) exit("❌ Invalid address");
if (!is_numeric($amount) || $amount <= 0) exit("❌ Invalid amount");

// 1. Get nonce & gas price
$nonceHex = rpc("eth_getTransactionCount", [$faucetAddress, "pending"]);
$nonce = hexdec($nonceHex);
$gasPrice = hexdec(rpc("eth_gasPrice", []));
$gasLimit = 21000;
$valueWei = bcmul($amount, bcpow("10", "18")); // ETH → wei

// 2. Build unsigned TX
$tx = [
    'nonce' => $nonce,
    'gasPrice' => $gasPrice,
    'gasLimit' => $gasLimit,
    'to' => $to,
    'value' => $valueWei,
    'data' => '',
    'chainId' => 11155111
];

// 3. Sign
$rawTx = signEthTx($tx, $privateKey);

// 4. Send
$txHash = rpc("eth_sendRawTransaction", [$rawTx]);

// 5. Redirect with TX hash
header("Location: ?tx=$txHash");
exit;


// 🔐 TX Signing
function signEthTx($tx, $privKey) {
    $txRlp = [
        dec2hex($tx['nonce']),
        dec2hex($tx['gasPrice']),
        dec2hex($tx['gasLimit']),
        substr($tx['to'], 2),
        dec2hex($tx['value']),
        '',
        dec2hex($tx['chainId']),
        '',
        ''
    ];

    $rlp = rlp_encode($txRlp);
    $hash = keccak256(hex2bin($rlp));

    $sig = sign(bin2hex($hash), $privKey);
    $v = dechex($sig['v'] + $tx['chainId'] * 2 + 8);
    $r = ltrim($sig['r'], '0');
    $s = ltrim($sig['s'], '0');

    $signedTx = [
        dec2hex($tx['nonce']),
        dec2hex($tx['gasPrice']),
        dec2hex($tx['gasLimit']),
        substr($tx['to'], 2),
        dec2hex($tx['value']),
        '',
        $v,
        $r,
        $s
    ];

    return "0x" . rlp_encode($signedTx);
}

function dec2hex($val) {
    $hex = dechex($val);
    return strlen($hex) % 2 ? "0$hex" : $hex;
}

function rpc($method, $params) {
    global $rpcUrl;
    $req = json_encode([
        'jsonrpc' => '2.0',
        'method' => $method,
        'params' => $params,
        'id' => 1
    ]);
    $opts = ['http' => ['method' => 'POST', 'header' => 'Content-Type: application/json', 'content' => $req]];
    $res = file_get_contents($rpcUrl, false, stream_context_create($opts));
    $json = json_decode($res, true);
    return $json['result'] ?? die("RPC error: $res");
}

// 🔧 RLP encoder (same as before)
function rlp_encode($input) {
    if (is_array($input)) {
        $output = '';
        foreach ($input as $item) $output .= rlp_encode($item);
        return encode_length(strlen($output), 0xc0) . $output;
    } else {
        $bin = hex2bin(strlen($input) % 2 ? "0$input" : $input);
        return (strlen($bin) === 1 && ord($bin) < 0x80) ? $bin : encode_length(strlen($bin), 0x80) . $bin;
    }
}

function encode_length($len, $offset) {
    return $len < 56
        ? chr($len + $offset)
        : chr($offset + 55 + strlen(dechex($len)) / 2) . hex2bin(dechex($len));
}
?>
