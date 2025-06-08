<?php
/*
PHP code to receive webhook JSON from application and relay to a Discord webhook URL with modified JSON template.
Webhook JSON will be modified to the required format before sending to Discord.
This relay is used for Series/Anime episode notification.
- Version 1.0
*/

// Load configuration
require_once 'config.php';

// Function to extract specific field values
function extractField($fields, $name)
{
    foreach ($fields as $field) {
        if ($field['name'] === $name) {
            return $field['value'];
        }
    }
    return null;
}

// Function to parse the title into different components
function parseTitle($title)
{
    $pattern = '/^(.*) - (\d+)x(\d+) - (.*)$/';
    if (preg_match($pattern, $title, $matches)) {
        return [
            'full' => $matches[0],
            'series_name' => $matches[1],
            'season_number' => $matches[2],
            'episode_number' => $matches[3],
            'episode_title' => $matches[4]
        ];
    }
    return null;
}

// Function to relay the modified JSON to the actual Discord webhook
function relayDiscordWebhook($JsonData, $discordWebhookUrl)
{
    // Removed: Encode JSON
    //$newJson = json_encode($newData);

    // Send the modified JSON to the actual Discord webhook
    $ch = curl_init($discordWebhookUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $JsonData);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    // Check if the request was successful
    if ($httpCode !== 204) { // Discord returns 204 No Content on success
        http_response_code(500); // Internal Server Error
        echo "Error sending data to Discord: HTTP $httpCode";
        exit;
    }

    // Respond with a success message
    http_response_code(200); // OK
    echo "Webhook relayed successfully";
}

// Capture notification JSON from sonarr
$inputJson = file_get_contents('php://input');

// DEBUG: Test Input from JSON file
//$inputJson = file_get_contents('sample_webhook.json');

$data = json_decode($inputJson, true);

// Handle test message from Sonarr
if (isset($data['content'])) {

    $sameJson = json_encode($data);
    // Send the JSON as is to the actual Discord webhook
    relayDiscordWebhook($sameJson, $discordWebhookUrl);

} elseif (isset($data['embeds']) && is_array($data['embeds'])) {

    // Extract fields from the input JSON
    $embed = $data['embeds'][0];
    $title = $embed['title'];
    $tvdb_url = $embed['url'];
    $imageUrl = $embed['image']['url'];
    $thumbnailUrl = $embed['thumbnail']['url'];
    $fields = $embed['fields'];
    $timestamp = $embed['timestamp'];

    // DEBUG: Print all the values from above
    //echo "Title: " . $title . "\n";
    //echo "URL: " . $tvdb_url . "\n";
    //echo "Image URL: " . $imageUrl . "\n";
    //echo "Fields: " . json_encode($fields) . "\n";

    // Parse the title into season, episode, and episode title
    $parsedTitle = parseTitle($title);
    if ($parsedTitle === null) {
        http_response_code(400); // Bad Request
        echo "Invalid title format";
        exit;
    }

    // Extract required fields values
    $language = extractField($fields, 'Languages');
    $links = extractField($fields, 'Links');

    // DEBUG: print all the values
    //echo "Language: " . $language . "\n";
    //echo "Links: " . $links . "\n";

    // Extract the Trakt link from the links field
    $traktPattern = '/\[Trakt\]\((.*?)\)/';
    preg_match($traktPattern, $links, $traktMatches);
    $traktLink = $traktMatches[1];

    // Season number should be 2 digits
    $seasonNumber = sprintf("%02d", $parsedTitle['season_number']);
    // Construct the new Title
    $newTitle = $parsedTitle['series_name'] . ' - S' . $seasonNumber . 'E' . $parsedTitle['episode_number'] . ' - ' . $parsedTitle['episode_title'];

    // Construct the new JSON structure
    $newData = [
        'embeds' => [
            [
                'title' => $newTitle,
                'url' => $traktLink,
                'color' => 11164867,
                'fields' => [
                    [
                        'name' => 'Episode added',
                        'value' => 'Episode ' . $parsedTitle['episode_number'] . ' - ' . $parsedTitle['episode_title']
                    ],
                    [
                        'name' => 'Season',
                        'value' => 'Season ' . $parsedTitle['season_number'],
                        'inline' => true
                    ],
                    [
                        'name' => 'Languages',
                        'value' => $language,
                        'inline' => true
                    ],
                    [
                        'name' => 'Links',
                        'value' => '[Trakt](' . $traktLink . ')',
                        'inline' => true
                    ]
                ],
                'author' => [
                    'name' => 'New Episode Update',
                    'icon_url' => $icon_url
                ],
                'timestamp' => $timestamp,
                'thumbnail' => [
                    'url' => $thumbnailUrl
                ]
            ]
        ]
    ];

    // Encode the new JSON
    $newJson = json_encode($newData);
    // Send the modified JSON to the actual Discord webhook
    relayDiscordWebhook($newJson, $discordWebhookUrl);

} else {
    // JSON does not match either structure
    echo "Invalid JSON structure";
    http_response_code(400); // Bad Request
    exit;
}