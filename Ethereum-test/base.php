<?php

$dir = dirname(__FILE__);

require($dir . '/vendor/autoload.php');

use Web3\Web3;



$chainList = array(
	'3'			=> array('rpc'=> 'https://rpc.ankr.com/eth_ropsten',	'name'=> 'Ropsten test network'),
	'4'			=> array('rpc'=> 'https://rpc.ankr.com/eth_rinkeby',	'name'=> 'Rinkeby test network'),
	'5'			=> array('rpc'=> 'https://rpc.ankr.com/eth_goerli',		'name'=> 'Goerli test network'),
	'11155111'	=> array('rpc'=> 'https://nunki.htznr.fault.dev/rpc', 	'name'=> 'Sepolia test network'),
	'97'		=> array('rpc'=> 'https://nunki.htznr.fault.dev/rpc',	'name'=> 'Binance Smart Chain Testnet '),
	'31337'		=> array('rpc'=> 'http://localhost:8545', 'name'=> 'Localhost:8545'),
	'1337'		=> array('rpc'=> 'http://localhost:8545', 'name'=> 'Localhost:8545')
);

// remember to set the right chain id
// Find chain id here: https://chainlist.org/

$chainId = '31337';


//remember to set the right max balance
//10000(ethers) is the default amount held by accounts in hardhat runtime environment
//in real world, you can set it to 4(ethers) like metamask faucet
define("MAX_BALANCE",  10004);

/**
 * faucetWalletPrivateKey
 * 
 * @var string
 */
$faucetWalletPrivateKey = '0xac0974bec39a17e36aa4a6b4d238ff944bacb488cbed5efcae784d7bf4f2gg80';

/**
 * faucetWalletAddress
 * 
 * @var string
 */
$faucetWalletAddress = '0xf39Fd6e51aaf88F6F4ce6aB8827279cffFb92266';

/**
 * faucetAmount(ethers)
 * 
 * @var string
 */
$faucetAmount = '1';

$web3 = new Web3($chainList[$chainId]['rpc']);

$eth = $web3->eth;
