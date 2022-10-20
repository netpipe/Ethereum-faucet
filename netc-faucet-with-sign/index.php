<!doctype html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="./bootstrap.min.css">
    
<!-- Remember to chmod 0755 uploads directory -->
   <!-- <script src="https://unpkg.com/axios/dist/axios.min.js"></script>-->
    <script type="text/javascript" src="./assets/axios.min.js"></script>

    <!-- Ethereum library for interacting with the blockchain 
    <script src="https://cdn.jsdelivr.net/npm/web3@latest/dist/web3.min.js"></script>
    <script type="text/javascript" src="https://unpkg.com/web3modal@1.9.8/dist/index.js"></script>
    <script type="text/javascript" src="https://unpkg.com/@walletconnect/web3-provider@1.7.8/dist/umd/index.min.js"></script>
-->
    <script type="text/javascript" src="./assets/web3.min.js"></script>
    <script type="text/javascript" src="./assets/index.js"></script>
    <script type="text/javascript" src="./assets/index.min.js"></script>
<!--body {
  background: lightblue url("img_tree.gif") no-repeat fixed center;
  background-image: url('')
} -->
    
    

    <title>NetCoin Faucet</title>
  </head>
  <body>
  <div>
  <?php

         ini_set("display_errors",1);
       error_reporting(E_ALL);
      //   include_once ("./faucet.php");
  //generatekeysandprint();
  ?>
  </div>

  <?php
    include ("faucet2.php");
  ?>
  </body>
</html>
