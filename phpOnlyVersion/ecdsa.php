<?php
/**
 * Minimal secp256k1 ECDSA signer for Ethereum (pure PHP)
 * WARNING: Do not use in production without full review and testing.
 */

// Curve parameters
define('SECP256K1_P', gmp_init('0xFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFEFFFFFC2F'));
define('SECP256K1_N', gmp_init('0xFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFEBAAEDCE6AF48A03BBFD25E8CD0364141'));
define('SECP256K1_GX', gmp_init('0x79BE667EF9DCBBAC55A06295CE870B07029BFCDB2DCE28D959F2815B16F81798'));
define('SECP256K1_GY', gmp_init('0x483ADA7726A3C4655DA4FBFC0E1108A8FD17B448A68554199C47D08FFB10D4B8'));

function point_double($x1, $y1) {
    $p = SECP256K1_P;
    $s = gmp_mod(gmp_mul(gmp_mul(3, gmp_pow($x1, 2)), gmp_invert(gmp_mul(2, $y1), $p)), $p);
    $x3 = gmp_mod(gmp_sub(gmp_pow($s, 2), gmp_mul(2, $x1)), $p);
    $y3 = gmp_mod(gmp_sub(gmp_mul($s, gmp_sub($x1, $x3)), $y1), $p);
    return [$x3, $y3];
}

function point_add($x1, $y1, $x2, $y2) {
    $p = SECP256K1_P;
    if ($x1 === $x2 && $y1 === $y2) return point_double($x1, $y1);
    $s = gmp_mod(gmp_mul(gmp_sub($y2, $y1), gmp_invert(gmp_sub($x2, $x1), $p)), $p);
    $x3 = gmp_mod(gmp_sub(gmp_sub(gmp_pow($s, 2), $x1), $x2), $p);
    $y3 = gmp_mod(gmp_sub(gmp_mul($s, gmp_sub($x1, $x3)), $y1), $p);
    return [$x3, $y3];
}

function scalar_mult($k, $x = SECP256K1_GX, $y = SECP256K1_GY) {
    $k_bin = strrev(gmp_strval($k, 2));
    $qx = null; $qy = null;
    for ($i = 0; $i < strlen($k_bin); $i++) {
        if ($k_bin[$i] == '1') {
            if ($qx === null) {
                $qx = $x; $qy = $y;
            } else {
                [$qx, $qy] = point_add($qx, $qy, $x, $y);
            }
        }
        [$x, $y] = point_double($x, $y);
    }
    return [$qx, $qy];
}

function deterministic_k($msg_hash, $privkey) {
    // Extremely simplified; replace with full RFC 6979 if needed
    return gmp_mod(gmp_init(bin2hex(hash('sha256', $msg_hash . $privkey, true)), 16), SECP256K1_N);
}

function sign($msg_hash, $privkey_hex) {
    $z = gmp_init($msg_hash, 16);
    $d = gmp_init($privkey_hex, 16);
    $k = deterministic_k($msg_hash, $privkey_hex);

    [$rx, ] = scalar_mult($k);
    $r = gmp_mod($rx, SECP256K1_N);
    $k_inv = gmp_invert($k, SECP256K1_N);
    $s = gmp_mod(gmp_mul($k_inv, gmp_add($z, gmp_mul($r, $d))), SECP256K1_N);

    // Enforce low-s
    if (gmp_cmp($s, gmp_div(SECP256K1_N, 2)) > 0) {
        $s = gmp_sub(SECP256K1_N, $s);
    }

    return [
        'r' => str_pad(gmp_strval($r, 16), 64, '0', STR_PAD_LEFT),
        's' => str_pad(gmp_strval($s, 16), 64, '0', STR_PAD_LEFT),
        'v' => 27 // later adjust with chain_id if needed
    ];
}

$msg_hash = 'e331b6d69882b4c07c8b6094a9a278c67f4e58d1c759a4f70b7851b6b0a07c35'; // example Keccak hash
$priv = 'c87509a1c067bbde78beb793e6faeb0e7e3e9c4a21f3f0ce4e01f73c8beedb99'; // example private key

$signature = sign($msg_hash, $priv);
echo "r: {$signature['r']}\n";
echo "s: {$signature['s']}\n";
echo "v: {$signature['v']}\n";
