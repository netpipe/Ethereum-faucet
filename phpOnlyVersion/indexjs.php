<!-- ===== FRONTEND: Faucet GUI ===== -->
<!DOCTYPE html>
<html>
<head>
    <title>Ethereum Faucet</title>
    <style>
        body { font-family: sans-serif; text-align: center; padding: 50px; background: #f4f4f4; }
        button { padding: 10px 20px; font-size: 16px; margin: 10px; }
        #status { margin-top: 20px; font-weight: bold; }
    </style>
</head>
<body>
    <h1>🪙 Ethereum Web3 Faucet</h1>
    <p>Connect your wallet and choose an option:</p>
    <button onclick="connectWallet()">🔌 Connect Wallet</button><br>
    <button onclick="getFreeEth()">🎁 Get Free ETH</button>
    <button onclick="donateEth()">🙏 Donate</button>
    <div id="status">Not connected</div>

    <script>
        let userAddress = null;

        async function connectWallet() {
            if (window.ethereum) {
                const accounts = await ethereum.request({ method: 'eth_requestAccounts' });
                userAddress = accounts[0];
                document.getElementById('status').innerText = "Connected: " + userAddress;
            } else {
                alert("Please install MetaMask.");
            }
        }

        async function getFreeEth() {
            if (!userAddress) return alert("Connect your wallet first.");
            const formData = new FormData();
            formData.append('to', userAddress);

            const res = await fetch('faucet.php', { method: 'POST', body: formData });
            const data = await res.json();

            if (data.txHash) {
                document.getElementById('status').innerText = "🎉 Sent! TX Hash: " + data.txHash;
            } else {
                document.getElementById('status').innerText = "❌ Faucet error.";
            }
        }

        async function donateEth() {
            if (!userAddress) return alert("Connect your wallet first.");

            const tx = {
                from: userAddress,
                to: "0xYourFaucetAddress", // Faucet address to receive donations
                value: '0x38d7ea4c68000' // 0.01 ETH in hex
            };

            try {
                const txHash = await ethereum.request({
                    method: 'eth_sendTransaction',
                    params: [tx],
                });
                document.getElementById('status').innerText = "🙏 Donation sent! TX: " + txHash;
            } catch (e) {
                document.getElementById('status').innerText = "❌ Donation failed.";
            }
        }
    </script>
</body>
</html>
