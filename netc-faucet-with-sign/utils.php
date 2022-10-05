<?php

$dir = dirname(__FILE__);

require($dir . '/vendor/autoload.php');

use kornrunner\Keccak; // composer require greensea/keccak

class EthereumValidator
{
    public function isAddress(string $address): bool
    {
        // See: https://github.com/ethereum/web3.js/blob/7935e5f/lib/utils/utils.js#L415
        if ($this->matchesPattern($address)) {
            return $this->isAllSameCaps($address) ?: $this->isValidChecksum($address);
        }

        return false;
    }

    protected function matchesPattern(string $address): int
    {
        return preg_match('/^(0x)?[0-9a-f]{40}$/i', $address);
    }

    protected function isAllSameCaps(string $address): bool
    {
        return preg_match('/^(0x)?[0-9a-f]{40}$/', $address) || preg_match('/^(0x)?[0-9A-F]{40}$/', $address);
    }

    protected function isValidChecksum($address)
    {
        $address = str_replace('0x', '', $address);
        $hash = Keccak::hash(strtolower($address), 256);

        // See: https://github.com/web3j/web3j/pull/134/files#diff-db8702981afff54d3de6a913f13b7be4R42
        for ($i = 0; $i < 40; $i++ ) {
            if (ctype_alpha($address{$i})) {
                // Each uppercase letter should correlate with a first bit of 1 in the hash char with the same index,
                // and each lowercase letter with a 0 bit.
                $charInt = intval($hash{$i}, 16);

                if ((ctype_upper($address{$i}) && $charInt <= 7) || (ctype_lower($address{$i}) && $charInt > 7)) {
                    return false;
                }
            }
        }

        return true;
    }
}

function getBalance($eth, $account) {
    $balance = 0;
    $eth->getBalance($account, function ($err, $rawBalance) use (&$balance) {
        if ($err !== null) {
            throw $err;
        }
        $balance = $rawBalance;
    });
    return $balance;
}

function getNonce($eth, $account) {
    $nonce = 0;
    $eth->getTransactionCount($account, function ($err, $count) use (&$nonce) {
        if ($err !== null) {
            throw $err;
        }
        $nonce = $count;
    });
    return $nonce;
}

function getTransactionReceipt($eth, $txHash) {
    $tx;
    $eth->getTransactionReceipt($txHash, function ($err, $transaction) use (&$tx) {
        if ($err !== null) {
            throw $err;
        }
        $tx = $transaction;
    });
    return $tx;
}

function confirmTx($eth, $txHash) {
    $transaction = null;
    while (!$transaction) {
        $transaction = getTransactionReceipt($eth, $txHash);
        if ($transaction) {
            return $transaction;
        } else {
            // echo "Sleep one second and wait transaction to be confirmed" . PHP_EOL;
            sleep(1);
        }
    }
}
