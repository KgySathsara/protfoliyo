<?php
function sendMail($to, $subject, $message, $headers = '')
{
    $smtpServer = 'smtp.gmail.com';
    $port = 465; // Use port 465 for SSL
    $username = 'yohansathsara87@gmail.com';
    $password = 'easn nlac lbnn rytd';

    $contextOptions = [
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true,
        ],
    ];

    $context = stream_context_create($contextOptions);
    $socket = stream_socket_client("ssl://$smtpServer:$port", $errno, $errstr, 10, STREAM_CLIENT_CONNECT, $context);

    if (!$socket) {
        echo "Connection failed: $errstr ($errno)";
        return false;
    }

    function serverResponse($socket, $expectedCode)
    {
        while ($response = fgets($socket, 515)) {
            $code = substr($response, 0, 3);
            if ($code == $expectedCode) {
                return true;
            }
            if ($code != '250' && $code != '220') {
                echo "Unexpected response from server: $response";
                return false;
            }
        }
        return false;
    }

    if (!serverResponse($socket, '220')) return false;

    fwrite($socket, "EHLO $smtpServer\r\n");
    if (!serverResponse($socket, '250')) return false;

    fwrite($socket, "AUTH LOGIN\r\n");
    if (!serverResponse($socket, '334')) return false;
    fwrite($socket, base64_encode($username) . "\r\n");
    if (!serverResponse($socket, '334')) return false;
    fwrite($socket, base64_encode($password) . "\r\n");
    if (!serverResponse($socket, '235')) return false;

    fwrite($socket, "MAIL FROM: <$username>\r\n");
    if (!serverResponse($socket, '250')) return false;

    fwrite($socket, "RCPT TO: <$to>\r\n");
    if (!serverResponse($socket, '250')) return false;

    fwrite($socket, "DATA\r\n");
    if (!serverResponse($socket, '354')) return false;

    fwrite($socket, "Subject: $subject\r\n$headers\r\n\r\n$message\r\n.\r\n");
    if (!serverResponse($socket, '250')) return false;

    fwrite($socket, "QUIT\r\n");
    if (!serverResponse($socket, '221')) return false;

    fclose($socket);

    echo "Message sent successfully!";
    return true;
}

// Usage in your form handling script
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Database configuration
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "contact_form_db";

    // Collect and sanitize form data
    $name = htmlspecialchars(trim($_POST['name']));
    $email = htmlspecialchars(trim($_POST['email']));
    $message = htmlspecialchars(trim($_POST['message']));

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "Invalid email format";
        exit;
    }

    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Prepare and bind
    $stmt = $conn->prepare("INSERT INTO contacts (name, email, message) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $email, $message);

    // Execute the statement
    if ($stmt->execute()) {
        $to = "your_email@gmail.com";
        $subject = "New Contact Message from $name";
        $body = "Name: $name\nEmail: $email\n\nMessage:\n$message";
        $headers = "From: $email\r\n";

        if (sendMail($to, $subject, $body, $headers)) {
            echo "Message sent and saved successfully!";
        } else {
            echo "Message saved but failed to send email.";
        }
    } else {
        echo "Failed to save message.";
    }

    // Close the statement and connection
    $stmt->close();
    $conn->close();
} else {
    echo "Invalid request method.";
}
?>
