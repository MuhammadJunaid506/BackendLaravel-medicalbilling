<!DOCTYPE html>
<html lang="en">

<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>eClinicAssist - Profile Completion</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 20px;
        }

        .container {
            max-width: 600px;
            margin: auto;
        }

        .header {
            background-color: #4CAF50;
            color: white;
            padding: 15px;
            text-align: center;
        }

        .content {
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-top: 20px;
        }

        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 12px;
            color: #888;
        }

        .button {
            display: inline-block;
            padding: 10px 20px;
            font-size: 16px;
            text-align: center;
            text-decoration: none;
            background-color: #4CAF50;
            color: white;
            border-radius: 5px;
        }
    </style>
</head>

<body>
    <div style="max-width: 800px;margin: auto;">

        <div style="padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-top: 20px;">
            <p>Hello {{$emailData['provider_name']}},</p>
            <p>
                {{$emailData['practice_name']}} are excited to have you on their network of trusted providers. eClinicAssist has received a request to have you affiliated with {{$emailData['locations']}} facility(ies).
            </p>

            <p>We need your help to complete your eClinicAssist Profile through the secure form link below, so we can gather all necessary information for seamless provider credentialing and accurate medical billing.</p>
            <p><a href="{{$emailData['link']}}" style="display: inline-block;
            padding: 10px 20px;
            font-size: 16px;
            text-align: center;
            text-decoration: none;
            background-color: #4CAF50;
            color: white;
            border-radius: 5px;">Provider Profile</a></p>
            <p>We understand the importance of data privacy and use industry-leading security protocols, including multi-factor authentication, end-to-end encryption, and secure data centers, to keep your information safe.</p>
            <div style="
            margin-top: 20px;
            ">
                <p>Regards,<br>eClinicAssist<br>Assisting your clinics</p>
            </div>
        </div>

    </div>
</body>

</html>