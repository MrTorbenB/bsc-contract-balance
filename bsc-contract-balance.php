<?php
/**
 * Plugin Name: BSC Contract Balances
 * Description: A plugin to display BNB balances of BSC contracts. Add per Shortcode. Shows BNB/EUR
 * Version: 0.1
 * Author: Internetdienstleistungen Torben Bühl
 * Text Domain: bsc-contract-balances
 */

function bsc_get_contract_balance($atts) {
    $a = shortcode_atts(array(
        'address' => 'no address provided'
    ), $atts);

    // Check the rate limit
    $requests = get_transient('bsc_requests');
    if ($requests !== false && $requests >= 5) {
        return "Bitte warten Sie einen Moment und aktualisieren Sie die Seite erneut.";
    }

    $api_key = "K62KV2PRAEJNHBMB3SFZEBV2ADMRIM9Q17"; // Replace this with your BscScan API Key
    $contract_address = $a['address'];

    // BscScan API call
    $bscscan_url = "https://api.bscscan.com/api?module=account&action=balance&address=$contract_address&tag=latest&apikey=$api_key";
    $bscscan_response = wp_remote_get($bscscan_url);
    $bscscan_body = wp_remote_retrieve_body($bscscan_response);
    $bscscan_data = json_decode($bscscan_body);
    $balance_wei = $bscscan_data->result;
    $balance_bnb = $balance_wei / pow(10, 18); // BNB has 18 decimal places

    // Update the rate limit
    if ($requests === false) {
        set_transient('bsc_requests', 1, 1);  // Set the transient to expire in 1 second
    } else {
        set_transient('bsc_requests', $requests + 1, 1);  // Increment the number of requests
    }

    // CoinGecko API call for BNB/EUR
    $coingecko_url = "https://api.coingecko.com/api/v3/simple/price?ids=binancecoin&vs_currencies=eur";
    $coingecko_response = wp_remote_get($coingecko_url);
    $coingecko_body = wp_remote_retrieve_body($coingecko_response);
    $coingecko_data = json_decode($coingecko_body);
    $bnb_eur = $coingecko_data->binancecoin->eur;

    // Convert the balance to EUR
    $balance_eur = round($balance_bnb * $bnb_eur, 2);

    // Return the results
    return "<div style='text-align:center'><strong>BNB:</strong> " . number_format($balance_bnb, 2, '.', ',') . " - <strong>EUR:</strong> " . number_format($balance_eur, 2, '.', ',') . "</div>";
}

add_shortcode('bsc_balance', 'bsc_get_contract_balance');
?>
