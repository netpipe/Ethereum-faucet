<?php

session_start();

/**
 *  web3p library & utils
 * 
 * */

require('./base.php');
require('./utils.php');

use Web3\Utils;
use Web3\Contract;
use Web3p\EthereumTx\Transaction;
use Web3p\EthereumTx\EIP1559Transaction;

/** 
 * 
 *  Login & encrypt and decrypt message
 *  library
 * 
 * */

require_once "./lib/Keccak/Keccak.php";
require_once "./lib/Elliptic/EC.php";
require_once "./lib/Elliptic/Curves.php";
require_once "./lib/JWT/jwt_helper.php";
$GLOBALS['JWT_secret'] = '4Eac8AS2cw84easd65araADX';

use Elliptic\EC;
use kornrunner\Keccak;

// Check if the message was signed with the same private key to which the public address belongs
function pubKeyToAddress($pubkey) {
    return "0x" . substr(Keccak::hash(substr(hex2bin($pubkey->encode("hex")), 1), 256), 24);
}

function verifySignature($message, $signature, $address) {
    $msglen = strlen($message);
    $hash   = Keccak::hash("\x19Ethereum Signed Message:\n{$msglen}{$message}", 256);
    $sign   = ["r" => substr($signature, 2, 64),
               "s" => substr($signature, 66, 64)];
    $recid  = ord(hex2bin(substr($signature, 130, 2))) - 27;
    if ($recid != ($recid & 1))
        return false;

    $ec = new EC('secp256k1');
    $pubkey = $ec->recoverPubKey($hash, $sign, $recid);

    return $address == pubKeyToAddress($pubkey);
}

function msgBuilder($state, $message) {
    return json_encode(array('status'=>$state, 'msg'=>$message)); 
}
/** */

$data = json_decode(file_get_contents("php://input"));
$request = $data->request;

// Create a standard of eth address by lowercasing them
// Some wallets send address with upper and lower case characters
$reqAddress = "";
if (!empty($data->reqAddress)) {
  $reqAddress = strtolower($data->reqAddress);
}

if($reqAddress == "") {
    echo json_encode(array('status'=>'error', 'msg'=>'Please enter your address!'));
    exit;
}

// validate if reqAddress is an ethereum address
$validator = new EthereumValidator();
if($validator->isAddress($reqAddress) === false ) {
    echo json_encode(array('status'=>'error', 'msg'=>'Address is not valid. Please check it again!'));
    exit;
}



if ($request == "requestMessage" ) {
    if(!isset($_SESSION[$reqAddress]['nonce'])) {
        $nonce = uniqid();
        $_SESSION[$reqAddress]['nonce'] = $nonce;
        echo msgBuilder("signRequest", "Sign this message to validate that you are the owner of the account. Random string: " . $nonce);
        exit;
    }
    else {
        $nonce = $_SESSION[$reqAddress]['nonce'];
        echo msgBuilder("signRequest", "Sign this message to validate that you are the owner of the account. Random string: " . $nonce);
        exit;
    }    
}

if ($request == "auth") {
  $nonce = $_SESSION[$reqAddress]['nonce'];
  $signature = $data->signature;
  $message = "Sign this message to validate that you are the owner of the account. Random string: " . $nonce;
  // If verification passed, authenticate user
  if (verifySignature($message, $signature, $reqAddress)) {
    // Create a new random nonce for the next login
    $nonce = uniqid();
    $_SESSION[$reqAddress]['nonce'] = $nonce;

    // Create JWT Token
    $token = array();
    $token['address'] = $reqAddress;
    $JWT = JWT::encode($token, $GLOBALS['JWT_secret']);
    echo msgBuilder("success", $JWT);
  } else {
    echo msgBuilder("failed", "You are not the owner of the account");
  }

  exit;
}


if ($request == "requestToken") {

    try { $JWT = JWT::decode($data->JWT, $GLOBALS['JWT_secret']);}
    catch (\Exception $e) { echo msgBuilder('error', 'Authentication error'); exit; }

    if ($JWT->address !== $reqAddress) {
        echo msgBuilder('error', 'Authentication error'); exit;
    }

    $faucetAccount = $faucetWalletAddress;
    $faucetBalance = getBalance($eth, $faucetAccount);
    $reqAddressBalance = getBalance($eth, $reqAddress);

    $contract = new Contract($web3->provider, $erc20AbiJson);

    $contract = $contract->at($tokenContractAddress);

    $contract->call('balanceOf', $faucetAccount, function ($err, $result) use ($decimals, &$faucetBalance) {
        if ($err !== null) {
            throw $err;
        }
        if ($result) {
            $faucetBalance = $result[0];
        }
    });

    $contract->call('balanceOf', $reqAddress, function ($err, $result) use ($decimals, &$reqAddressBalance) {
        if ($err !== null) {
            throw $err;
        }
        if ($result) {
            $reqAddressBalance = $result[0];
        }
    });

    $bigDecimals    = Utils::toBn(pow(10, (int) $decimals));
    $maxBalance     = Utils::toBn(MAX_BALANCE)->multiply($bigDecimals);

    if($reqAddressBalance->compare($maxBalance) >= 0) {
        echo json_encode(array('status'=>'error', 'msg'=>'User is greedy - already has too much NETC'));
        exit;
    };

    // send specific amount of NETC to request account

    $estimatedGas = '0x200b20';

    $faucetAmountBn = Utils::toBn($faucetAmount)->multiply($bigDecimals)->toString();

    $contract->at($tokenContractAddress)->estimateGas('transfer', $reqAddress, $faucetAmountBn, [
        'from' => $faucetAccount,
    ], function ($err, $result) use (&$estimatedGas) {
        if ($err !== null) {
            throw $err;
        }
        $estimatedGas = '0x' . $result->toHex();
    });

    $data = $contract->getData('transfer', $reqAddress, $faucetAmountBn);
    $nonce = getNonce($eth, $faucetAccount);

    $transaction = new Transaction([
        'nonce' => '0x' . $nonce->toHex(),
        'to' => $tokenContractAddress,
        'gas' => $estimatedGas,
        'gasPrice' => '0x' . Utils::toWei('50', 'gwei')->toHex(),
        'data' => '0x' . $data,
        'chainId' => $chainId
    ]);
    $transaction->sign($faucetWalletPrivateKey);

    $txHash = '';
    $eth->sendRawTransaction('0x' . $transaction->serialize(), function ($err, $transaction) use ($eth, $reqAddress, $faucetAccount, &$txHash) {
        if ($err !== null) {
            echo 'Error: ' . $err->getMessage();
            exit;
        }
        // echo 'Tx hash: ' . $transaction . PHP_EOL;
        $txHash = $transaction;
    });

    $transaction = confirmTx($eth, $txHash);
    if (!$transaction) {
        throw new Error('Transaction was not confirmed.');
    }

    echo json_encode(array('status'=>'success', 'msg'=>'Transaction has been confirmed', 'txHash'=> $txHash));

}
