<?php

require('./base.php');
require('./utils.php');

use Web3\Utils;
use Web3\Contract;
use Web3p\EthereumTx\Transaction;
use Web3p\EthereumTx\EIP1559Transaction;

$data = json_decode(file_get_contents("php://input"));

// Create a standard of eth address by lowercasing them
// Some wallets send address with upper and lower case characters
$reqAddress = "";
if (!empty($data->reqAddress)) {
  $reqAddress = strtolower($data->reqAddress);
}

if($reqAddress == "") {
    echo json_encode(array('status'=>'error', 'msg'=>'Please enter your address!'));
    return;
}

// validate if reqAddress is an ethereum address
$validator = new EthereumValidator();
if($validator->isAddress($reqAddress) === false ) {
    echo json_encode(array('status'=>'error', 'msg'=>'Address is not valid. Please check it again!'));
    return;
}

$faucetAccount = $faucetWalletAddress;
$faucetBalance = getBalance($eth, $faucetAccount);
$reqAddressBalance = getBalance($eth, $reqAddress);

list($bnq, $bnr) = Utils::fromWei($reqAddressBalance, 'ether');

$reqAddress_balance_in_ether = $bnq->toString();

if($reqAddress_balance_in_ether > MAX_BALANCE) {
    echo json_encode(array('status'=>'error', 'msg'=>'User is greedy - already has too much ether'));
    return;
}


// send 0.01 ether to request account
$nonce = getNonce($eth, $faucetAccount);
$value = Utils::toWei($faucetAmount, 'ether');


$transaction = new Transaction([
    'nonce' => '0x' . $nonce->toHex(),
    'to' => $reqAddress,
    'gas' => '0xfd240',
    'gasPrice' => '0x' . Utils::toWei('5', 'gwei')->toHex(),
    'value' => '0x' . $value->toHex(),
    'chainId' => $chainId
]);
$transaction->sign($faucetWalletPrivateKey);
$txHash = '';
$eth->sendRawTransaction('0x' . $transaction->serialize(), function ($err, $transaction) use ($eth, $reqAddress, $faucetAccount, &$txHash) {
    if ($err !== null) {
        echo 'Error: ' . $err->getMessage();
        return;
    }
    // echo 'Tx hash: ' . $transaction . PHP_EOL;
    $txHash = $transaction;
});
$transaction = confirmTx($eth, $txHash);
if (!$transaction) {
    throw new Error('Transaction was not confirmed.');
}
// echo "Transaction has been confirmed. " . " transaction hash: " . $txHash . " block number: " . $transaction->blockNumber . PHP_EOL;

echo json_encode(array('status'=>'success', 'msg'=>'Transaction has been confirmed', 'txHash'=> $txHash));