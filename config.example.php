<?php
// Copy this file to config.php and fill in your real values.
// config.php is listed in .gitignore, so it is never uploaded to GitHub.
return [
    'env'            => 'sandbox',   // change to 'production' when going live
    'consumerKey'    => 'YOUR_CONSUMER_KEY',
    'consumerSecret' => 'YOUR_CONSUMER_SECRET',
    'shortcode'      => '174379',    // sandbox; live = your store/head office number
    'till'           => '174379',    // sandbox; live = your till number
    'passkey'        => 'YOUR_PASSKEY',
    'callbackUrl'    => 'https://your-site.com/callback.php', // must be public HTTPS
];