<!DOCTYPE html>
<html>

<head>
    <title>Invalid Attempts</title>
</head>

<body>
    
    <table>
        <thead>
            <th></th>
            <th></th>
            <th></th>
        </thead>
        <tbody>
            <tr>
                <td style="padding:0 0 40px 0;">Hey <b>{{ $mailData['name'] }}</b>, </td>
            </tr>
            <br />
            <br />
            <tr>
                <td style="padding:0 0 20px 0;"><b>{{ $mailData['locked_user'] }}</b> has been trying to log in to their eClinicAssist account 3 times without success.</td>
            </tr>
            <tr>
                <td style="padding:0 0 20px 0;">We've locked the account to protect it from unauthorized access.
                    <br />

                    <br/>
                    The eClinicAssist Team.
                    </pre>
                </td>
            </tr>

        </tbody>
    </table>

</body>

</html>