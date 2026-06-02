<?php
/**
 * GHL Software Services and Solutions - Enterprise Contact Form Handler
 * 
 * This PHP script receives contact form submissions via AJAX, validates the data,
 * routes the complete message directly to GHL admin, and automatically sends a 
 * professional, themed auto-responder thanking the customer.
 * 
 * It supports standard SMTP Socket connections (with TLS/SSL authentication) to
 * smtp.ghlsoftwaresolutions.com, with a smart fallback to PHP mail() if SMTP is not 
 * fully configured.
 */

// Set JSON output headers
header('Content-Type: application/json; charset=utf-8');

// 1. SMTP & System Configuration
// UPDATE THESE PARAMETERS WITH YOUR ACTUAL MAILBOX CREDENTIALS
define('SMTP_HOST', 'ghlsoftwaresolutions.com');
define('SMTP_PORT', 465); // 587 for TLS (recommended), 465 for SSL, or 25 for non-secure
define('SMTP_USER', 'info@ghlsoftwaresolutions.com'); // Your SMTP Username
define('SMTP_PASS', 'SL4uwi0%8(9G'); // YOUR SMTP Password (replace with actual password)
define('SMTP_SECURE', 'ssl'); // 'tls', 'ssl', or '' (empty for none)

// Routing Addresses
define('ADMIN_EMAIL', 'info@ghlsoftwaresolutions.com'); // Admin email that receives the lead details
define('SENDER_NAME', 'GHL Software Solutions'); // Display name for auto-responder
define('SENDER_EMAIL', 'info@ghlsoftwaresolutions.com'); // Sender email address (must match SMTP domain authentication)

// Toggle Auto-responder (Thank you mail to user)
define('SEND_THANK_YOU', true);

// 2. Fetch and Validate JSON Input
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method Not Allowed. Only POST requests are permitted.'
    ]);
    exit;
}

// Read the raw JSON payload
$raw_input = file_get_contents('php://input');
$data = json_decode($raw_input, true);

if (!$data) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Bad Request. Invalid or missing JSON payload.'
    ]);
    exit;
}

// Extract and sanitize input parameters
$name = isset($data['name']) ? trim(strip_tags($data['name'])) : '';
$email = isset($data['email']) ? filter_var(trim($data['email']), FILTER_VALIDATE_EMAIL) : false;
$company = isset($data['company']) ? trim(strip_tags($data['company'])) : 'Not Provided';
$inquiry = isset($data['inquiry']) ? trim(strip_tags($data['inquiry'])) : 'General Inquiry';
$message = isset($data['message']) ? trim(htmlspecialchars($data['message'])) : '';

// Validation checks
if (empty($name)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Please provide your name.']);
    exit;
}

if (!$email) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Please provide a valid email address.']);
    exit;
}

if (empty($message)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Please write your message.']);
    exit;
}

// 3. Construct HTML Mail Templates with Premium Branding
// Primary colors matching GHL Corporate Palette: #0EA5E9 (Primary Sky), #06b6d4 (Cyan Secondary)

