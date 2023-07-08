<!DOCTYPE html>
<html lang="EN">
<head>
	<title>Google API import contacts example - sbhrashad</title>

	<meta charset="UTF-8">

	<!-- All other meta tag here -->
	<meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0" >	

	<!-- CSS styles -->
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" type="text/css">	

<style>
    /* Table Styles */
    table {
        width: 100%;
        border-collapse: collapse;
    }
    
    th, td {
        padding: 8px;
        text-align: left;
        border-bottom: 1px solid #ddd;
    }
    
    th {
        background-color: #f2f2f2;
    }
    
    /* Image Styles */
    img {
        max-width: 100px;
        height: auto;
    }
</style>


</head>

<body>
    
<?php
session_start();

// Include Google API library
require_once 'google-api-php-client/src/Google/autoload.php';

$google_client_id = '276931171196-kkr2dvej9iu9iubd5hc73a2oenvqe065.apps.googleusercontent.com';
$google_client_secret = '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';
$google_redirect_uri = 'https://animexdev.com/people/import-contacts-with-php.php';

// Setup new Google client
$client = new Google_Client();
$client->setApplicationName('Contact Integration');
$client->setClientId($google_client_id);
$client->setClientSecret($google_client_secret);
$client->setRedirectUri($google_redirect_uri);
$client->setAccessType('offline'); // Change access type to offline
//$client->setPrompt('consent'); // Prompt for consent each time
$client->setScopes('https://www.googleapis.com/auth/contacts.readonly');

$googleImportUrl = $client->createAuthUrl();

// Curl function
function curl($url, $post = "") {
    $curl = curl_init();
    $userAgent = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; .NET CLR 1.1.4322)';
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
    if ($post != "") {
        curl_setopt($curl, CURLOPT_POST, 5);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
    }
    curl_setopt($curl, CURLOPT_USERAGENT, $userAgent);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($curl, CURLOPT_AUTOREFERER, true);
    curl_setopt($curl, CURLOPT_TIMEOUT, 10);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    $contents = curl_exec($curl);
    curl_close($curl);
    return $contents;
}

// Google response with contact. We set a session and redirect back
if (isset($_GET['code'])) {
    $auth_code = $_GET["code"];
    $_SESSION['google_code'] = $auth_code;
    header('Location: ' . $google_redirect_uri); // Redirect to remove the code from the URL
    exit();
}

$page = isset($_GET['page']) ? $_GET['page'] : 1;
$limit = 10; // Number of contacts to display per page
$start = ($page - 1) * $limit;

if (isset($_SESSION['google_code'])) {
    $auth_code = $_SESSION['google_code'];
    $fields = array(
        'code' => urlencode($auth_code),
        'client_id' => urlencode($google_client_id),
        'client_secret' => urlencode($google_client_secret),
        'redirect_uri' => urlencode($google_redirect_uri),
        'grant_type' => urlencode('authorization_code')
    );
    $post = '';
    foreach ($fields as $key => $value) {
        $post .= $key . '=' . $value . '&';
    }
    $post = rtrim($post, '&');

    $accesstoken = "";
    $result = curl('https://accounts.google.com/o/oauth2/token', $post);
    $response = json_decode($result);
    $accesstoken = $response->access_token;

  $url = 'https://people.googleapis.com/v1/people/me/connections?pageSize=2000&personFields=names,emailAddresses,phoneNumbers,photos&sortOrder=FIRST_NAME_ASCENDING&access_token=' . $accesstoken;
  $jsonResponse = curl($url);
    $data = json_decode($jsonResponse, true);
    $connections = $data['connections'];

    $total = count($connections);
    $totalPages = ceil($total / $limit);

    $contacts = array_slice($connections, $start, 10000);

    $tableHtml = '<table>';
    $tableHtml .= '<thead><tr><th>Name</th><th>Email</th><th>Phone Number</th><th>Image</th></tr></thead>';
    $tableHtml .= '<tbody>';

    foreach ($contacts as $connection) {
        $name = isset($connection['names'][0]['displayName']) ? $connection['names'][0]['displayName'] : '';
        $email = isset($connection['emailAddresses'][0]['value']) ? $connection['emailAddresses'][0]['value'] : '';
        $phone = isset($connection['phoneNumbers'][0]['value']) ? $connection['phoneNumbers'][0]['value'] : '';
        $image = isset($connection['photos'][0]['url']) ? $connection['photos'][0]['url'] : '';

        $tableHtml .= '<tr>';
        $tableHtml .= '<td>' . $name . '</td>';
        $tableHtml .= '<td>' . $email . '</td>';
        $tableHtml .= '<td>' . $phone . '</td>';
        $tableHtml .= '<td><img src="' . $image . '" alt="Profile Image" style="max-width: 100px;"></td>';
        $tableHtml .= '</tr>';
    }

    $tableHtml .= '</tbody>';
    $tableHtml .= '</table>';

    // Clear the session after fetching contacts
    unset($_SESSION['google_code']);

    echo $tableHtml;

    // Pagination
    if ($totalPages > 1) {
        echo '<div style="text-align: center; margin-top: 20px;">';
        if ($page > 1) {
            echo '<a href="?page=' . ($page - 1) . '">Previous</a>';
        }

        for ($i = 1; $i <= $totalPages; $i++) {
            if ($i == $page) {
                echo '<span style="margin: 0 5px;">' . $i . '</span>';
            } else {
                echo '<a href="?page=' . $i . '">' . $i . '</a>';
            }
        }

        if ($page < $totalPages) {
            echo '<a href="?page=' . ($page + 1) . '">Next</a>';
        }
        echo '</div>';
    }
}
?>



<div class="container">
	<div class="row">
		<br><br><br>
		<div class="col-lg-12">
			This page is a practical example on how to import google contacts. This is related with the folowing article 
			<br>
			<a href="https://github.com/MSRAJAWAT298" target="_blank">
				Import Google contacts with PHP or Javascript using Google Contacts API and OAUTH 2.0 
			</a>
			<br><br><br>

			<a class="btn btn-primary" href="<?php echo $googleImportUrl; ?>" role="button">Import google contacts</a>

		</div>
	</div>
</div>

<!-- Google CDN's jQuery -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.0/jquery.min.js"></script>


</body>
</html>