<?php

header('Content-Type: application/json');

// Function to validate the phone number (should start with 92)
function validate_phone_number($number) {
    return preg_match('/^92\d{10}$/', $number);
}

// Function to validate the CNIC (should be without dashes)
function validate_cnic($cnic) {
    return preg_match('/^\d{13}$/', $cnic);
}

// Function to fetch details
def fetch_details($arg) {
    $url = 'https://pakistandatabase.com/databases/sim.php';
    $params = ['search_query' => $arg];

    $options = [
        'http' => [
            'method' => 'POST',
            'header' => 'Content-type: application/x-www-form-urlencoded',
            'content' => http_build_query($params)
        ]
    ];

    $context = stream_context_create($options);
    $response = file_get_contents($url, false, $context);

    if ($response === FALSE) {
        echo json_encode(['error' => 'Failed to retrieve data.']);
        exit;
    }

    return $response;
}

// Function to convert HTML table data to JSON format
function convert_to_json($html) {
    preg_match_all('/<tr>(.*?)<\/tr>/s', $html, $rows);
    $data = [];
    $numbers = [];
    $name = null;
    $address = null;

    foreach ($rows[1] as $row) {
        preg_match_all('/<td>(.*?)<\/td>/', $row, $cols);

        if (count($cols[1]) >= 4) {
            // Set name and address if not already set
            if (!$name && $cols[1][1] !== 'DATA NOT RECIEVED FROM NADRA') {
                $name = $cols[1][1];
            }
            if (!$address && $cols[1][3] !== 'no') {
                $address = $cols[1][3];
            }
            $numbers[] = $cols[1][0];
        }
    }

    // Fallback if name or address is not found
    $name = $name ?: 'Unknown Name';
    $address = $address ?: 'Unknown Address';

    $data = [
        'name' => $name,
        'CNIC' => $rows[0][2] ?? 'Unknown CNIC',
        'address' => $address,
        'mobile' => $numbers
    ];
    return json_encode($data, JSON_PRETTY_PRINT);
}

// Get query from URL
$query = $_GET['query'] ?? null;

if (!$query) {
    echo json_encode(['error' => 'No query provided. Use ?query={number or CNIC}']);
    exit;
}

// Validate input
if (!validate_phone_number($query) && !validate_cnic($query)) {
    echo json_encode(['error' => 'Invalid number or CNIC format.']);
    exit;
}

// Fetch and output data
$html = fetch_details($query);
echo convert_to_json($html);

?>
