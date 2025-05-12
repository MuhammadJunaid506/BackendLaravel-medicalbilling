<!DOCTYPE html>
<html>

<head>
    <title>Login Credentials</title>
</head>
<style>
    table th,
    td {
        border: 2px solid #000;
    }

    table th {
        padding: 10px;
    }
</style>

<body>
    <table>
        <thead>
            <th></th>
            <th></th>
            <th></th>
        </thead>
        <tbody>
            <tr>
                <td>
                    <h3>Login Credentials</h3>
                </td>
            </tr>
            <tr>
                <td style="padding:0 0 20px 0;">Hello, <b>{{$mailData['name']}}</b></td>
            </tr>
            <tr>
                <td style="padding:0 0 20px 0;">We welcome you onboard</td>
            </tr>
            <tr>
                @if($mailData['provider_type'] === "solo")
                <td style="padding:0 0 20px 0;">
                    <p>We have successfully added solo provider of name <b>{{$mailData['name']}}</b> in project Eclinic Assist, here your login details for Eclinic Assist.</p>
                </td>
                @elseif($mailData['provider_type'] === "group")
                <td style="padding:0 0 20px 0;">
                    <p>We have successfully added group provider of name <b>{{$mailData['name']}}</b> having business <b>{{$mailData['legal_business_name']}}</b> in project Eclinic Assist, here your login details for Eclinic Assist.</p>
                </td>
                @endif
            </tr>
            <tr>
                <td>
                    <p>Email Address : {{$mailData['login_email']}}</p>
                </td>
            </tr>
            <tr>
                <td style="padding:0 0 20px 0;">
                    <p>Password : {{$mailData['password']}}</p>
                </td>
            </tr>
            <tr>
                <td style="padding:0 0 20px 0;">
                    <p>You will be asked to change your password on your first log-in</p>
                </td>
            </tr>
            <tr>
                <td>
                    <p>Regards,</p>
                </td>
            </tr>
            <tr>
                <td>
                    <p>Eclinic Assist</p>
                </td>
            </tr>
        </tbody>
    </table>

</body>

</html>