// A. Template for GHL Admin Notification
$admin_subject = "New Enterprise Inquiry: " . $inquiry . " - " . $name;
$admin_html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>New Enterprise Inquiry</title>
</head>
<body style="font-family: \'Segoe UI\', Helvetica, Arial, sans-serif; background-color: #f1f5f9; margin: 0; padding: 20px; color: #1e293b;">
    <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05); overflow: hidden;">
        <!-- Header -->
        <div style="background: linear-gradient(135deg, #0EA5E9 0%, #06b6d4 100%); padding: 30px; text-align: center; color: #ffffff;">
            <h2 style="margin: 0; font-size: 24px; font-weight: 700; letter-spacing: -0.5px;">New Lead Received</h2>
            <p style="margin: 5px 0 0 0; opacity: 0.9; font-size: 14px;">GHL Portal Form Submission</p>
        </div>
        <!-- Lead details -->
        <div style="padding: 30px;">
            <p style="font-size: 16px; line-height: 1.5; margin-bottom: 25px;">You have received a new contact inquiry from the GHL Software Solutions website. See submission details below:</p>
            
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 30px; font-size: 15px;">
                <tr>
                    <td style="padding: 10px 0; border-bottom: 1px solid #f1f5f9; font-weight: 600; color: #64748b; width: 35%;">Contact Name:</td>
                    <td style="padding: 10px 0; border-bottom: 1px solid #f1f5f9; color: #0f172a; font-weight: 500;">' . $name . '</td>
                </tr>
                <tr>
                    <td style="padding: 10px 0; border-bottom: 1px solid #f1f5f9; font-weight: 600; color: #64748b;">Email Address:</td>
                    <td style="padding: 10px 0; border-bottom: 1px solid #f1f5f9; color: #0EA5E9; font-weight: 500;"><a href="mailto:' . $email . '" style="color:#0EA5E9; text-decoration:none;">' . $email . '</a></td>
                </tr>
                <tr>
                    <td style="padding: 10px 0; border-bottom: 1px solid #f1f5f9; font-weight: 600; color: #64748b;">Company Name:</td>
                    <td style="padding: 10px 0; border-bottom: 1px solid #f1f5f9; color: #0f172a;">' . $company . '</td>
                </tr>
                <tr>
                    <td style="padding: 10px 0; border-bottom: 1px solid #f1f5f9; font-weight: 600; color: #64748b;">Inquiry Service:</td>
                    <td style="padding: 10px 0; border-bottom: 1px solid #f1f5f9;"><span style="background-color: #e0f2fe; color: #0369a1; padding: 4px 10px; border-radius: 50px; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">' . $inquiry . '</span></td>
                </tr>
            </table>

            <div style="background-color: #f8fafc; border-radius: 12px; padding: 20px; border: 1px solid #f1f5f9;">
                <h4 style="margin: 0 0 10px 0; font-size: 14px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.5px;">Message Content:</h4>
                <p style="margin: 0; font-size: 15px; line-height: 1.6; color: #334155; white-space: pre-wrap;">' . nl2br($message) . '</p>
            </div>
        </div>
        <!-- Footer -->
        <div style="background-color: #f8fafc; padding: 20px 30px; border-top: 1px solid #e2e8f0; text-align: center; font-size: 12px; color: #94a3b8;">
            Sent from IP: ' . $_SERVER['REMOTE_ADDR'] . ' at ' . date('Y-m-d H:i:s') . '<br>
            &copy; ' . date('Y') . ' GHL Software Services & Solutions Pvt Ltd.
        </div>
    </div>
</body>
</html>
';

// B. Template for Auto-Responder Thanking Mail
$thankyou_subject = "Thank you for contacting GHL Software Services";
$thankyou_html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Thank You for Contacting GHL</title>
</head>
<body style="font-family: \'Segoe UI\', Helvetica, Arial, sans-serif; background-color: #f1f5f9; margin: 0; padding: 20px; color: #1e293b;">
    <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05); overflow: hidden;">
        <!-- Header -->
        <div style="background: linear-gradient(135deg, #0EA5E9 0%, #06b6d4 100%); padding: 35px 30px; text-align: center; color: #ffffff;">
            <h2 style="margin: 0; font-size: 24px; font-weight: 700; letter-spacing: -0.5px;">Inquiry Received Successfully</h2>
            <p style="margin: 5px 0 0 0; opacity: 0.9; font-size: 14px;">GHL Software Services & Solutions</p>
        </div>
        <!-- Core Message -->
        <div style="padding: 35px 30px;">
            <h3 style="margin: 0 0 15px 0; color: #0f172a; font-size: 18px; font-weight: 600;">Dear ' . $name . ',</h3>
            <p style="font-size: 15px; line-height: 1.6; color: #334155; margin-bottom: 20px;">
                Thank you for reaching out to GHL Software Services and Solutions. We are thrilled that you visited our platform and submitted an inquiry regarding <strong>' . $inquiry . '</strong>.
            </p>
            <p style="font-size: 15px; line-height: 1.6; color: #334155; margin-bottom: 25px;">
                Our enterprise SAP and cloud consulting experts are reviewing your business details and requirements. One of our specialists will contact you within <strong>24 business hours</strong> to schedule a detailed assessment.
            </p>
            
            <div style="border-left: 3px solid #0EA5E9; padding-left: 15px; margin: 25px 0; font-style: italic; color: #64748b; font-size: 14px;">
                "Empowering global enterprises with innovative IT, cloud migrations, and premium SAP consulting solutions."
            </div>

            <p style="font-size: 15px; line-height: 1.6; color: #334155; margin-bottom: 30px;">
                If your inquiry is urgent, please do not hesitate to contact our global headquarters directly at <strong>+91 7670946355</strong> or reply directly to this email.
            </p>
            
            <hr style="border: 0; border-top: 1px solid #f1f5f9; margin-bottom: 25px;">
            
            <p style="margin: 0 0 5px 0; font-size: 14px; font-weight: 700; color: #0f172a;">Warm regards,</p>
            <p style="margin: 0; font-size: 14px; font-weight: 600; color: #0EA5E9;">Enterprise Relations Team</p>
            <p style="margin: 0; font-size: 12px; color: #64748b;">GHL Software Services & Solutions Pvt Ltd</p>
        </div>
        <!-- Headquarters block -->
        <div style="background-color: #f8fafc; padding: 25px 30px; border-top: 1px solid #e2e8f0; font-size: 12px; color: #64748b; line-height: 1.5;">
            <strong style="color: #475569; font-size: 13px;">Global Headquarters</strong><br>
            Chitrapuri Colony, HIG Block-7, Flat No:202, 2nd Floor<br>
            Manikonda Jagir, Hyderabad - 500008, RangaReddy District, Telengana, INDIA.<br>
            <span style="color:#0EA5E9;">www.ghlsoftwaresolutions.com</span>
        </div>
    </div>
