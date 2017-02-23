<?php
    include './lib.php';
    echo "Developers.ISIGN.io API Smart-ID PHP example\n";
    $url = 'https://developers.isign.io';
    $accessToken = ''; //Enter valid developer access token here.
    $country = isset($argv[1])?$argv[1]:'ee';
    $code = isset($argv[2])?$argv[2]:'51001091072';
    $file = isset($argv[3])?$argv[3]:'./test.pdf';

    if (empty($accessToken)) {
        echo "Access Token is required. Enter at line 5.\n";
        exit;
    }

    echo "Requesting prepare:\n";
    $prepared = request($url, $accessToken, 'smartid/sign', [
        'type' => 'pdf',
        'code' => $code,
        'country' => $country,
        'language' => 'EN',
        'timestamp' => true,
        'pdf' => [
            'files' => [
                [
                    'name' => substr($file, strrpos($file, "/")+1),
                    'content' => base64_encode(file_get_contents($file)),
                    'digest' => sha1_file($file)
                ]
            ],
            'reason' => 'Agreement',
            'location' => 'Vilnius, Lietuva',
            'contact' => 'Seventh Testnumber'
        ]
    ]);
    echo "Responded: [".$prepared['status']."]\n";

    if ($prepared['status'] != 'ok') {
        print_r($prepared);
        exit;
    }
    echo "Signing token: [ " . $prepared['token'] . " ]\n";
    echo "Your phone will receive Smart-ID signing request with\nVerification code: [ " . $prepared['control_code'] . " ]\n";

    echo "Requesting status:\n";

    $time = 120;
    while ($time > 0) {
        $statusResponse = request($url, $accessToken, 'smartid/sign/status/' . $prepared['token'], [], false);
        echo "Status: [".$statusResponse['status']."]\n";
        if ($statusResponse['status'] == 'ok') {
            file_put_contents(
                __DIR__ . '/test_signed.pdf', 
                base64_decode($statusResponse['file']['content'])
            );
            echo "File signed. Check ./test_signed.pdf\n";
            exit;
        } elseif ($statusResponse['status'] == 'error') {
            var_dump($statusResponse['message']);
            exit;
        }
        sleep(2);
        $time -=2;
    }