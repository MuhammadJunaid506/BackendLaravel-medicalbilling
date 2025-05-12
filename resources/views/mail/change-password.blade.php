<!DOCTYPE html>
<html>
<head>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            padding: 20px;
            border-radius: 4px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            color: #333333;
            font-size: 24px;
            margin-bottom: 20px;
        }
        p {
            color: #555555;
            font-size: 16px;
            margin-bottom: 20px;
        }
        .password {
            background-color: #f9f9f9;
            border-radius: 4px;
            padding: 10px;
            font-family: monospace;
            font-size: 14px;
        }
        
    </style>
</head>
<body>
    <div class="container">
        <h1>Password Change Notification</h1>
        <p>Your password has been changed. Please find your new password below:</p>
        <div class="password">{{ $newPassword }}</div>
        <p>For security reasons, we recommend changing your password after logging in.</p>
        
    </div>
</body>
</html>