</body>
</html>
';

// 4. Send Emails via SMTP Socket connection or native PHP Mailer Fallback
$smtp_configured = (SMTP_PASS !== 'your_smtp_password_here' && SMTP_PASS !== '');

$admin_sent = false;
$user_sent = false;
$smtp_error = '';

if ($smtp_configured) {
    try {
        // Send Notification Email to GHL Admin
        $admin_sent = send_socket_smtp(
            ADMIN_EMAIL,
            $admin_subject,
            $admin_html,
            [
                'Reply-To' => $email,
                'From-Name' => $name
            ]
        );

        // Send Auto-responder to form filler
        if (SEND_THANK_YOU) {
            $user_sent = send_socket_smtp(
                $email,
                $thankyou_subject,
                $thankyou_html,
                [
                    'Reply-To' => ADMIN_EMAIL,
                    'From-Name' => SENDER_NAME
                ]
            );
        } else {
            $user_sent = true;
        }
    } catch (Exception $e) {
        $smtp_error = $e->getMessage();
        error_log("DEBUG: SMTP Exception caught: " . $smtp_error);
        // Socket connection failed; fall back to PHP mail()
        $admin_sent = false;
    }
}

// Fallback to PHP Mailer if SMTP is unconfigured or failed
if (!$admin_sent) {
    // Send to Admin
    $admin_headers = "MIME-Version: 1.0\r\n";
    $admin_headers .= "Content-type: text/html; charset=utf-8\r\n";
    $admin_headers .= "Content-Transfer-Encoding: base64\r\n";
    $admin_headers .= "From: " . $name . " <" . SENDER_EMAIL . ">\r\n"; // Sending as domain admin with lead name
    $admin_headers .= "Reply-To: " . $email . "\r\n";
    $admin_headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

    $encoded_admin_html = chunk_split(base64_encode($admin_html), 76, "\r\n");
    $admin_sent = mail(ADMIN_EMAIL, $admin_subject, $encoded_admin_html, $admin_headers);

    // Send to User
    if ($admin_sent && SEND_THANK_YOU) {
        $user_headers = "MIME-Version: 1.0\r\n";
        $user_headers .= "Content-type: text/html; charset=utf-8\r\n";
        $user_headers .= "Content-Transfer-Encoding: base64\r\n";
        $user_headers .= "From: " . SENDER_NAME . " <" . SENDER_EMAIL . ">\r\n";
        $user_headers .= "Reply-To: " . ADMIN_EMAIL . "\r\n";
        $user_headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

        $encoded_thankyou_html = chunk_split(base64_encode($thankyou_html), 76, "\r\n");
        $user_sent = mail($email, $thankyou_subject, $encoded_thankyou_html, $user_headers);
    } else {
        $user_sent = true;
    }
}

// 5. Output Response
if ($admin_sent && $user_sent) {
    echo json_encode([
        'success' => true,
        'message' => 'Thank you, ' . $name . '! Your inquiry has been sent successfully. An auto-confirmation email has been sent to ' . $email . '.'
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'System could not deliver emails. Please contact info@ghlsoftwaresolutions.com directly.',
        'debug' => !empty($smtp_error) ? $smtp_error : 'PHP mail() function rejected mail delivery'
    ]);
}

/**
 * Portable, standalone Socket SMTP Email Delivery Client.
 * Connects directly to server via fsockopen/SSL/TLS and performs authentications.
 *
 * @param string $to Recipient email address
 * @param string $subject Email Subject
 * @param string $html_body HTML formatted email body
 * @param array $extra_options Extra configuration options
 * @return bool True if mail accepted, throws exception on failure
 */
