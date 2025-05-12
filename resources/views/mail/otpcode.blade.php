<!DOCTYPE html>
<html>

<head>
    <title>Login OTP</title>
</head>

<body>
    <!-- <h1>Login OPT </h1>
    <p>{{ $mailData['otp_code'] }}</p>
  
    
     
    <p>Thank you</p> -->
    <table>
        <thead>
            <th></th>
            <th></th>
            <th></th>
        </thead>
        <tbody>
            <tr>
                <td style="padding:0 0 40px 0;">Hello {{ $mailData['name'] }}, </td>
            </tr>
            <br />
            <br />
            <tr>
                <td style="padding:0 0 20px 0;">We take the security of your eClinicAssist account seriously. That's why we're sending you this One-Time
                    Password (OTP) code to help keep your account safe and secure.</td>
            </tr>
            <tr>
                <td style="padding:0 0 20px 0;">Here's your OTP code: <b>{{ $mailData['otp_code'] }}</b>
                    <br />
                    <p style="padding:0 0 20px 0;">Think of it as the key to the door that keeps all your medical information safe and secure. 
                        <br>
                        <br>
                        Best, 
                        <br>
                        <br>
                        The eClinicAssist Team.
                    </pre>
                </td>
            </tr>

        </tbody>
    </table>

</body>

</html>