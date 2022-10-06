<?php

$dir = dirname(__FILE__);

require($dir . '/vendor/autoload.php');

use Web3\Web3;
use Web3\Providers\HttpProvider;
use Web3\RequestManagers\HttpRequestManager;

$chainList = array(
	'3'			=> array('rpc'=> 'https://rpc.ankr.com/eth_ropsten',	'name'=> 'Ropsten test network'),
	'4'			=> array('rpc'=> 'https://rpc.ankr.com/eth_rinkeby',	'name'=> 'Rinkeby test network'),
	'5'			=> array('rpc'=> 'https://rpc.ankr.com/eth_goerli',		'name'=> 'Goerli test network'),
	'11155111'	=> array('rpc'=> 'https://nunki.htznr.fault.dev/rpc', 	'name'=> 'Sepolia test network'),
	'97'		=> array('rpc'=> 'https://nunki.htznr.fault.dev/rpc',	'name'=> 'Binance Smart Chain Testnet '),
	'31337'		=> array('rpc'=> 'http://localhost:8545', 'name'=> 'Localhost:8545'),
	'1337'		=> array('rpc'=> 'http://localhost:8545', 'name'=> 'Localhost:8545'),
    '2000'      => array('rpc'=> 'https://rpc-us.dogechain.dog', 'name'=> 'Dogechain Mainnet')
);

// remember to set the right chain id
// Find chain id here: https://chainlist.org/

$chainId = '2000';


//remember to set the right max balance
//10000(ethers) is the default amount held by accounts in hardhat runtime environment
//in real world, you can set it to 4(ethers) like metamask faucet
//for netc, if request address hold above 5 netc, alert greedy
define("MAX_BALANCE",  5);

/**
 * faucetWalletPrivateKey
 * 
 * @var string
 */
$faucetWalletPrivateKey = 'FFa044e0edd06fbc97195e052bFFc69d586ecc36c8510ff41fb389a6cFFF41FF';

/**
 * faucetWalletAddress
 * 
 * @var string
 */
$faucetWalletAddress = '0x123FGGB409672dC3dC0ea158FdA8352eGG8BA9CC';

/**
 * faucetAmount(ethers)
 * 
 * @var string
 */
$faucetAmount = '1';

/**
 * Http request timeout(seconds)
 * 
 * @var float
 */
$timeout = 10;

$web3 = new Web3(new HttpProvider(new HttpRequestManager($chainList[$chainId]['rpc'], $timeout)));

$eth = $web3->eth;

/**
 * NETC tokenContractAddress on DogeChain Mainnet
 * 
 * @var string
 */
$tokenContractAddress = '0x4631Ef412C736F5eBA2bF8115dEbaBB65B9c2d33';

$erc20AbiJsonFile = file_get_contents($dir . '/ERC20ABI.json');
$erc20AbiJson = json_decode($erc20AbiJsonFile);
$decimals = 8;