function send_socket_smtp($to, $subject, $html_body, $extra_options = [])
{
    $host = SMTP_HOST;
    $port = SMTP_PORT;
    $username = SMTP_USER;
    $password = SMTP_PASS;
    $secure = SMTP_SECURE;

    $from = SENDER_EMAIL;
    $from_name = isset($extra_options['From-Name']) ? $extra_options['From-Name'] : SENDER_NAME;
    $reply_to = isset($extra_options['Reply-To']) ? $extra_options['Reply-To'] : $from;

    // Helper function to read SMTP response (handling multi-line SMTP responses properly)
    $read_response = function($socket) {
        $response = '';
        while ($line = fgets($socket, 515)) {
            $response .= $line;
            if (strlen($line) >= 4 && substr($line, 3, 1) === ' ') {
                break;
            }
            if (strlen($line) < 4) {
                break;
            }
        }
        return $response;
    };

    // Establish raw connection
    $remote = ($secure === 'ssl' ? 'ssl://' : '') . $host;
    $socket = @fsockopen($remote, $port, $errno, $errstr, 15);

    if (!$socket) {
        throw new Exception("Connection to SMTP host $host failed: $errstr ($errno)");
    }

    // Capture banner response
    $response = $read_response($socket);

    // Initial EHLO identification
    fwrite($socket, "EHLO " . $_SERVER['SERVER_NAME'] . "\r\n");
    $response = $read_response($socket);

    // Start TLS Encryption Handshake if requested
    if ($secure === 'tls') {
        fwrite($socket, "STARTTLS\r\n");
        $response = $read_response($socket);
        if (substr($response, 0, 3) !== '220') {
            fclose($socket);
            throw new Exception("STARTTLS failed: " . $response);
        }

        // Enable secure encryption on the stream socket
        if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            fclose($socket);
            throw new Exception("Secure cryptographic TLS handshake failed.");
        }

        // Re-greet server securely
        fwrite($socket, "EHLO " . $_SERVER['SERVER_NAME'] . "\r\n");
        $response = $read_response($socket);
    }

    // Authenticate Login Credentials
    if ($username && $password) {
        fwrite($socket, "AUTH LOGIN\r\n");
        $response = $read_response($socket);
        if (substr($response, 0, 3) !== '334') {
            fclose($socket);
            throw new Exception("AUTH LOGIN rejected by host: " . $response);
        }

        // Send base64 username
        fwrite($socket, base64_encode($username) . "\r\n");
        $response = $read_response($socket);
        if (substr($response, 0, 3) !== '334') {
            fclose($socket);
            throw new Exception("Username credentials rejected: " . $response);
        }

        // Send base64 password
        fwrite($socket, base64_encode($password) . "\r\n");
        $response = $read_response($socket);
        if (substr($response, 0, 3) !== '235') {
            fclose($socket);
            throw new Exception("Password credentials rejected: " . $response);
        }
    }

    // Sender declaration
    fwrite($socket, "MAIL FROM:<$from>\r\n");
    $response = $read_response($socket);
    if (substr($response, 0, 3) !== '250') {
        fclose($socket);
        throw new Exception("Sender address <$from> rejected: " . $response);
    }

    // Recipient declaration
    fwrite($socket, "RCPT TO:<$to>\r\n");
    $response = $read_response($socket);
    if (substr($response, 0, 3) !== '250' && substr($response, 0, 3) !== '251') {
        fclose($socket);
        throw new Exception("Recipient address <$to> rejected: " . $response);
    }

    // Prepare for message body data
    fwrite($socket, "DATA\r\n");
    $response = $read_response($socket);
    if (substr($response, 0, 3) !== '354') {
        fclose($socket);
        throw new Exception("DATA initiation failed: " . $response);
    }

    // Build standard MIME headers
    $boundary = md5(uniqid(time()));

    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/alternative; boundary=\"$boundary\"\r\n";
    $headers .= "From: =?UTF-8?B?" . base64_encode($from_name) . "?= <$from>\r\n";
    $headers .= "Reply-To: <$reply_to>\r\n";
    $headers .= "To: <$to>\r\n";
    $headers .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
    $headers .= "Date: " . date('r') . "\r\n";
    $headers .= "X-Mailer: SocketMailer/1.0\r\n";
    $headers .= "\r\n";

    // Plain text alternative with safe wrapping
    $plain_msg = wordwrap(strip_tags($html_body), 998, "\r\n", true);
    $encoded_plain = chunk_split(base64_encode($plain_msg), 76, "\r\n");
    $encoded_html = chunk_split(base64_encode($html_body), 76, "\r\n");

    $email_body = "--$boundary\r\n";
    $email_body .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $email_body .= "Content-Transfer-Encoding: base64\r\n\r\n";
    $email_body .= $encoded_plain . "\r\n";

    // HTML portion
    $email_body .= "--$boundary\r\n";
    $email_body .= "Content-Type: text/html; charset=UTF-8\r\n";
    $email_body .= "Content-Transfer-Encoding: base64\r\n\r\n";
    $email_body .= $encoded_html . "\r\n";
    $email_body .= "--$boundary--\r\n";

    // Submit content (must terminate with standard SMTP CRLF.CRLF)
    fwrite($socket, $headers . $email_body . "\r\n.\r\n");
    $response = $read_response($socket);
    if (substr($response, 0, 3) !== '250') {
        fclose($socket);
        throw new Exception("Message content transmission failed: " . $response);
    }

    // Gracefully terminate connection
    fwrite($socket, "QUIT\r\n");
    fclose($socket);
    return true;
}
?>