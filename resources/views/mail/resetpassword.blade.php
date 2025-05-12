<html>
    <body>
    <title>Password Reset</title>    
        <table>
            <thead>
                <th></th>
                <th></th>
                <th></th>
            </thead>
            <tbody>
                
                <tr>
                    <td style="padding:0 0 20px 0;">Hello {{$mailData["name"]}},</td>
                </tr>        
                <tr>
                    <td style="padding:0 0 20px 0;">Forgot your password?</td>
                </tr>        
                <tr>
                    <td><p>We received a request to reset the password for your account.</p>
                </td>
                </tr>          
                <tr>
                    <td><p>To reset your password, click on the button below:</p>
                    <a href="{{$mailData['link']}}">Reset password</a>
                </td>
                </tr> 
                <tr>
                    <td><p>Or copy and paste the URL into your browser:</p>
                </td>
                </tr>
                <tr>
                <td><p><a href="{{$mailData['link']}}" target="_blank">{{$mailData['link']}}</a></p></td>
                </tr>          
                </tbody>
            </table>
    </body>
</html>
